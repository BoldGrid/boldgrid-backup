<?php
/**
 * File: class-boldgrid-backup-admin-zip.php
 *
 * Helpers for reading and repairing ZIP archives, including archives whose
 * end-of-central-directory record omits ZIP64 metadata when entry counts exceed
 * 65535 (a failure mode observed with PHP ZipArchive on large backups).
 *
 * @link       https://www.boldgrid.com
 * @since      1.17.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Zip
 *
 * @since 1.17.3
 */
class Boldgrid_Backup_Admin_Zip {
	/**
	 * ZIP end-of-central-directory signature.
	 *
	 * @since 1.17.3
	 * @var string
	 */
	const SIG_EOCD = "PK\x05\x06";

	/**
	 * ZIP64 end-of-central-directory signature.
	 *
	 * @since 1.17.3
	 * @var string
	 */
	const SIG_ZIP64_EOCD = "PK\x06\x06";

	/**
	 * ZIP64 end-of-central-directory locator signature.
	 *
	 * @since 1.17.3
	 * @var string
	 */
	const SIG_ZIP64_LOCATOR = "PK\x06\x07";

	/**
	 * Central directory file header signature.
	 *
	 * @since 1.17.3
	 * @var string
	 */
	const SIG_CENTRAL_DIR = "PK\x01\x02";

