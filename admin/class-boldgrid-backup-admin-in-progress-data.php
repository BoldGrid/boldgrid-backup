<?php
/**
 * Class: Boldgrid_Backup_Admin_In_Progress_Data
 *
 * This class used for managing the data used by the Boldgrid_Backup_Admin_In_Progress class.
 *
 * @link  https://www.boldgrid.com
 * @since 1.7.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_In_Progress_Data
 *
 * @since 1.7.0
 */
class Boldgrid_Backup_Admin_In_Progress_Data {
	/**
	 * Option name in which data is stored.
	 *
	 * @since 1.7.0
	 * @var   string
	 */
	public static $option_name = 'boldgrid_backup_in_progress_data';

	/**
	 * Delete one arguement.
	 *
	 * @since 1.7.0
	 *
	 * @param string $arg   The key.
	 */
	public static function delete_arg( $arg ) {
		$args = self::get_args();

		if ( isset( $args[ $arg ] ) ) {
			unset( $args[ $arg ] );
		}

		update_option( self::$option_name, $args );
	}

	/**
	 * Get one argument.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $key Index/key.
	 * @return mixed
	 */
	public static function get_arg( $key ) {
		$args = get_option( self::$option_name );

		return isset( $args[ $key ] ) ? $args[ $key ] : false;
	}

	/**
	 * Get all arguments.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_args() {
		return get_option( self::$option_name );
	}

	/**
	 * Get required markup to show the progress bar.
	 *
	 * Generally displayed under a "Backup Now" button.
	 *
	 * @since 1.7.0
	 *
	 * @param string $label Progress label.
	 */
	public static function get_markup( $label = null ) {
		$label = ! empty( $label ) ? $label : __( 'Initializing backup...', 'boldgrid-backup' );

		$steps = '<div id="boldgrid_backup_in_progress_steps">
			<div class="step" data-step="1">' . esc_html__( 'Backing up database...', 'boldgrid-backup' ) . '</div>
			<div class="step" data-step="2">' . esc_html__( 'Adding files to archive...', 'boldgrid-backup' ) . '</div>
			<div class="step" data-step="3">' . esc_html__( 'Saving archive to disk...', 'boldgrid-backup' ) . '</div>
		</div>';

		$progress_bar = '<div id="boldgrid-backup-in-progress-bar">
			<div class="progress-label">' . esc_html( $label ) . '</div>
			<div id="last_file_archived"></div>
		</div>';

		return '<div id="boldgrid_backup_in_progress_container" class="hidden">' . $steps . $progress_bar . '</div>';
	}

	/**
	 * Set one arguement, a key / value pair.
	 *
	 * @since 1.7.0
	 *
	 * @param string $arg   The key.
	 * @param string $value The value.
	 */
	public static function set_arg( $arg, $value ) {
		$args = self::get_args();

		$args[ $arg ] = $value;

		update_option( self::$option_name, $args );
	}

	/**
	 * Set arguments.
	 *
	 * @since 1.7.0
	 *
	 * @param array $args Arguments.
	 */
	public static function set_args( $args ) {
		update_option( self::$option_name, $args );
	}
}
