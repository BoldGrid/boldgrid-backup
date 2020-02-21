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
					<div id="bglib-page-header">
						<h1>' . esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Total Upkeep Transfers', 'boldgrid-backup' ) ) . '</h1>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-transfers.php';
		echo '
				</div>
			</div>
		</div>';
	}
}
