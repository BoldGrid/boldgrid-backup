<?php
/**
 * Local storage.
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
 * Local storage.
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Storage_Local {

	/**
	 * The core class object.
	 *
	 * @since  1.5.2
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Delete a local backup file.
	 *
	 * This method is registered to the "boldgrid_backup_delete_local" action.
	 * If the user does not wish to keep local copies of backups, after all
	 * remote backup providers have been run, this method will run and delete
	 * it locally.
	 *
	 * @since 1.5.2
	 *
	 * @param string $filepath Full path to backup file.
	 */
	public function delete_local( $filepath ) {
		return $this->core->wp_filesystem->delete( $filepath );
	}

	/**
	 * Action to take after a backup file has been created.
	 *
	 * If the user has not chosen to keep local copies, this method adds the
	 * "delete local copy" to the jobs queue.
	 *
	 * @since 1.5.2
	 *
	 * @see self::delete_local()
	 *
	 * @param array $info
	 */
	public function post_archive_files( $info ) {

		/*
		 * We only want to add this to the jobs queue if we're in the middle of
		 * an automatic backup. If the user simply clicked on "Backup site now",
		 * we don't want to automatically delete the backup, there's a button
		 * for that.
		 */
		if( ! $this->core->doing_cron ) {
			return;
		}

		if( $this->core->remote->is_enabled( 'local' ) ) {
			return;
		}

		$args = array(
			'filepath' => $info['filepath'],
			'action' => 'boldgrid_backup_delete_local',
			'action_data' => $info['filepath'],
			'action_title' => __( 'Delete backup from Web Server', 'boldgrid-backup' ),
		);

		$this->core->jobs->add( $args );
	}
}
