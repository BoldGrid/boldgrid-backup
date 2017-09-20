<?php
/**
 * Boldgrid Backup Admin Auto Rollback.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * Boldgrid Backup Admin Auto Rollback class.
 *
 * We hook into "the upgrader_process_complete" (run when the download process
 * for a plugin install or update finishes). If the user has enabled auto
 * rollback and we have data in the boldgrid_backup_pending_rollback site
 * option, then we add the cron jobs for 5 minutes later to auto rollback.
 *
 * Auto Rollback works with the following site options:
 *
 * boldgrid_backup_pending_rollback When we manually create a backup, if we
 *                                  $_POST['is_updating'] === 'true', then the
 *                                  results of this backup file are saved in
 *                                  this option.
 *
 *                                  To cancel an auto rollback, this option
 *                                  needs to be deleted (and subsequent crons
 *                                  cleared).
 *
 *                                  array (
 *                                      compressor   "pcl_zip"
 *                                      db_duration  "0.16"
 *                                      dryrun       false
 *                                      duration     "20.07"
 *                                      filepath     "/home/user/boldgrid-backup/backup.zip"
 *                                      filesize     262562329
 *                                      lastmodunix  1505912200
 *                                      mail_success true
 *                                      mode         "backup"
 *                                      save         true
 *                                  );
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Auto_Rollback {

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add cron to permorm auto rollback.
	 *
	 * Based on scheduler, cron is either system cron or wp cron.
	 *
	 * @since 1.5.1
	 */
	public function add_cron() {
		$settings = $this->core->settings->get_settings();

		// If auto-rollback is not enabled, then abort.
		if ( 1 !== $settings['auto_rollback'] ) {
			$this->core->settings->delete_rollback_option();
			return;
		}

		// If a backup was not made prior to an update (from an update page), then abort.
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		if ( empty( $pending_rollback ) ) {
			return;
		}

		$archives = $this->core->get_archive_list();
		$archive_count = count( $archives );

		// If there are no archives, then abort.
		if ( $archive_count <= 0 ) {
			$this->core->settings->delete_rollback_option();
			return;
		}

		$scheduler = $this->core->scheduler->get();

		switch( $scheduler ) {
			case 'cron':
				$this->core->cron->add_restore_cron();
				break;
			case 'wp-cron':
				$this->core->wp_cron->add_restore_cron();
				break;
		}
	}
}
