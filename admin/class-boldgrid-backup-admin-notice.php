<?php
/**
 * File: class-boldgrid-backup-admin-notice.php
 *
 * @link       http://www.boldgrid.com
 * @since      1.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

use \Boldgrid\Library\Library\Notice;

/**
 * Class: Boldgrid_Backup_Admin_Notice
 *
 * @since 1.2
 */
class Boldgrid_Backup_Admin_Notice {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Common strings used in notices.
	 *
	 * @since 1.6.0
	 * @var   array
	 */
	public $lang = [
		'dis_error'   => 'notice notice-error is-dismissible',
		'dis_success' => 'notice notice-success is-dismissible',
	];

	/**
	 * An array of messages we've already printed to the current page.
	 *
	 * @since 1.5.1
	 * @var   array
	 */
	public $displayed_messages = [];

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add a notice for a user.
	 *
	 * @since 1.6.0
	 *
	 * @param string $message Message.
	 * @param string $class   Class.
	 * @param string $heading Heading.
	 */
	public function add_user_notice( $message, $class, $heading = null ) {
		$option = $this->get_user_option();

		$notices = get_option( $option, [] );

		$message = $this->add_container( $message );

		$notices[] = [
			'message' => $message,
			'class'   => $class,
			'heading' => $heading,
		];

		update_option( $option, $notices );
	}

