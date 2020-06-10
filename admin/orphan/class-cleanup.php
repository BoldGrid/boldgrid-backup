<?php
/**
 * Orphan Cleanup class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.13.8
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Orphan;

/**
 * Class: Cleanup
 *
 * @since 1.13.8
 */
class Cleanup {
	/**
	 * An instance of core.
	 *
	 * @since 1.13.8
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Header message for log file.
	 *
	 * @since 1.13.8
	 * @access private
	 * @var string
	 */
	private $header;

	/**
	 * Constructor.
	 *
	 * @since 1.13.8
	 */
	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->header = "\n" .
		'This is your "orphaned files" log. If a backup process fails, orphaned files might remain in ' .
		'your backup directory, and they will need to be deleted. Total Upkeep will delete those files ' .
		'and keep a log of them here. If this file exists and you see an excess of orphaned files being ' .
		'deleted, please contact support for further assistance at https://wordpress.org/support/plugin/boldgrid-backup/';
	}

	/**
	 * Delete orphaned files.
	 *
	 * @since 1.13.8
	 */
	public function run() {
		$orphan_finder = new Finder();
		$orphans       = $orphan_finder->run();

		if ( $orphans ) {
			$logger = new \Boldgrid_Backup_Admin_Log( $this->core );
			$logger->init( 'orphaned-files.log' );

			if ( $logger->is_new ) {
				$logger->add( $this->header );
			}

			foreach ( $orphans as $filepath => $data ) {
				$this->core->wp_filesystem->delete( $filepath );
				$logger->add( 'Deleted: ' . $filepath . ' (' . size_format( $data['size'], 2 ) . ')' );
			}
		}
	}
}
