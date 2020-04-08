<?php
/**
 * File: class-boldgrid-backup-admin-transfers.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Transfers
 *
 * @since 1.11.0
 */
class Boldgrid_Backup_Admin_Transfers {
	/**
	 * The core class object.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.11.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Render the transfers page.
	 *
	 * @since 1.11.0
	 */
	public function page() {
		wp_enqueue_style( 'bglib-ui-css' );
		wp_enqueue_script( 'bglib-ui-js' );
		wp_enqueue_script( 'bglib-sticky' );

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

		wp_enqueue_style(
			'boldgrid-backup-admin-transfers',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css',
			[],
			BOLDGRID_BACKUP_VERSION
		);

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Total Upkeep Transfers', 'boldgrid-backup' ) ) . '</h1>
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
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-transfers.php';
		echo '
				</div>
			</div>
		</div>';
	}
}
