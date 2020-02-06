<?php
/**
 * File: class-boldgrid-backup-admin-compressors.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * File: Boldgrid_Backup_Admin_Compressors
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Compressors {
	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * The default compressor.
	 *
	 * WordPress ships out of the box with pcl_zip.
	 *
	 * In the contructor, if php_zip is available, it will be set as the default.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    string
	 */
	public $default = 'pcl_zip';

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		/*
		 * If ZipArchive is available, make it the default. Tests show it is
		 * superior to PclZip.
		 */
		if ( class_exists( 'Boldgrid_Backup_Admin_Compressor_Php_Zip' ) && Boldgrid_Backup_Admin_Compressor_Php_Zip::is_extension_available() ) {
			$this->default = 'php_zip';
		}
	}

	/**
	 * Get the compressor type we will use, such as 'php_zip'.
	 *
	 * @since 1.5.1
	 *
	 * @return string
	 */
	public function get() {
		$settings              = $this->core->settings->get_settings();
		$available_compressors = $this->get_available();

		/*
		 * If we have a compressor saved in our settings and it is an
		 * available compressor, then use it.
		 */
		if ( ! empty( $settings['compressor'] ) && in_array( $settings['compressor'], $available_compressors, true ) ) {
			return $settings['compressor'];
		}

		// Otherwise, return the default.
		return $this->default;
	}

	/**
	 * Get all available compressors.
	 *
	 * @since 1.5.1
	 *
	 * @return array
	 */
	public function get_available() {
		return $this->core->config->get_available_compressors();
	}

	/**
	 * Get the default compressor.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_default() {
		return $this->default;
	}

	/**
	 * Get our compressor object.
	 *
	 * @since 1.13.0
	 *
	 * @param  string $compressor The id of a compressor to get.
	 * @return mixed
	 */
	public function get_object( $compressor ) {
		switch ( $compressor ) {
			case 'pcl_zip':
				return new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );
			case 'php_zip':
				return new Boldgrid_Backup_Admin_Compressor_Php_Zip( $this->core );
			case 'system_zip':
				return new Boldgrid_Backup_Admin_Compressor_System_Zip( $this->core );
		}
	}

	/**
	 * Set php_zip (ZipArchive) as our compressor/extractor.
	 *
	 * @since 1.5.2
	 *
	 * @return bool True on success.
	 */
	public function set_php_zip() {
		if ( Boldgrid_Backup_Admin_Compressor_Php_Zip::is_extension_available() ) {
			$settings               = $this->core->settings->get_settings();
			$settings['compressor'] = 'php_zip';
			$settings['extractor']  = 'php_zip';
			return $this->core->settings->save( $settings );
		}

		return false;
	}

	/**
	 * Hook into WordPress' filter: unzip_file_use_ziparchive
	 *
	 * @since 1.5.1
	 *
	 * @return bool
	 */
	public function unzip_file_use_ziparchive() {
		$settings = $this->core->settings->get_settings();

		/*
		 * By default WordPress is set to use ZipArchive by default. Only use
		 * PclZip if we explicitly set it.
		 */
		if ( ! empty( $settings['extractor'] ) && 'pcl_zip' === $settings['extractor'] ) {
			return false;
		}

		return true;
	}
}