	/**
	 * Call a callback for each central-directory entry.
	 *
	 * Walks by central-directory size rather than the EOCD entry count, so
	 * archives with a broken EOCD (entry count 0 / missing ZIP64) still list.
	 *
	 * @since 1.17.3
	 *
	 * @param string   $filepath Archive path.
	 * @param callable $callback function( array $entry ): void|false. Return false to stop.
	 * @return bool True when the central directory was walked, false on failure.
	 */
	public static function each_central_directory_entry( $filepath, $callback ) {
		$eocd = self::read_eocd( $filepath );
		if ( empty( $eocd ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $filepath, 'rb' );
		if ( false === $handle ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		if ( 0 !== fseek( $handle, $eocd['cd_offset'] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
			return false;
		}

		$cd_end = $eocd['cd_offset'] + $eocd['cd_size'];
		$index  = 0;

		while ( ftell( $handle ) < $cd_end ) {
			$entry = self::read_central_directory_entry( $handle, $index );
			if ( null === $entry ) {
				break;
			}

			$result = call_user_func( $callback, $entry );
			if ( false === $result ) {
				break;
			}

			$index++;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		return $index > 0 || 0 === $eocd['cd_size'];
	}

	/**
	 * List central-directory entries in a PclZip-compatible shape.
	 *
	 * @since 1.17.3
	 *
	 * @param string $filepath Archive path.
	 * @return array
	 */
	public static function list_central_directory( $filepath ) {
		$list = array();

		self::each_central_directory_entry(
			$filepath,
			function ( $entry ) use ( &$list ) {
				$list[] = $entry;
			}
		);

		return $list;
	}

	/**
	 * Open a ZipArchive, repairing a missing ZIP64 EOCD when needed.
	 *
	 * @since 1.17.3
	 *
	 * @param string $filepath Archive path.
	 * @return ZipArchive|false
	 */
	public static function open_zip_archive( $filepath ) {
		if ( ! class_exists( 'ZipArchive' ) || empty( $filepath ) || ! is_readable( $filepath ) ) {
			return false;
		}

		$zip    = new ZipArchive();
		$status = $zip->open( $filepath );

		if ( true === $status ) {
			return $zip;
		}

		if ( ! self::maybe_repair_zip64_eocd( $filepath ) ) {
			return false;
		}

		$status = $zip->open( $filepath );
		return true === $status ? $zip : false;
	}

	/**
	 * Repair archives that need ZIP64 EOCD metadata but lack it.
	 *
	 * PHP ZipArchive has been observed to write a classic EOCD with entry counts
	 * of 0 and no ZIP64 records when a backup contains more than 65535 entries.
	 * The central directory itself remains intact; Info-ZIP `zip -FF` rewrites
	 * the same end records this method writes.
	 *
	 * @since 1.17.3
	 *
	 * @param string $filepath Archive path.
	 * @return bool True when the archive is usable (already valid or repaired).
	 */
	public static function maybe_repair_zip64_eocd( $filepath ) {
		if ( empty( $filepath ) || ! is_writable( $filepath ) ) {
			return false;
		}

		$eocd = self::read_eocd( $filepath );
		if ( empty( $eocd ) ) {
			return false;
		}

		if ( self::has_zip64_locator( $filepath, $eocd['eocd_offset'] ) ) {
			return true;
		}

		$entry_count = 0;
		self::each_central_directory_entry(
			$filepath,
			function () use ( &$entry_count ) {
				$entry_count++;
			}
		);

		if ( $entry_count < 1 ) {
			return 0 === $eocd['total_entries'];
		}

		$needs_zip64_count = $entry_count > 0xffff;
		$broken_zero_count = ( 0 === $eocd['total_entries'] && $entry_count > 0 );

		if ( ! $needs_zip64_count && ! $broken_zero_count ) {
			return true;
		}

		if ( ! $needs_zip64_count && $eocd['total_entries'] === $entry_count ) {
			return true;
		}

		return self::write_zip64_eocd( $filepath, $eocd, $entry_count );
	}

	/**
	 * Convert a DOS date/time pair to a Unix timestamp (UTC).
	 *
	 * @since 1.17.3
	 *
	 * @param int $dos_time DOS time.
	 * @param int $dos_date DOS date.
	 * @return int
	 */
	public static function dos_to_unix( $dos_time, $dos_date ) {
		$seconds = 2 * ( $dos_time & 0x1f );
		$minutes = ( $dos_time >> 5 ) & 0x3f;
		$hours   = ( $dos_time >> 11 ) & 0x1f;
		$day     = $dos_date & 0x1f;
		$month   = ( $dos_date >> 5 ) & 0x0f;
		$year    = ( ( $dos_date >> 9 ) & 0x7f ) + 1980;

		if ( $month < 1 || $month > 12 || $day < 1 || $day > 31 ) {
			return 0;
		}

		return gmmktime( $hours, $minutes, $seconds, $month, $day, $year );
	}

	/**
	 * Read classic EOCD fields from the end of a zip file.
	 *
	 * @since 1.17.3
	 *
	 * @param string $filepath Archive path.
	 * @return array|false {
	 *     @type int $disk
	 *     @type int $cd_disk
	 *     @type int $disk_entries
	 *     @type int $total_entries
	 *     @type int $cd_size
	 *     @type int $cd_offset
	 *     @type int $comment_len
	 *     @type int $eocd_offset
	 * }
	 */
	protected static function read_eocd( $filepath ) {
		if ( empty( $filepath ) || ! is_readable( $filepath ) ) {
			return false;
		}

		$size = filesize( $filepath );
		if ( false === $size || $size < 22 ) {
			return false;
		}

		$read = (int) min( $size, 65557 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $filepath, 'rb' );
		if ( false === $handle ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		fseek( $handle, $size - $read );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$tail = fread( $handle, $read );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		if ( false === $tail ) {
			return false;
		}

		$pos = strrpos( $tail, self::SIG_EOCD );
		if ( false === $pos ) {
			return false;
		}

		$eocd_offset = $size - $read + $pos;
		$record      = substr( $tail, $pos, 22 );
		if ( strlen( $record ) < 22 ) {
			return false;
		}

		$fields = unpack( 'vdisk/vcd_disk/vdisk_entries/vtotal_entries/Vcd_size/Vcd_offset/vcomment_len', substr( $record, 4 ) );
		if ( empty( $fields ) ) {
			return false;
		}

		$fields['eocd_offset'] = $eocd_offset;
		return $fields;
	}

	/**
	 * Whether a ZIP64 EOCD locator exists before the classic EOCD.
	 *
	 * @since 1.17.3
	 *
	 * @param string $filepath    Archive path.
	 * @param int    $eocd_offset Classic EOCD offset.
	 * @return bool
	 */
	protected static function has_zip64_locator( $filepath, $eocd_offset ) {
		if ( $eocd_offset < 20 ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $filepath, 'rb' );
		if ( false === $handle ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		fseek( $handle, $eocd_offset - 20 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$locator = fread( $handle, 20 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		return is_string( $locator ) && 0 === strpos( $locator, self::SIG_ZIP64_LOCATOR );
	}

	/**
	 * Read one central-directory entry from an open file handle.
	 *
	 * @since 1.17.3
	 *
	 * @param resource $handle Open file handle positioned at a CD entry.
	 * @param int      $index  Entry index.
	 * @return array|null
	 */
	protected static function read_central_directory_entry( $handle, $index ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$sig = fread( $handle, 4 );
		if ( self::SIG_CENTRAL_DIR !== $sig ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$header = fread( $handle, 42 );
		if ( false === $header || strlen( $header ) < 42 ) {
			return null;
		}

		$fields = unpack(
			'vver_made/vver_need/vflag/vmethod/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk_start/vinternal_attr/Vexternal_attr/Voffset',
			$header
		);
		if ( empty( $fields ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$filename = $fields['filename_len'] ? fread( $handle, $fields['filename_len'] ) : '';
		if ( $fields['extra_len'] ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			fread( $handle, $fields['extra_len'] );
		}
		if ( $fields['comment_len'] ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			fread( $handle, $fields['comment_len'] );
		}

		$is_folder = '/' === substr( $filename, -1 ) || (bool) ( $fields['external_attr'] & 0x10 );

		return array(
			'filename'        => $filename,
			'stored_filename' => $filename,
			'size'            => $fields['size'],
			'compressed_size' => $fields['compressed_size'],
			'mtime'           => self::dos_to_unix( $fields['mtime'], $fields['mdate'] ),
			'comment'         => '',
			'folder'          => (bool) $is_folder,
			'index'           => $index,
			'status'          => 'ok',
			'crc'             => $fields['crc'],
		);
	}

	/**
	 * Rewrite end-of-archive records with ZIP64 EOCD + locator + classic EOCD.
	 *
	 * @since 1.17.3
	 *
	 * @param string $filepath    Archive path.
	 * @param array  $eocd        Existing classic EOCD fields.
	 * @param int    $entry_count Actual central-directory entry count.
	 * @return bool
	 */
	protected static function write_zip64_eocd( $filepath, $eocd, $entry_count ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $filepath, 'r+b' );
		if ( false === $handle ) {
			return false;
		}

		$zip64_offset = $eocd['eocd_offset'];
		$cd_size      = $eocd['cd_size'];
		$cd_offset    = $eocd['cd_offset'];

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		if ( 0 !== fseek( $handle, $zip64_offset ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
			return false;
		}

		/*
		 * ZIP64 EOCD: size of record (44), version made by (Unix 3.0 = 0x031E),
		 * version needed (45), disk numbers, entry counts, CD size/offset.
		 * pack format "P" is little-endian uint64 (PHP >= 5.6.3).
		 */
		$zip64_eocd = pack( 'V', 0x06064b50 ) .
			pack( 'P', 44 ) .
			pack( 'vv', 0x031e, 45 ) .
			pack( 'VV', 0, 0 ) .
			pack( 'P', $entry_count ) .
			pack( 'P', $entry_count ) .
			pack( 'P', $cd_size ) .
			pack( 'P', $cd_offset );

		// Locator: sig, disk with ZIP64 EOCD, offset of ZIP64 EOCD, total disks.
		$locator = pack( 'VV', 0x07064b50, 0 ) . pack( 'P', $zip64_offset ) . pack( 'V', 1 );

		$classic_entries = $entry_count > 0xffff ? 0xffff : $entry_count;
		$classic_eocd    = pack(
			'VvvvvVVv',
			0x06054b50,
			0,
			0,
			$classic_entries,
			$classic_entries,
			$cd_size > 0xffffffff ? 0xffffffff : $cd_size,
			$cd_offset > 0xffffffff ? 0xffffffff : $cd_offset,
			0
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		$written = fwrite( $handle, $zip64_eocd . $locator . $classic_eocd );
		if ( false === $written ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
			return false;
		}

		$new_size = $zip64_offset + strlen( $zip64_eocd ) + strlen( $locator ) + strlen( $classic_eocd );
		fflush( $handle );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftruncate
		ftruncate( $handle, $new_size );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		if ( ! class_exists( 'ZipArchive' ) ) {
			return true;
		}

		$zip    = new ZipArchive();
		$status = $zip->open( $filepath );
		if ( true === $status ) {
			$zip->close();
			return true;
		}

		return false;
	}
}
