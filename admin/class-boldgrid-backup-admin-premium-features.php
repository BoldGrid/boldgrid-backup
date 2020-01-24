<?php
/**
 * File: class-boldgrid-backup-admin-premium-features.php
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
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
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Admin_Premium_Features {

	/**
	 * The core class object.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * The page's Boldgrid\Library\Library\Plugin\Page Object.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    Boldgrid\Library\Library\Plugin\Page
	 */
	private $page;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
		$this->page = $core->plugin->getPageBySlug( 'boldgrid-backup-premium-features' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since SINCEVERSION
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
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_cards() {
		$cards = [
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
	 * @since SINCEVERSION
	 */
	public function page() {
		wp_enqueue_style( 'bglib-ui-css' );

		$this->admin_enqueue_scripts( 'boldgrid-backup-admin-premium-features' );
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-premium.php';
		$this->page->setAllNoticesRead();
	}
}
