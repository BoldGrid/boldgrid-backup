<?php
/**
 * File: class-boldgrid-backup-admin-filelist.php
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
 * Class: Boldgrid_Backup_Admin_Filelist
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Filelist {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * The filelist filter array.
	 *
	 * This existed in the core class as of 1.0, moved to this class as of 1.5.4
	 *
	 * This array primarily exists to help get the total size of your website.
	 * We loop through this list and calculate the disk space of each item.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    array
	 */
	public $filelist_filter = array(
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
	 * Get the total size of WordPress core and the wp-content directory.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public function get_size() {
		/*
		 * Include wp-includes/ms-functions.php.
		 *
		 * This method uses WordPress' recurse_dirsize function, which is loaded on multisite
		 * installations. If the recurse_dirsize function does not exist, include the necessary
		 * file.
		 */
		if ( ! function_exists( 'recurse_dirsize' ) ) {
			require_once ABSPATH . 'wp-includes/ms-functions.php';
		}

		$size = 0;

		// Delete transient "dirsize_cache" that WordPress sets when using recurse_dirsize(); used for Site Health info, our REST endpoint, and here.
		delete_transient( 'dirsize_cache' );

		foreach ( $this->filelist_filter as $file ) {
			$file_path = ABSPATH . $file;

			if ( is_dir( $file_path ) ) {
				$this_size = recurse_dirsize( $file_path );
			} else {
				$this_size = $this->core->wp_filesystem->size( $file_path );
			}

			$size += $this_size;
		}

		return $size;
	}

	/**
	 * Get the total size of a $filelist.
	 *
	 * @since 1.5.1
	 *
	 * @param array $filelist {
	 *     An array files and data about those files.
	 *
	 *     @type string 0 The path of a file.   /home/user/public_html/readme.html
	 *     @type string 1 The filename.         readme.html
	 *     @type int    2 The size of the file. 7413
	 * }
	 * @return int
	 */
	public function get_total_size( $filelist ) {
		$total_size = 0;

		foreach ( $filelist as $fileinfo ) {
			$total_size += $fileinfo[2];
		}

		return $total_size;
	}
}
