<?php
/**
 * Filelist class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Filelist;

/**
 * Class: Create
 *
 * @since SINCEVERSION
 */
class Create {
	/**
	 * The core class object.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of filelists.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $list = array();

	/**
	 * Construct.
	 *
	 * @since SINCEVERSION
	 */
	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', false );
	}

	/**
	 * Run our filelist creator.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function run() {
		$filelist = $this->core->get_filtered_filelist( ABSPATH );

		// Create our different file lists.
		foreach ( $filelist as $file ) {
			$type                  = $this->get_type( $file[1] );
			$this->list[ $type ][] = $file;
		}

		return $this->list;
	}

	/**
	 * Get which type of file this is.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $relative_path A path to a file that we'll backup.
	 * @return string The type, such as plugins, themes, etc.
	 */
	private function get_type( $relative_path ) {
		if ( \Boldgrid_Backup_Admin_Utility::starts_with( $relative_path, 'wp-content/plugins/' ) ) {
			return 'plugins';
		} elseif ( \Boldgrid_Backup_Admin_Utility::starts_with( $relative_path, 'wp-content/themes/' ) ) {
			return 'themes';
		} elseif ( \Boldgrid_Backup_Admin_Utility::starts_with( $relative_path, 'wp-content/uploads/' ) ) {
			return 'uploads';
		} else {
			return 'other';
		}
	}

}
