<?php
/**
 * Cron class.
 *
 * This is a controller class, working with classes within the admin/cron folder.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin;

/**
 * Class: Cron
 *
 * @since 1.11.0
 */
class Cron {
	/**
	 * Get our cron configs.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_configs() {
		return require BOLDGRID_BACKUP_PATH . '/includes/config/config.cron.php';
	}

	/**
	 * Get our cron engine (either "cron" or "wp-cron").
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_engine() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		return $core->scheduler->get();
	}

	/**
	 * Get a cron entry.
	 *
	 * @since 1.11.0
	 *
	 * @param string $name The cron entry to get.
	 * @return mixed
	 */
	public function get_entry( $name ) {
		$configs = $this->get_configs();

		$engine = $this->get_engine();
		if ( empty( $engine ) ) {
			return false;
		}

		// Get an instance of our entry class.
		$entry = $this->get_entry_class();
		if ( empty( $entry ) ) {
			return false;
		}

		// Init our entry.
		switch ( $name ) {
			case 'backup':
				$entry->init_via_search( $configs['entries'][ $name ]['search'][ $engine ] );
				break;
			default:
				break;
		}

		return $entry;
	}

	/**
	 * Get our entry class object.
	 *
	 * Determine our cron engine and return the appropriate entry class object.
	 *
	 * @since 1.11.0
	 *
	 * @return mixed
	 */
	public function get_entry_class() {
		$entry_class = false;

		$engine = $this->get_engine();

		// Get our entry.
		switch ( $engine ) {
			case 'cron':
				$entry_class = new \Boldgrid\Backup\Admin\Cron\Entry\Crontab();
				break;
			case 'wp-cron':
				$entry_class = new \Boldgrid\Backup\Admin\Cron\Entry\Wpcron();
				break;
			default:
				break;
		}

		return $entry_class;
	}
}
