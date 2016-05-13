<?php
/**
 * A custom RecursiveFilterIterator class
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * A custom RecursiveFilterIterator class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_RecursiveFilterIterator extends RecursiveFilterIterator {
	/**
	 * The filter array.
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	public static $filters = array(
		'.htaccess',
		'index.php',
		'license.txt',
		'readme.html',
		'readme.txt',
		'wp-activate.php',
		'wp-admin',
		'wp-blog-header.php',
		'wp-comments-post.php',
		'wp-config.php',
		'wp-content',
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
	 * The required "accept" class method.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function accept() {
		foreach ( self::$filters as $wp_path ) {
			if ( false !== strpos( $this->current()
			->getRealPath(), DIRECTORY_SEPARATOR . $wp_path ) ) {
				return true;
			}
		}

		return false;
	}
}