	/**
	 * Display notices for user.
	 *
	 * @since 1.6.0
	 */
	public function display_user_notice() {
		$option = $this->get_user_option();

		$notices = get_option( $option, [] );

		// Only admins should see "backup in progress" notices.
		if ( Boldgrid_Backup_Admin_Utility::is_user_admin() ) {
			$notices = $this->core->in_progress->add_notice( $notices );
		}

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			printf(
				'
				<div class="%1$s is-dismissible">
					%3$s
					%2$s
				</div>',
				/* 1 */ $notice['class'],
				/* 2 */ $this->add_container( $notice['message'] ),
				/* 3 */ ! empty( $notice['heading'] ) ?
					sprintf( '<h2 class="header-notice">%1$s</h2>', $notice['heading'] ) : '' // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			);
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
		if ( in_array( $message, $this->displayed_messages, true ) ) {
			return;
		}

		echo $this->get_notice_markup( $class, $message ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		$this->displayed_messages[] = $message;
	}

	/**
	 * Get our "backup complete!" admin notice.
	 *
	 * @since 1.6.0
	 *
	 * @return mixed False on failure, an array on success. Prior to @1.14.13, we returned a string
	 *               containing the markup of the admin notice.
	 */
	public function get_backup_complete() {
		// Assume that this "backup complete!" notice is for the last backup made.
		$archive_info = get_option( 'boldgrid_backup_latest_backup' );
		if ( empty( $archive_info ) ) {
			return false;
		}

		$message = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup.php';

		return $message;
	}

	/**
	 * Get the entire html markup for a notice, including the .notice container.
	 *
	 * @param  string $class   Class.
	 * @param  string $message Message.
	 * @param  string $heading Heading.
	 * @return string
	 */
	public function get_notice_markup( $class, $message, $heading = null ) {
		return sprintf(
			'
				<div class="%1$s">
					%2$s
					%3$s
				</div>',
			/* 1 */ $class,
			/* 2 */ ! empty( $heading ) ? sprintf( '<h2 class="header-notice">%1$s</h2>', $heading ) : '',
			/* 3 */ $this->add_container( $message )
		);
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
				// translators: 1: HTML anchor opening tag, 2: HTML anchor closing tag.
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
	 * @since 1.6.0
	 */
	public function get_user_option() {
		$user_id = get_current_user_id();
		return 'boldgrid_backup_user_notices_' . $user_id;
	}

	/**
	 * Ensure a message is within a container and return it.
	 *
	 * If it is not within a p or div, wrap it in a p tag.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $message Message.
	 * @return string
	 */
	public function add_container( $message ) {
		$in_container = false !== strpos( $message, '<p' ) || false !== strpos( $message, '<div' );

		$message = ! $in_container ? '<p>' . $message . '</p>' : $message;

		return $message;
	}

	/**
	 * Display a notice for auto-update settings.
	 *
	 * @since 1.7.0
	 */
	public function display_autoupdate_notice() {
		$notice_id = 'bgbkup_autoupdate_notice';

		/*
		 * This notice is dismissible per user.
		 *
		 * @link https://wordpress.org/support/topic/how-do-i-remove-this-notificatio/
		 */
		if ( Notice::isDismissed( $notice_id ) ) {
			return;
		}

		$auto_update_array = [
			( apply_filters( 'allow_major_auto_core_updates', false ) ) ? 'Major' : false,
			( apply_filters( 'allow_minor_auto_core_updates', false ) ) ? 'Minor' : false,
			( apply_filters( 'allow_dev_auto_core_updates', false ) ) ? 'Development' : false,
			( apply_filters( 'auto_update_translation', false, wp_get_translation_updates() ) ) ? 'Translation' : false,
		];
		$auto_update_array = array_filter( $auto_update_array );
		$update_msg        = '';
		switch ( count( $auto_update_array ) ) {
			case 0:
				$update_msg = esc_html__( 'disabled for all', 'boldgrid-backup' );
				break;
			case 1:
				$auto_update_array = array_values( $auto_update_array );
				$update_msg        = sprintf(
					// translators: 1: Auto Update Type.
					esc_html__( 'enabled for %s', 'boldgrid-backup' ),
					$auto_update_array[0]
				);
				break;
			case 4:
				$update_msg = esc_html__( 'enabled for all', 'boldgrid-backup' );
				break;
			default:
				$x = array_slice( $auto_update_array, 0, -1 );

				$auto_update_string = implode( ', ', $x );
				$update_msg         = sprintf(
					// translators: 1: Auto Update Types, 2: Auto Update Type.
					esc_html__(
						'enabled for %1$s and %2$s',
						'boldgrid-backup'
					),
					$auto_update_string,
					end( $auto_update_array )
				);
				break;
		}

		$message = '<p>' . sprintf(
			// translators: 1: HTML anchor opening tag, 2: HTML anchor closing tag, 3: Plugin title.
			esc_html__(
				'Auto Updates are %4$s WordPress Core Updates. This can be configured in the %1$s%3$s Settings%2$s.',
				'boldgrid-backup'
			),
			'<a href="' . admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_auto_updates' ) . '">',
			'</a>',
			BOLDGRID_BACKUP_TITLE,
			$update_msg
		) . '</p>';

		Notice::show( $message, $notice_id, 'notice notice-info' );
	}

	/**
	 * Display a notice for this plugin being renamed.
	 *
	 * The notice is displayed to all admin users, dismissible per user.
	 *
	 * @since 1.12.0
	 *
	 * @see \Boldgrid\Library\Library\Notice::isDismissed()
	 * @see \Boldgrid\Library\Library\Notice::show()
	 */
	public function plugin_renamed_notice() {
		$plugin = \Boldgrid\Library\Library\Plugin\Factory::create( 'boldgrid-backup' );

		/*
		 * Only show to existing users.
		 *
		 * If the first version of this plugin is not less than 1.12.0 (the version when the rename
		 * occurred), abort.
		 */
		if ( ! $plugin->firstVersionCompare( '1.12.0', '<' ) ) {
			return;
		}

		$notice_id = 'boldgrid_backup_renamed';

		if ( ! Notice::isDismissed( $notice_id ) ) {
			$message = sprintf(
				// translators: 1: HTML anchor open tag, 2: HTML anchor close tag, 3: HTML H3 open tag, 4: HTML H3 close tag, 5: HTML p open tag, 6: HTML p close tag, 7: Plugin title.
				esc_html__(
					'%3$sBoldGrid Backup has become Total Upkeep!%4$s%5$sDifferent name with the same great features.  For more information on the change, please go to %1$sour website%2$s.%6$s',
					'boldgrid-backup'
				),
				'<a target="_blank" href="' . esc_url( $this->core->configs['urls']['plugin_renamed'] ) . '">',
				'</a>',
				'<h3>',
				'</h3>',
				'<p>',
				'</p>'
			);

			Notice::show( $message, $notice_id, 'notice notice-info' );
		}
	}
}
