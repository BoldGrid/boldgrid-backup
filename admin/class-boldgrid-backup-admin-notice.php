<?php
/**
 * The admin-specific notice methods for the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.2
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin notice class.
 *
 * @since 1.2
 */
class Boldgrid_Backup_Admin_Notice {

	/**
	 * Add a notice for a user.
	 *
	 * @since 1.5.4
	 *
	 * @param string $message
	 * @param string $class
	 */
	public function add_user_notice( $message, $class ) {
		$option = $this->get_user_option();

		$notices = get_option( $option, array() );

		$notices[] = array(
			'message' => $message,
			'class' => $class,
		);

		update_option( $option, $notices );
	}

	/**
	 * An array of messages we've already printed to the current page.
	 *
	 * @since 1.5.1
	 * @var   array
	 */
	public $displayed_messages = array();

	/**
	 * Display notices for user.
	 *
	 * @since 1.5.4
	 */
	public function display_user_notice() {
		$option = $this->get_user_option();

		$notices = get_option( $option, array() );

		if( empty( $notices ) ) {
			return;
		}

		foreach( $notices as $notice ) {
			printf( '<div class="%1$s is-dismissible">%2$s</div>', $notice['class'], $notice['message'] );
		}

		delete_option( $option );
	}

	/**
	 * Print an admin notice.
	 *
	 * @since 1.2
	 *
	 * @param string $message A message to display in the admin notice.
	 * @param string $class The class string for the div.
	 */
	public function boldgrid_backup_notice( $message, $class = 'notice notice-error is-dismissible' ) {
		if( in_array( $message, $this->displayed_messages, true ) ) {
			return;
		}

		// Determine if our message is already in a container (either div or p).
		$in_container = false !== strpos( '<p', $message ) || false !== strpos( '<div', $message );

		printf(
			'<div class="%1$s">%2$s</div>',
			$class,
			$in_container ? $message : '<p>' . $message . '</p>'
		);

		$this->displayed_messages[] = $message;
	}

	/**
	 * Queue a notice for a failed functionality test.
	 *
	 * @since 1.2.3
	 *
	 * @param bool $use_link Link to the Functionality Tests page.  Default is TRUE.
	 */
	public function functionality_fail_notice( $use_link = true ) {
		if ( $use_link ) {
			$message = sprintf(
				esc_html__(
					'Functionality test has failed.  You can go to %1$sFunctionality Test%2$s to view a report.',
					'boldgrid-backup'
				),
				'<a href="' . admin_url( 'admin.php?page=boldgrid-backup-test' ) . '">',
				'</a>'
			);
		} else {
			$message = esc_html__( 'Functionality test has failed.', 'boldgrid-backup' );
		}

		do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );
	}

	/**
	 * Get user_notices option name for current user.
	 *
	 * @since 1.5.4
	 */
	public function get_user_option() {
		$user_id = get_current_user_id();
		return 'boldgrid_backup_user_notices_' . $user_id;
	}
}
