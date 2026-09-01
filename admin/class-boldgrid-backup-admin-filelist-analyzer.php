<?php
/**
 * File: class-boldgrid-backup-admin-filelist-analyzer.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.14.13
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Filelist
 *
 * @since 1.14.13
 */
class Boldgrid_Backup_Admin_Filelist_Analyzer {
	/**
	 * The key used to save this setting in the Total Upkeep settings.
	 *
	 * @since 1.14.13
	 * @var string
	 */
	public static $settings_key = 'filelist_analysis';

	/**
	 * The unix timestamp used for the parent backup's log file.
	 *
	 * This is passed in via the constructor, and is only used when creating the log file. We want
	 * the unixtime in the "backup" and "backup filelist" log fies to match so you know their is a
	 * relationship.
	 *
	 * @since 1.14.13
	 * @access private
	 * @var int
	 */
	private $log_time;

	/**
	 * An array of files.
	 *
	 * This is passed in via the contructor. This is the same filelist that is passed to each compressor
	 * so they know which files to backup.
	 *
	 * @since 1.14.13
	 * @access private
	 * @var array
	 */
	private $filelist;

	/**
	 * Constructor.
	 *
	 * @since 1.14.13
	 *
	 * @param array $filelist
	 * @param int   $log_time
	 */
	public function __construct( $filelist = array(), $log_time = 0 ) {
		$this->filelist = is_array( $filelist ) ? $filelist : array();
		$this->log_time = ! empty( $log_time ) ? $log_time : time();
	}

	/**
	 * Whether or not the filelist analyer is enabled.
	 *
	 * @since 1.14.13
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$core    = apply_filters( 'boldgrid_backup_get_core', null );
		$setting = $core->settings->get_setting( self::$settings_key );

		return ! empty( $setting );
	}

	/**
	 * Run.
	 *
	 * Do all the magic and write the log file.
	 *
	 * @since 1.14.13
	 */
	public function run() {
		$core   = apply_filters( 'boldgrid_backup_get_core', null );
		$logger = new Boldgrid_Backup_Admin_Log( $core );

		$size_by_extension  = array();
		$count_by_extension = array();
		$size_by_dir        = array();

		$logger->init( 'archive-' . $this->log_time . '-filelist.log' );

		// Loop through each file.
		foreach ( $this->filelist as $file ) {
			$extension = pathinfo( $file[1], PATHINFO_EXTENSION );
			$dir       = dirname( $file[1] );

			// Generate our stats.
			$size_by_extension[ $extension ]  = empty( $size_by_extension[ $extension ] ) ? $file[2] : $size_by_extension[ $extension ] + $file[2];
			$count_by_extension[ $extension ] = empty( $count_by_extension[ $extension ] ) ? 1 : $count_by_extension[ $extension ] + 1;
			$size_by_dir[ $dir ]              = empty( $size_by_dir[ $dir ] ) ? $file[2] : $size_by_dir[ $dir ] + $file[2];
		}

		// Display top 100 files.
		$limit   = 100;
		$to_show = count( $size_by_extension ) >= $limit ? $limit : count( $size_by_extension );
		$key     = 1;

		$logger->add_separator();
		$logger->add( 'LARGEST FILES' );

		usort( $this->filelist, function ( $item1, $item2 ) {
			return $item1[2] <= $item2[2] ? 1 : -1;
		} );

		foreach ( $this->filelist as $file ) {
			$logger->add( '(' . $key . ') ' . $file[1] . ' - ' . size_format( $file[2], 2 ) );

			$key++;
			if ( $key > $to_show ) {
				break;
			}
		}

		// Display size by extension.
		$limit   = 30;
		$to_show = count( $size_by_extension ) >= $limit ? $limit : count( $size_by_extension );
		$key     = 1;

		$logger->add_separator();
		$logger->add( 'SIZE BY EXTENSION' );

		arsort( $size_by_extension );

		foreach ( $size_by_extension as $extension => $size ) {
			$logger->add( '(' . $key . ') .' . $extension . ' - ' . number_format( $count_by_extension[ $extension ] ) . ' files totaling ' . size_format( $size, 2 ) );

			$key++;
			if ( $key > $to_show ) {
				break;
			}
		}

		// Display size by directory.
		$limit   = 30;
		$to_show = count( $size_by_dir ) >= $limit ? $limit : count( $size_by_dir );
		$key     = 1;

		$logger->add_separator();
		$logger->add( 'SIZE BY DIRECTORY' );

		arsort( $size_by_dir );

		foreach ( $size_by_dir as $directory => $size ) {
			$logger->add( '(' . $key . ') ' . $directory . ' - ' . size_format( $size, 2 ) );

			$key++;
			if ( $key > $to_show ) {
				break;
			}
		}
	}
}
