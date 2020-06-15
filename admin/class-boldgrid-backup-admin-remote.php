<?php
/**
 * File: class-boldgrid-backup-admin-remote.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Remote
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Remote {
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
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Determine if any storage locations are enabled.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $skip_local Whether or not to skip local storage when determining if any storage
	 *                         locations are enabled. Initially, this method returned true if any storage
	 *                         "provider" was enabled. Meaning, if you have local storage enabled only,
	 *                         this would return true. There are times you need to specifically ask
	 *                         if a REMOTE storage provider is enabled. To do this, pass true for $skip_local.
	 *                         This confusion is caused by this class name being "REMOTE" yet us saving
	 *                         LOCAL storage settings in boldgrid_backup_settings['remote']['local'].
	 *
	 * @return bool
	 */
	public function any_enabled( $skip_local = false ) {
		$settings = $this->core->settings->get_settings();

		if ( empty( $settings ) || empty( $settings['remote'] ) ) {
			return false;
		}

		foreach ( $settings['remote'] as $key => $remote ) {
			if ( $skip_local && 'local' === $key ) {
				continue;
			}

			if ( isset( $remote['enabled'] ) && true === $remote['enabled'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get enabled remote storage providers.
	 *
	 * @since 1.11.0
	 *
	 * @param  string $key If a key is passed in, only that key from the array of providers will be
	 *                     returned. For example, if we only need the title of the providers, set
	 *                     $key = 'title' and you'll only get an array of titles.
	 * @return array
	 */
	public function get_enabled( $key = '' ) {
		$enabled = [];

		// Example: https://pastebin.com/w09Kea9B
		$storage_locations = apply_filters( 'boldgrid_backup_register_storage_location', [] );

		foreach ( $storage_locations as $location ) {
			if ( ! empty( $location['enabled'] ) ) {
				$enabled[] = $location;
			}
		}

		// Filter the remote storage providers if a $key was passed in.
		if ( ! empty( $key ) ) {
			$keys = [];

			foreach ( $enabled as $storage_location ) {
				if ( ! empty( $storage_location[ $key ] ) ) {
					$keys[] = $storage_location[ $key ];
				}
			}

			$enabled = $keys;
		}

		return $enabled;
	}


	/**
	 * Return whether or not a remote storage provider is enabled.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $id A remote storage id, such as "amazon_s3".
	 * @return bool
	 */
	public function is_enabled( $id ) {
		$settings = $this->core->settings->get_settings();

		return ! empty( $settings['remote'][ $id ]['enabled'] ) && true === $settings['remote'][ $id ]['enabled'];
	}

	/**
	 * Take action after a backup has been downloaded remotely.
	 *
	 * @since 1.6.0
	 *
	 * @see Boldgrid_Backup_Admin_Archive::init()
	 * @see Boldgrid_Backup_Admin_Archive::update_timestamp()
	 *
	 * @param string $filepath A file path.
	 */
	public function post_download( $filepath ) {
		// Update the archive's timestamp based upon time last modified time in the log.
		$this->core->archive->reset();
		$this->core->archive->init( $filepath );
		$this->core->archive->update_timestamp();
	}
}
