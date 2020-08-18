<?php
/**
 * File: class-boldgrid-backup-admin-environment.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Environment
 *
 * The purpose of this class is to help us get more information about our environment. It should also
 * help answer questions, such as, has our environment changed?
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Admin_Environment {
	/**
	 * The option to which we'll save environment data to.
	 *
	 * For example, to know if something has changed in the environment, we'll need to know both the
	 * old values and the new values. The old values will be stored in this option.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $option_name = 'boldgrid_backup_environment';

	/**
	 * Get a saved environment value.
	 *
	 * @since SINCEVERSION
	 *
	 * @see self::option_name
	 *
	 * @param  string $key     The name of the value to get.
	 * @param  mixed  $default The default value to return.
	 * @return mixed
	 */
	private function get_saved_value( $key, $default = false ) {
		$saved_values = $this->get_saved_values();

		return isset( $saved_values[ $key ] ) ? $saved_values[ $key ] : $default;
	}

	/**
	 * Get all of our saved values.
	 *
	 * @since SINCEVERSION
	 *
	 * @see self::option_name
	 *
	 * @return array
	 */
	private function get_saved_values() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Save a specific environment value.
	 *
	 * @since SINCEVERSION
	 *
	 * @see self::option_name
	 *
	 * @param string $key   The environment key, like "hostname".
	 * @param mixed  $value The value, such as "domain.com".
	 */
	private function save_value( $key, $value ) {
		$saved_values = $this->get_saved_values();

		$saved_values[ $key ] = $value;

		$this->save_values( $saved_values );
	}

	/**
	 * Save all of our environment values.
	 *
	 * @since SINCEVERSION
	 *
	 * @see self::option_name
	 *
	 * @param array $values All of our environment values.
	 */
	private function save_values( $values ) {
		update_option( $this->option_name, $values );
	}

	/**
	 * Determine whether or not our environment has changed.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function has_changed() {
		/*
		 * Run all the tests.
		 *
		 * This is done instead of a simple return ! thing1_changed() and ! thing2_changed and etc...
		 *
		 * If they're not run all at the same time, it could potentionally take the same number of
		 * pageloads as there are tests to set all the initial values.
		 *
		 * An xhprof of the first 3 tests shows it takes 0.00065 seconds and 15.5kb
		 */
		$has_hostname_changed   = $this->has_hostname_changed();
		$has_phpversion_changed = $this->has_phpversion_changed();
		$has_wpversion_changed  = $this->has_wpversion_changed();

		return $has_hostname_changed ||
			$has_phpversion_changed ||
			$has_wpversion_changed;
	}

	/**
	 * Determine whether or not our hostname has changed.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function has_hostname_changed() {
		$key = 'hostname';

		$current_hostname  = gethostname();
		$previous_hostname = $this->get_saved_value( $key );

		/*
		 * We took the time to get the current hostname, so save it. We need to know our previous hostname,
		 * so it's important to only save AFTER we've retrieved the previous hostname.
		 */
		$this->save_value( $key, $current_hostname );

		return $current_hostname !== $previous_hostname;
	}

	/**
	 * Determine whether or not our php version has changed.
	 *
	 * @since SINCEVERSION
	 *
	 * @bool
	 */
	public function has_phpversion_changed() {
		$key = 'phpversion';

		$current_phpversion  = phpversion();
		$previous_phpversion = $this->get_saved_value( $key );

		/*
		 * We took the time to get the current phpversion, so save it. We need to know our previous phpversion,
		 * so it's important to only save AFTER we've retrieved the previous phpversion.
		 */
		$this->save_value( $key, $current_phpversion );

		return $current_phpversion !== $previous_phpversion;
	}

	/**
	 * Determine whether or not our WordPress version has changed.
	 *
	 * @since SINCEVERSION
	 *
	 * @global string $wp_version';
	 *
	 * @bool
	 */
	public function has_wpversion_changed() {
		$key = 'wpversion';

		global $wp_version;

		$current_wpversion  = $wp_version;
		$previous_wpversion = $this->get_saved_value( $key );

		/*
		 * We took the time to get the current wpversion, so save it. We need to know our previous wpversion,
		 * so it's important to only save AFTER we've retrieved the previous wpversion.
		 */
		$this->save_value( $key, $current_wpversion );

		return $current_wpversion !== $previous_wpversion;
	}
}
