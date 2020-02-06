<?php
/**
 * File: class-boldgrid-backup-admin-compressor.php
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
 * Class: Boldgrid_Backup_Admin_Compressor
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Compressor {
	/**
	 * An instance of Boldgrid_Backup_Admin_Core.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Core object
	 */
	public $core;

	/**
	 * A reference to the global wp_filesystem.
	 *
	 * @since 1.5.1
	 * @var   WP Filesystem
	 */
	public $wp_filesystem;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @global $wp_filesystem
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( $core ) {
		global $wp_filesystem;
		$this->wp_filesystem = $wp_filesystem;

		$this->core = $core;
	}

	/**
	 * Archive files.
	 *
	 * Default behaviour is to do nothing. Subclass is expected to override.
	 *
	 * @since 1.5.1
	 *
	 * @see Boldgrid_Backup_Admin_Filelist::get_total_size()
	 *
	 * @param array $filelist A file list.
	 * @param array $info {
	 *     An array of data about the backup archive we are generating.
	 *
	 *     @type string mode       backup
	 *     @type bool   dryrun
	 *     @type string compressor php_zip
	 *     @type ing    filesize   0
	 *     @type bool   save       1
	 *     @type int    total_size 0
	 * }
	 */
	public function archive_files( $filelist, &$info ) {
		return false;
	}

	/**
	 * Determine whether or not this compressor is available.
	 *
	 * @since 1.13.0
	 *
	 * @param  string $compressor A compressor.
	 * @return bool               True if is available.
	 */
	public function is_available() {
		return in_array( $this->key, $this->core->compressors->get_available(), true );
	}

	/**
	 * Determine whether or not the given compressor is the default.
	 *
	 * @since 1.13.0
	 *
	 * @param  string $compressor A compressor.
	 * @return bool               True if is default compressor.
	 */
	public function is_default() {
		return $this->key === $this->core->compressors->get_default();
	}

	/**
	 * Determine whether or not the given compressor is the one saved in the settings.
	 *
	 * @since 1.13.0
	 *
	 * @param  string $compressor A compressor.
	 * @return bool               True if compressor is saved in settings.
	 */
	public function is_saved_compressor() {
		return $this->key === $this->core->settings->get_setting( 'compressor' );
	}

	/**
	 * Whether or not this compressor should be selected if it is in an array of available compressors.
	 *
	 * Used in a <select> element.
	 *
	 * @return bool
	 */
	public function maybe_selected_compressor() {
		$setting = $this->core->settings->get_setting( 'compressor' );

		return $this->is_saved_compressor() ||
			( empty( $setting ) && $this->is_default() );
	}
}
