<?php
/**
 * Compressors.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Compressors class.
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
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get the compressor type we will use, such as 'php_zip'.
	 *
	 * @since 1.5.1
	 *
	 * @return string
	 */
	public function get() {
		$available_compressors = $this->core->config->get_available_compressors();

		foreach ( $available_compressors as $available_compressor ) {
			return $available_compressor;
		}
	}
}