<?php
/**
 * File: class-boldgrid-backup-admin-core-files.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Core_Files
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Core_Files {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of core files within WordPress.
	 *
	 * Exceptions:
	 * # The wp-content folder is not included.
	 * # .htaccess.bgb is included, but it is not a core file.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    array
	 */
	public $files = array(
		'.htaccess',
		'.htaccess.bgb',
		'index.php',
		'license.txt',
		'readme.html',
		'wp-activate.php',
		'wp-admin',
		'wp-blog-header.php',
		'wp-comments-post.php',
		'wp-config.php',
		'wp-cron.php',
		'wp-includes',
		'wp-links-opml.php',
		'wp-load.php',
		'wp-login.php',
		'wp-mail.php',
		'wp-settings.php',
		'wp-signup.php',
		'wp-trackback.php',
		'xmlrpc.php',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Determine if a given $file (relative to ABSPATH) is a core file.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $file File path.
	 * @return bool
	 */
	public function is_core_file( $file ) {
		if ( ! is_string( $file ) || empty( $file ) ) {
			return false;
		}

		foreach ( $this->files as $core_file ) {
			if ( 0 === strpos( $file, $core_file ) ) {
				return true;
			}
		}

		return false;
	}
}
