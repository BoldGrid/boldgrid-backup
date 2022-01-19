<?php
/**
 * File: class-boldgrid-backup-admin-archiver-cancel.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.14.13
 *
 * @package    Boldgrid_Backup
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archiver_Cancel
 *
 * @since 1.14.13
 */
class Boldgrid_Backup_Admin_Archiver_Cancel {
	/**
	 * Add cancelation data to the in progress data.
	 *
	 * @since 1.14.13
	 */
	public static function add_progress_data() {
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'process_error', __( 'This backup has been successfully canceled by the user.', 'boldgrid-backup' ) );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'success', false );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'is_user_killed', true );
	}

	/**
	 * Whether or not we can cancel a backup.
	 *
	 * @since 1.14.13
	 */
	public static function can_cancel() {
		$pid = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'pid' );

		if ( empty( $pid ) ) {
			return false;
		}

		/*
		 * Signal 0 does not actually get sent, but kill will check to see if it is possible to send
		 * the signal.
		 */
		return posix_kill( $pid, 0 );
	}

	/**
	 * Cancel a backup.
	 *
	 * @since 1.14.13
	 *
	 * @return bool
	 */
	public static function cancel() {
		$pid = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'pid' );
		if ( empty( $pid ) ) {
			return false;
		}

		if ( ! self::can_cancel() ) {
			return false;
		}

		/*
		 * Kill signals may not always be defined. When defined, the numbers don't always match from
		 * machine to machine. 10 is our best guess for SIGUSR1 if it is not defined.
		 */
		if ( ! defined( 'SIGUSR1' ) ) {
			define( 'SIGUSR1', 10 );
		}

		return posix_kill( $pid, SIGUSR1 );
	}

	/**
	 * Delete rogue files.
	 *
	 * If an archive fails or is canceled by the user, handle cleanup of unecessary files. IE, there
	 * may be a rogue db dump sitting out there.
	 *
	 * @since 1.14.13
	 */
	public static function delete_files() {
		$core = apply_filters( 'boldgrid_backup_get_core', false );

		$delete_files = array(
			$core->db_dump_filepath,
			$core->archive_filepath,
		);

		foreach ( $delete_files as $file ) {
			if ( $core->wp_filesystem->exists( $file ) ) {
				$core->wp_filesystem->delete( $file );
			}
		}
	}

	/**
	 * Get our cancel button.
	 *
	 * @since 1.14.13
	 *
	 * @return string
	 */
	public static function get_button() {
		$button = '<a href="#" id="bgbkup_cancel_backup">' . esc_html__( 'Cancel backup', 'boldgrid-backup' ) . '</a>';

		$button .= wp_nonce_field( 'bgbkup_cancel_backup', 'bgbkup-cancel', true, false );

		return $button;
	}

	/**
	 * Determine whether or not we have a valid nonce.
	 *
	 * @since 1.14.13
	 *
	 * @return bool True on success.
	 */
	public static function is_valid_nonce() {
		return false !== wp_verify_nonce( $_REQUEST['cancel_auth'], 'bgbkup_cancel_backup' );
	}

	/**
	 * Listener for ajax requests to cancel a backup.
	 *
	 * @since 1.14.13
	 */
	public static function wp_ajax_cancel() {
		if ( ! self::is_valid_nonce() ) {
			wp_send_json_error( __( 'Invalid nonce', 'boldgrid-backup' ) );
		}

		$log = Boldgrid_Backup_Admin_In_Progress::get_log();

		if ( self::cancel() ) {
			self::add_progress_data();

			$log->add( 'Backup canceled successfully.' );

			wp_send_json_success( 'done' );
		} else {
			$log->add( 'Failed to cancel backup.' );

			wp_send_json_error( 'fail' );
		}
	}
}
