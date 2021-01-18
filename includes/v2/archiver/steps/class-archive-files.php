<?php
/**
 * Archive Files class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver\Steps;

/**
 * Class: Archive_Files
 *
 * This class is used to archive a "type" of file. For example, this class can represet "plugins",
 * "themes", etc.
 *
 * @since SINCEVERSION
 */
class Archive_Files extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * An array of configs.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $configs;

	/**
	 * An array of files belonging to this "type".
	 *
	 * IE An array of all "plugins".
	 *
	 * Set via the self::set_filelist() method.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $filelist;

	/**
	 * The last key of the filelist archived.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $last_archived_key;

	private $last_key;

	/**
	 * The max batch size.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $max_batch_size = 25 * 1024 * 1024;

	/**
	 * Our parts class.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files\Parts
	 */
	private $parts;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $id     The id of this step.
	 * @param string $dir    The backup directory.
	 * @param array  $configs An array of configs.
	 */
	public function __construct( $id, $parent_id, $dir ) {
		parent::__construct( $id, $parent_id, $dir );

		$this->parts = new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files\Parts( $this );
	}

	/**
	 * Get our next batch data.
	 *
	 * Not too complicated, but probably the most complex part of the backup process. Need to loop through
	 * all the files and determine how to create the batches so the parts are as close as possible to
	 * the max.
	 *
	 * @since SINCEVERSION
	 *
	 * @return mixed An array of data on success, false on failure.
	 */
	private function get_next_batch() {
		// An array of files to add during this batch.
		$batch_filelist = array();

		$start_key = empty( $this->last_archived_key ) ? 0 : $this->last_archived_key + 1;

		$files_added = 0;

		$part = $this->parts->get_next();

		if ( ! empty( $this->configs['part_configs'] ) ) {
			$part->set_configs( $this->configs['part_configs'] );
		}

		/*
		 * Determine the max size to archive during this batch.
		 *
		 * When determining how large to make this batch:
		 * If we're trying to add 10MB to the part and we loop through and find 10MB of files, after
		 * compression it may only be 3MB. So instead of adding 10MB to the file, we only added 3MB.
		 * This can account for more itterations than needed. @todo Account for this in logic below.
		 */
		$max_batch_size = min( $part->get_remaining_size(), $this->max_batch_size );
		$remaining_size = $max_batch_size;
		$is_part_empty  = $part->is_empty();

		// If the very next file would push us over the limit, we need to get a new part.
		if ( ! $is_part_empty && $this->filelist[ $start_key ][2] > $remaining_size ) {
			$part->complete();
			$part = $this->parts->get_next();

			// Determine the max size to archive during this batch.
			$max_batch_size = min( $part->get_remaining_size(), $this->max_batch_size );
			$remaining_size = $max_batch_size;
			$is_part_empty  = $part->is_empty();
		}

		// At this point, no matter what, we're adding at least one file.
		foreach ( $this->filelist as $key => $file ) {
			if ( $key < $start_key ) {
				continue;
			}

			$too_big        = $file[2] > $remaining_size;
			$allow_in_batch = ! $too_big || ( 0 === $files_added && $is_part_empty );

			if ( $allow_in_batch ) {
				$batch_filelist[]        = $this->configs['use_full_filepath'] ? $file[0] : $file[1];
				$remaining_size         -= $file[2];
				$this->last_archived_key = $key;
				$files_added++;
			} else {
				break;
			}
		}

		// Write the batch file.
		$batch_filelist_filename = 'filelist-' . $this->configs['type'] . '-' . $start_key . '.txt';
		$batch_filelist_filepath = $this->get_path_to( $batch_filelist_filename );

		if ( empty( $batch_filelist ) ) {
			$this->fail( 'Something went wrong. Empty batch filelist ' . $batch_filelist_filename . ' was almost written.' );
			return false;
		}

		$success = $this->get_core()->wp_filesystem->put_contents( $batch_filelist_filepath, implode( PHP_EOL, $batch_filelist ) );

		$batch_info = array(
			'batch_filelist_filepath' => $batch_filelist_filepath,
			'part'                    => $part,
		);

		/*
		 * Log data.
		 *
		 * The additional spacing added to make it easier to read in the log.
		 */
		$part_size  = $part->get_size();
		$batch_zize = $max_batch_size - $remaining_size;
		$log_data   = array(
			'$batch_filelist_filename' => $batch_filelist_filename,
			'$start_key              ' => $start_key,
			'$files_added            ' => $files_added,
			'$last_archived_key      ' => $this->last_archived_key,
			'last key                ' => $this->last_key,
			'batch size              ' => size_format( $batch_zize, 2 ) . ' / ' . $batch_zize,
			'batch size max          ' => size_format( $max_batch_size, 2 ) . ' / ' . $max_batch_size,
			'part filename           ' => $part->get_filename(),
			'part size               ' => size_format( $part_size, 2 ) . ' / ' . $part_size,
			'part remaining size     ' => size_format( $remaining_size, 2 ) . ' / ' . $remaining_size,
		);
		$this->log( 'New batch created and needs processing: ' . print_r( $log_data, 1 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		return $success ? $batch_info : false;
	}

	/**
	 * Get our configs.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_configs() {
		return $this->configs;
	}

	/**
	 * Archive files.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'step', 3 );

		$this->add_attempt();

		/*
		 * Get our filelist.
		 *
		 * If we don't have any files, complete and return right now. For example, if we are filtering
		 * by "*.txt", we may not find any txt files in the uploads folder.
		 */
		$this->set_filelist();
		if ( empty( $this->filelist ) ) {
			$this->log( 'No files in filelist.' );
			$this->complete();
			return;
		}

		$this->last_key          = count( $this->filelist ) - 1;
		$this->last_archived_key = $this->get_data_type( 'step' )->get_key( 'last_archived_key', 0 );
		$archived_all_keys       = false;

		while ( ! $archived_all_keys ) {
			$batch_info = $this->get_next_batch();

			if ( false !== $batch_info ) {

				/*
				 * If a sysadmin kills the zip command but not this main process, we'll try again. If
				 * it was killed, sleep before the next attepmt.
				 */
				$zip_attempts     = 0;
				$zip_max_attempts = 3;
				$zip_success      = false;
				$zip_sleep        = 10;

				while ( ! $zip_success && ( $zip_attempts < $zip_max_attempts ) ) {
					$zip_attempts++;

					$zip_success = $batch_info['part']->add_batch( $batch_info['batch_filelist_filepath'] );

					if ( ! $zip_success ) {
						$this->log( 'Zipping failed.' );
						$this->unresponsive_time += $this->unresponsive_time;
						sleep( $zip_sleep );
					}
				}

				if ( $zip_success ) {
					// Save the last key we successfully archived.
					$this->get_data_type( 'step' )->set_key( 'last_archived_key', $this->last_archived_key );

					// If we've archived all the files, flag the last part as complete.
					if ( $this->last_archived_key === $this->last_key ) {
						$batch_info['part']->complete();
					}
				} else {
					$this->fail( 'Failed ' . $zip_max_attempts . ' times to add ' . $batch_info['batch_filelist_filepath'] . ' to archive.' );
					return false;
				}
			} else {
				$this->fail( 'Error getting last batch.' );
				return false;
			}

			$archived_all_keys = $this->last_archived_key === $this->last_key;
		}

		$this->complete();

		return true;
	}

	/**
	 *
	 */
	public function set_configs( $configs ) {
		$default_configs = array(
			'use_full_filepath' => false,
		);

		$this->configs = wp_parse_args( $configs, $default_configs );
	}

	/**
	 * Set our filelist.
	 *
	 * @since SINCEVERSION
	 */
	private function set_filelist() {
		$filelist_filepath = $this->get_path_to( 'filelist-' . $this->configs['type'] . '.json' );
		$json              = $this->get_core()->wp_filesystem->get_contents( $filelist_filepath );

		$this->filelist = json_decode( $json, true );
	}
}
