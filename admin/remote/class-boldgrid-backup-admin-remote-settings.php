<?php
/**
 * File: class-boldgrid-backup-admin-remote-settings.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.7.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/remote
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Remote_Settings
 *
 * The purpose of this class is to handle remote settings stored in the database. This class was
 * introduced as of 1.7.2, however prior code working with settings has not been refactored to use
 * this class.
 *
 * The remote settings are stored in the boldgrid_backup_settings option, such as:
 * boldgrid_backup_settings['remote']['ftp']['host']
 *
 * @since 1.7.2
 */
class Boldgrid_Backup_Admin_Remote_Settings {
	/**
	 * Our remote_id.
	 *
	 * For example, the 'ftp' in boldgrid_backup_settings['remote']['ftp'].
	 *
	 * @since 1.7.2
	 * @access private
	 * @var string
	 */
	private $remote_id;

	/**
	 * The settings key that stores the last successful login.
	 *
	 * For example, the last_login in boldgrid_backup_settings['remote']['ftp']['last_login'].
	 *
	 * @since 1.7.2
	 * @access private
	 * @var string
	 */
	private $last_login_key = 'last_login';

	/**
	 * The WordPress option that stores settings.
	 *
	 * @since 1.7.2
	 * @access private
	 * @var string
	 */
	private $option_name = 'boldgrid_backup_settings';

	/**
	 * The key within our settings option that contains all remote settings.
	 *
	 * For example, the 'remote' in boldgrid_backup_settings['remote']['ftp'].
	 *
	 * @since 1.7.2
	 * @access private
	 * @var string
	 */
	private $remote_key = 'remote';

	/**
	 * Constructor.
	 *
	 * @since 1.7.2
	 *
	 * @param string $remote_id The remote id, such as 'ftp' or 'amazon_s3'.
	 */
	public function __construct( $remote_id ) {
		$this->remote_id = $remote_id;
	}

	/**
	 * Delete all settings for this provider.
	 *
	 * @since 1.11.3
	 */
	public function delete_settings() {
		$this->save_settings( [] );
	}

	/**
	 * Get the time we last logged in successfully.
	 *
	 * @since 1.7.2
	 *
	 * @return int
	 */
	public function get_last_login() {
		return $this->get_setting( $this->last_login_key, 0 );
	}

	/**
	 * Get our boldgrid_backup_settings option.
	 *
	 * @since 1.7.2
	 *
	 * @return array
	 */
	public function get_option() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Get one setting.
	 *
	 * @since 1.7.2
	 *
	 * @param  string $key     The key of the setting.
	 * @param  mixed  $default The default value if one does not exist.
	 * @return mixed
	 */
	public function get_setting( $key, $default = false ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Get our remote_id's settings.
	 *
	 * For example, if our remote_id is 'ftp', return all of our ftp settings.
	 *
	 * @since 1.7.2
	 *
	 * @return array
	 */
	public function get_settings() {
		$option = $this->get_option();

		$settings = ! empty( $option[ $this->remote_key ][ $this->remote_id ] ) ? $option[ $this->remote_key ][ $this->remote_id ] : array();

		return $settings;
	}

	/**
	 * Deterine whether or not this provider has a set of settings.
	 *
	 * For example, if working with an s3 client, we'll want to know if it has a key, host, etc.
	 *
	 * @since 1.11.3
	 *
	 * @param  array $keys An array of keys to check for.
	 * @return bool
	 */
	public function has_setting_keys( array $keys ) {
		$has_setting_keys = true;

		$settings = $this->get_settings();

		foreach ( $keys as $key ) {
			if ( empty( $settings[ $key ] ) ) {
				$has_setting_keys = false;
			}
		}

		return $has_setting_keys;
	}

	/**
	 * Whether or not this remove provider has settings saved.
	 *
	 * @since 1.11.3
	 *
	 * @return bool
	 */
	public function has_settings() {
		$settings = $this->get_settings();

		return is_array( $settings ) && ! empty( $settings );
	}

	/**
	 * Determine whether or not our last login is within the last_login_lifetime range.
	 *
	 * Please see comments for last_login_lifetime within the plugin's config file.
	 *
	 * @since 1.7.2
	 *
	 * @return bool
	 */
	public function is_last_login_valid() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		return $this->get_last_login() + $core->configs['last_login_lifetime'] >= time();
	}

	/**
	 * Set and save a setting.
	 *
	 * @since 1.7.2
	 *
	 * @param string $key   Key/index.
	 * @param mixed  $value Value.
	 */
	public function save_setting( $key, $value ) {
		$settings = $this->get_settings();

		$settings[ $key ] = $value;

		$this->save_settings( $settings );
	}

	/**
	 * Save our remote_id's settings.
	 *
	 * @since 1.7.2
	 *
	 * @param array $settings An array containing our remote_id's settings.
	 */
	public function save_settings( $settings ) {
		$option = $this->get_option();

		$option[ $this->remote_key ][ $this->remote_id ] = $settings;

		update_option( $this->option_name, $option );
	}

	/**
	 * Set the time that we last logged in successfully.
	 *
	 * @since 1.7.2
	 */
	public function set_last_login() {
		$this->save_setting( $this->last_login_key, time() );
	}
}
