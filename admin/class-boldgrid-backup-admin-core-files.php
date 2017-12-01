<?php
/**
 * Core files.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Core Files Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Core_Files {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
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
	 * @since  1.5.4
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
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Determine if a given $file (relative to ABSPATH) is a core file.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $file
	 * @return bool
	 */
	public function in( $file ) {
		if( ! is_string( $file ) || empty( $file ) ) {
			return false;
		}

		foreach( $this->files as $core_file ) {
			if( 0 === strpos( $file, $core_file ) ) {
				return true;
			}
		}

		return false;
	}

}
