<?php
/**
 * File: class-boldgrid-backup-admin.php
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since 1.0
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
	 * Boldgrid_Backup_Admin_Config class object.
	 *
	 * @since 1.3.6
	 *
	 * @var Boldgrid_Backup_Admin_Config
	 */
	private $config;

	/**
	 * Configuration array.
	 *
	 * @since 1.3.5
	 *
	 * @var array
	 * @staticvar
	 */
	private static $configs;

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
		$this->version     = $version;

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Ensure the WP_Filesystem was initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->config = new Boldgrid_Backup_Admin_Config( null );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0
	 */
	public function enqueue_styles() {
		if ( self::is_upkeep_page() ) {
			Boldgrid\Library\Library\Ui\Page::enqueueScripts();
		}

		$core = apply_filters( 'boldgrid_backup_get_core', null );

		/*
		 * An instance of this class should be passed to the run() function
		 * defined in Boldgrid_Backup_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Boldgrid_Backup_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin.css', array(), $this->version
		);

		// Enqueue JS.
		wp_register_script(
			'boldgrid-backup-admin',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$spinner = '<span class="spinner inline"></span> ';
		$dots    = ' ...';

		$translation = array(
			'is_premium'      => ( true === $this->config->get_is_premium() ? 'true' : 'false' ),
			'lang'            => $this->config->lang,
			'spinner_loading' => $spinner . __( 'Loading', 'boldgrid-backup' ) . $dots,
			'spinner'         => $spinner,
			'get_premium_url' => Boldgrid_Backup_Admin_Go_Pro::$url,
			'is_done'         => Boldgrid_Backup_Admin_In_Progress::is_done(),
			'is_quick_fail'   => Boldgrid_Backup_Admin_In_Progress::is_quick_fail(),
		);

		wp_localize_script( 'boldgrid-backup-admin', 'BoldGridBackupAdmin', $translation );

		wp_enqueue_script( 'boldgrid-backup-admin' );

		// Enqueue "In Progress" script.
		$handle = 'boldgrid-backup-admin-in-progress';
		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/' . $handle . '.js',
			array( 'jquery', 'wp-i18n' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$translation = array(
			'archive_file_size'           => __( 'Archive file size: ', 'boldgrid-backup' ),
			'size_before_compression'     => __( 'File size before compression: ', 'boldgrid-backup' ),
			'adding_tables'               => __( 'Adding tables.', 'boldgrid-backup' ),
			'completing_database'         => __( 'Completing database backup...', 'boldgrid-backup' ),
			'update_protection_activated' => $core->elements['update_protection_activated'],
			'backup_created'              => $core->lang['backup_created'],
			'backup_error'                => '<span class="dashicons dashicons-no red"></span>' . esc_html__( 'Unfortunately there was an error creating your backup. Update protection is not available.', 'boldgrid-backup' ),
			'error'                       => esc_html__( 'Error:', 'boldgrid-backup' ),
			'get_support'                 => $core->lang['get_support'],
		);
		wp_localize_script( $handle, 'BoldGridBackupAdminInProgress', $translation );
		wp_set_script_translations( 'handle', 'boldgrid-backup' );
		wp_enqueue_script( $handle );

		// The "In Progress" script relies on the heartbeat.
		wp_enqueue_script( 'heartbeat' );

		// The "In Progress" script needs this progressbar script.
		wp_enqueue_script( 'jquery-ui-progressbar' );

		// Used by admin.js to highlight / bounce elements.
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-effects-bounce' );

		// Enqueue CSS for the home page.
		if ( isset( $_REQUEST['page'] ) && 'boldgrid-backup' === $_REQUEST['page'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			wp_enqueue_style(
				'boldgrid-backup-admin-home',
				plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
				BOLDGRID_BACKUP_VERSION
			);
		}
	}

	/**
	 * Get configuration settings.
	 *
	 * @since 1.3.5
	 *
	 * @static
	 *
	 * @return array An array of configuration settings.
	 */
	public static function get_configs() {
		// If the configuration array was already created, then return it.
		if ( ! empty( self::$configs ) ) {
			return self::$configs;
		}

		// Set the config directory.
		$config_dir = BOLDGRID_BACKUP_PATH . '/includes/config';

		// Set the config file paths.
		$global_config_path = $config_dir . '/config.plugin.php';
		$local_config_path  = $config_dir . '/config.local.php';

		// Initialize $global_configs array.
		$global_configs = array();

		// If a global config file exists, read the global configuration settings.
		if ( file_exists( $global_config_path ) ) {
			$global_configs = require $global_config_path;
		}

		// Initialize $local_configs array.
		$local_configs = array();

		// If a local configuration file exists, then read the settings.
		if ( file_exists( $local_config_path ) ) {
			$local_configs = require $local_config_path;
		}

		// If an api key hash stored in the database, then set it as the global api_key.
		$api_key_from_database = get_option( 'boldgrid_api_key' );

		if ( ! empty( $api_key_from_database ) ) {
			$global_configs['api_key'] = $api_key_from_database;
		}

		// Get the WordPress site url and set it in the global configs array.
		$global_configs['site_url'] = get_site_url();

		// Merge global and local configuration settings.
		if ( ! empty( $local_configs ) ) {
			$configs = array_merge( $global_configs, $local_configs );
		} else {
			$configs = $global_configs;
		}

		// Set the configuration array in the class property.
		self::$configs = $configs;

		// Return the configuration array.
		return $configs;
	}

	/**
	 * Whether or not the current screen is a Total Upkeep page.
	 *
	 * @since 1.14.0
	 *
	 * @return bool
	 */
	public static function is_upkeep_page() {
		$screen = get_current_screen();

		$prefixes = [
			'toplevel_page_boldgrid-backup-',
			'total-upkeep_page_',
			'admin_page_boldgrid-backup-',
		];

		foreach ( $prefixes as $prefix ) {
			if ( substr( $screen->id, 0, strlen( $prefix ) ) === $prefix ) {
				return true;
			}
		}

		return false;
	}
}
