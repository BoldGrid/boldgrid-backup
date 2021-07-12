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
	 * @param  string $key     Index/key.
	 * @param  mixed  $default The default value to return if key is not set.
	 * @return mixed
	 */
	public static function get_arg( $key, $default = false ) {
		$args = get_option( self::$option_name );

		return isset( $args[ $key ] ) ? $args[ $key ] : $default;
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
	 * Set an array of data.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $arg   The key.
	 * @param string $value The value.
	 */
	public static function set_args( $data ) {
		foreach ( $data as $key => $value ) {
			self::set_arg( $key, $value );
		}
	}

	/**
	 * Init data.
	 *
	 * This removes all other in progress data and adds fresh data. This should only be used when a
	 * backup is initially started.
	 *
	 * @since 1.7.0
	 *
	 * @param array $args Arguments.
	 */
	public static function init( $args ) {
		update_option( self::$option_name, $args );
	}
}
