<?php
/**
 * Boldgrid Backup Admin Remote.
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
 * Boldgrid Backup Admin Remote class.
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
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Determine if any storage locations are enabled.
	 *
	 * @since 1.5.4
	 *
	 * @return bool
	 */
	public function any_enabled() {
		$settings = $this->core->settings->get_settings();

		if( empty( $settings ) || empty( $settings['remote'] ) ) {
			return false;
		}

		foreach( $settings['remote'] as $remote ) {
			if( isset( $remote['enabled'] ) && true === $remote['enabled'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return whether or not a remote storage provider is enabled.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $id amazon_s3
	 * @return bool
	 */
	public function is_enabled( $id ) {
		$settings = $this->core->settings->get_settings();

		return ! empty( $settings['remote'][$id]['enabled'] ) && true === $settings['remote'][$id]['enabled'] ;
	}

	/**
	 * Take action after a backup has been downloaded remotely.
	 *
	 * @since 1.5.4
	 */
	public function post_download( $filepath ) {

		/*
		 * Restore the log file from the archive so we can get all the juicy
		 * meta data about the archive.
		 */
		$this->core->archive_log->restore_by_zip( $filepath );

		/*
		 * Now that we have the log, update the archive's timestamp based upon
		 * time last modified time in the log.
		 */
		$this->core->archive->reset();
		$this->core->archive->init( $filepath );
		$this->core->archive->update_timestamp();
	}
}
