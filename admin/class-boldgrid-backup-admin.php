<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @author BoldGrid.com <wpb@boldgrid.com>
 */
class Boldgrid_Backup_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Ensure the WP_Filesystem was initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0
	 */
	public function enqueue_styles() {
		/*
		 * An instance of this class should be passed to the run() function
		 * defined in Boldgrid_Backup_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Boldgrid_Backup_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name,
		plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin.css', array(), $this->version, 'all' );
	}
}
