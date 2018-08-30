<?php
/**
 * File: class-boldgrid-backup-admin-in-progress-data.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_In_Progress_Data
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_In_Progress_Data {

	public static $option_name = 'boldgrid_backup_in_progress_data';

	/**
	 *
	 */
	public static function get_args() {
		return get_option( self::$option_name );
	}

	/**
	 *
	 */
	public static function get_markup() {
		return '
			<div id="boldgrid-backup-in-progress-bar" class="hidden">
				<div class="progress-label">Initializing backup...</div>
				<div id="last_file_archived"></div>
			</div>
		';
	}

	/**
	 *
	 */
	public static function set_arg( $arg, $value ) {
		$args = self::get_args();

		$args[$arg] = $value;

		update_option( self::$option_name, $args );
	}

	/**
	 *
	 */
	public static function set_args( $args ) {
		update_option( self::$option_name, $args );
	}
}
