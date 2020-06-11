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
			add_thickbox();
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
		$plugin = $this->core->plugin;

		$this->page = $plugin->getPageBySlug( 'boldgrid-backup-premium-features' );
		$cards      = [
			new Card\Timely_Auto_Updates( $this->page ),
			new Card\Database_Encryption( $this->page ),
			new Card\Google_Drive( $this->page ),
			new Card\Amazon_S3( $this->page ),
			new Card\Dream_Objects( $this->page ),
			new Card\One_Click_Restoration( $this->page ),
			new Card\History( $this->page ),
			new Card\Historical_Versions( $this->page ),
			new Card\Plugin_Editor_Tools( $this->page ),
			new Card\Find_Modified_Files( $this->page ),
		];

		return $cards;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.13.0
	 *
	 * @return array returns an array of the $nav, $dashboard, and $premium_box for validation.
	 */
	public function page() {

		wp_enqueue_style( 'bglib-ui-css' );
		$settings = $this->core->settings->get_settings();
		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );

		$this->core->auto_rollback->enqueue_home_scripts();
		$this->core->auto_rollback->enqueue_backup_scripts();
		$this->core->archive_actions->enqueue_scripts();

		$this->core->folder_exclusion->enqueue_scripts();
		$this->core->db_omit->enqueue_scripts();

		$in_modal = true;
		$modal    = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
		$in_modal = false;

		$this->admin_enqueue_scripts( 'boldgrid-backup-admin-premium-features' );

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html__( 'Total Upkeep Premium Features', 'boldgrid-backup' ) . '</h1>
						<div class="page-title-actions">
						<a href="#TB_inline?width=800&amp;height=600&amp;inlineId=backup_now_content" class="thickbox page-title-action page-title-action-primary">' .
							esc_html__( 'Backup Site Now', 'boldgrid-backup' ) . '
						</a>
						<a class="page-title-action add-new">' . esc_html__( 'Upload Backup', 'boldgrid-backup' ) . '</a>
					</div>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		echo $modal; //phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-premium.php';
		echo '
				</div>
			</div>
		</div>';

		$this->page->setAllNoticesRead();
		return array(
			'nav'         => $nav,
			'dashboard'   => $dashboard,
			'premium_box' => $premium_box,
		);
	}
}
