<?php
/**
 * File: class-boldgrid-backup-admin-site-health.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.10.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Site_Health
 *
 * @since 1.10.0
 */
class Boldgrid_Backup_Admin_Site_Health {
	/**
	 * The core class object.
	 *
	 * @since  1.10.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.10.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.10.0
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {
		$handle = 'boldgrid-backup-admin-site-health';

		wp_register_style(
			$handle,
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-site-health.css',
			array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		switch ( $hook ) {
			case 'site-health.php':
				wp_enqueue_style( $handle );
				break;
		}
	}

	/**
	 * Hook into site_status_tests and add our test.
	 *
	 * @since 1.10.0
	 *
	 * @param array $tests An array of tests.
	 * @return array
	 */
	public function site_status_tests( $tests ) {
		$tests['direct']['backup_plugin'] = array(
			'label' => __( 'BoldGrid Backup Test' ),
			'test'  => array( $this, 'add_test' ),
		);

		return $tests;
	}

	/**
	 * Add our test.
	 *
	 * @since 1.10.0
	 */
	public function add_test() {
		$result = array(
			'label'       => __( 'Backups enabled with Full Site Protection', 'boldgrid-backup' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Backups', 'boldgrid-backup' ),
				'color' => 'blue',
			),
			'description' => include_once BOLDGRID_BACKUP_PATH . '/admin/partials/tools/full-protection.php',
			'actions'     => '',
			'test'        => 'backup_plugin',
		);

		if ( ! $this->core->settings->has_full_protection() ) {
			$result['status'] = 'recommended';

			$result['label'] = __( 'Site backups not properly configured', 'boldgrid-backup' );
		}

		return $result;
	}
}
