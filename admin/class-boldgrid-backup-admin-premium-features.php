<?php
/**
 * File: class-boldgrid-backup-admin-premium-features.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.13.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

use Boldgrid\Backup\Admin\Card;

/**
 * Class: Boldgrid_Backup_Admin_Premium_Features
 *
 * @since 1.13.0
 */
class Boldgrid_Backup_Admin_Premium_Features {

	/**
	 * The core class object.
	 *
	 * @since  1.13.0
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * The page's Boldgrid\Library\Library\Plugin\Page Object.
	 *
	 * @since  1.13.0
	 * @access private
	 * @var    Boldgrid\Library\Library\Plugin\Page
	 */
	private $page;

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.13.0
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
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function get_cards() {
		if ( $this->core->plugin ) {
			$plugin = $this->core->plugin;
		} else {
			$plugin = new \Boldgrid\Library\Library\Plugin\Plugin( 'boldgrid-backup', $this->core->configs );
		}
		$this->page = $plugin->getPageBySlug( 'boldgrid-backup-premium-features' );
		$cards      = [
			new Card\Database_Encryption( $this->page ),
			new Card\Google_Drive( $this->page ),
			new Card\Amazon_S3( $this->page ),
			new Card\Dream_Objects( $this->page ),
			new Card\One_Click_Restoration( $this->page ),
			new Card\History( $this->page ),
			new Card\Historical_Versions( $this->page ),
			new Card\Plugin_Editor_Tools( $this->page ),
		];

		return $cards;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.13.0
	 */
	public function page() {
		wp_enqueue_style( 'bglib-ui-css' );

		$this->admin_enqueue_scripts( 'boldgrid-backup-admin-premium-features' );
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-premium.php';
		$this->page->setAllNoticesRead();
		return array(
			'nav'         => $nav,
			'dashboard'   => $dashboard,
			'premium_box' => $premium_box,
		);
	}
}
