<?php
/**
 * File: class-boldgrid-backup-admin-premium-features.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.12.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Premium_Features
 *
 * @since 1.12.4
 */
class Boldgrid_Backup_Admin_Premium_Features {
	/**
	 * The core class object.
	 *
	 * @since  1.12.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.12.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.12.4
	 *
	 * @param string $hook Hook name.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( isset( $_REQUEST['page'] ) && 'boldgrid-backup-premium-features' === $_REQUEST['page'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			wp_enqueue_style(
				'boldgrid-backup-admin-premium-features',
				plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-premium.css', array(),
				BOLDGRID_BACKUP_VERSION
			);
		}
	}

	/**
	 * Get cards needed for the dashboard.
	 *
	 * @since 1.12.4
	 *
	 * @return array
	 */
	public function get_cards() {
		$cards = [
			new \Boldgrid\Backup\Admin\Card\Database_Encryption(),
			new \Boldgrid\Backup\Admin\Card\Google_Drive(),
			new \Boldgrid\Backup\Admin\Card\Amazon_S3(),
			new \Boldgrid\Backup\Admin\Card\Dream_Objects(),
			new \Boldgrid\Backup\Admin\Card\One_Click_Restoration(),
			new \Boldgrid\Backup\Admin\Card\History(),
			new \Boldgrid\Backup\Admin\Card\Historical_Versions(),
			new \Boldgrid\Backup\Admin\Card\Plugin_Editor_Tools(),
		];

		return $cards;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.12.4
	 */
	public function page() {
		$this->admin_enqueue_scripts( 'boldgrid-backup-admin-premium-features' );
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-premium.php';
	}
}