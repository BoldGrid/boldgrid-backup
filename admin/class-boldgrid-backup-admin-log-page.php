<?php
/**
 * File: class-boldgrid-backup-admin-log-page.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.12.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Log_page
 *
 * @since 1.12.5
 */
class Boldgrid_Backup_Admin_Log_Page {
	/**
	 * Admin enqueue scripts.
	 *
	 * @since 1.12.5
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'total-upkeep_page_boldgrid-backup-tools' === $hook ) {
			$handle = 'boldgrid-backup-admin-logs';

			wp_register_script(
				$handle,
				plugin_dir_url( __FILE__ ) . 'js/' . $handle . '.js',
				array( 'jquery' ),
				BOLDGRID_BACKUP_VERSION,
				false
			);

			$translation = array(
				'loading'      => esc_html__( 'Loading log file', 'boldgrid-backup' ),
				'unknownError' => esc_html__( 'An unknown error occurred. Please close this modal and try again.', 'boldgrid-backup' ),
			);

			wp_localize_script( $handle, 'BoldGridBackupAdminLogs', $translation );

			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Render the page for a specific log.
	 *
	 * @since 1.12.5
	 */
	public function wp_ajax_boldgrid_backup_view_log() {
		// Validate permissions.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$markup = '<div class="notice notice-error"><p>' . esc_html__( 'Permission denied.', 'boldgrid-backup' ) . '</p></div>';
			wp_send_json_error( $markup );
		}

		// Validate nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'boldgrid_backup_view_log' ) ) {
			$markup = '<div class="notice notice-error"><p>' . esc_html__( 'Invalid nonce. Please refresh the page and try again.', 'boldgrid-backup' ) . '</p></div>';
			wp_send_json_error( $markup );
		}

		$core = apply_filters( 'boldgrid_backup_get_core', null );

		// Make sure a filename was passsed.
		$filename = ! empty( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : null; // phpcs:ignore
		if ( empty( $filename ) ) {
			$markup = '<div class="notice notice-error"><p>' . esc_html__( 'Error: No log filename provided.', 'boldgrid-backup' ) . '</p></div>';
			wp_send_json_error( $markup );
		}

		// Make sure the log file exists.
		$filepath = $core->backup_dir->get_logs_dir() . DIRECTORY_SEPARATOR . $filename;
		if ( ! $core->wp_filesystem->exists( $filepath ) ) {
			$markup = '<div class="notice notice-error"><p>' . esc_html__( 'Error: Log file does not exist.', 'boldgrid-backup' ) . '</p></div>';
			wp_send_json_error( $markup );
		}

		// Make sure we don't have an empty file.
		$contents = $core->wp_filesystem->get_contents( $filepath );
		if ( empty( $contents ) ) {
			$markup = '<div class="notice notice-warning"><p>' . esc_html__( 'Log file is empty.', 'boldgrid-backup' ) . '</p></div>';
			wp_send_json_error( $markup );
		}

		// Good to go.
		$markup = '<div style="overflow:auto;white-space:pre-wrap;font-family:\'Courier New\';">' . esc_html( $contents ) . '</div>';
		wp_send_json_success( $markup );
	}
}
