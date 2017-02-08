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
		$this->version = $version;

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Ensure the WP_Filesystem was initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->config = new Boldgrid_Backup_Admin_Config( null );

		$this->prepare_plugin_update();
	}

	/**
	 * Prepare the plugin update class.
	 *
	 * @since 1.3.6
	 *
	 * @see self::wpcron()
	 * @see self::load_update()
	 */
	public function prepare_plugin_update() {
		$is_cron = ( defined( 'DOING_CRON' ) && DOING_CRON );
		$is_wpcli = ( defined( 'WP_CLI' ) && WP_CLI );

		if ( $is_cron || $is_wpcli || is_admin() ) {
			require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-update.php';

			$plugin_update = new Boldgrid_Backup_Update( self::get_configs() );

			add_action( 'init', array (
				$plugin_update,
				'add_hooks'
			) );
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
		plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin.css', array(), $this->version );

		// Enqueue JS.
		wp_register_script( 'boldgrid-backup-admin',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$translation = array(
			'is_premium' => ( true === $this->config->get_is_premium() ? 'true' : 'false' ),
			'max_dow' => $this->config->get_max_dow(),
			'lang' => $this->config->lang,
		);

		wp_localize_script( 'boldgrid-backup-admin', 'BoldGridBackupAdmin', $translation );

		wp_enqueue_script( 'boldgrid-backup-admin' );

		// Enqueue CSS for the home page.
		if ( isset( $_REQUEST['page'] ) && 'boldgrid-backup' === $_REQUEST['page'] ) {
			wp_enqueue_style( 'boldgrid-backup-admin-home',
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
		$local_config_path = $config_dir . '/config.local.php';

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
}
