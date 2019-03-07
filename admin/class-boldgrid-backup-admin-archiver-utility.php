<?php
/**
 * File: class-boldgrid-backup-admin-archiver-utility.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.9.0
 *
 * @package    Boldgrid_Backup
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archiver_Utility
 *
 * This class is a utility class designed to help during the archiving process.
 *
 * @since 1.9.0
 */
class Boldgrid_Backup_Admin_Archiver_Utility {
	/**
	 * The core class object.
	 *
	 * @since 1.9.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.9.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Determine whether or not we are making a full backup.
	 *
	 * A full backup, in this case, is a backup meant for full restoration purposes.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_full_backup() {
		if ( $this->core->pre_auto_update ) {
			return true;
		}

		if ( $this->core->is_archiving_update_protection ) {
			return true;
		}

		if ( $this->core->is_backup_full ) {
			return true;
		}

		if ( $this->core->is_scheduled_backup && $this->core->settings->is_all_files() && $this->core->settings->is_all_tables() ) {
			return true;
		}

		return false;
	}
}
