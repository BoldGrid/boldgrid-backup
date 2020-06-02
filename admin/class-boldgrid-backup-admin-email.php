<?php
/**
 * File: class-boldgrid-backup-admin-email.php
 *
 * Email helper.
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Email
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Email {
	/**
	 * An array of ads.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    array
	 */
	private $ads = array();

	/**
	 * The core class object.
	 *
	 * @since  1.5.2
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Create a generic email body.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $message Message to send.
	 * @param  bool   $add_ad  Allow ads to be added to the email. In some cases,
	 *                         like when we have bad news (something failed), we
	 *                         may not want to ask the user to upgrade (bad timing).
	 * @return string
	 */
	public function fill_generic_template( $message, $add_ad = true ) {
		$this->init_ads();

		$email_body = __( 'Hello', 'boldgrid-backup' ) . ",\n\n";

		$email_body .= trim( $message ) . "\n\n";

		if ( $add_ad ) {
			$email_body .= $this->ads['generic'];
		}

		$email_body .= __( 'Best regards', 'boldgrid-backup' ) . ",\n\n";
		$email_body .= sprintf(
			// translators: 1: Plugin title.
			__( 'The %1$s plugin', 'boldgrid-backup' ) . "\n\n",
			BOLDGRID_BACKUP_TITLE
		);

		return $email_body;
	}

	/**
	 * Init our ads.
	 *
	 * @since 1.6.0
	 */
	public function init_ads() {
		$this->ads = [
			'generic' => $this->core->config->get_is_premium() ? '' : sprintf(
				// translators: 1: URL address, 2: Premium plugin title.
				__(
					'Want to store your backups on Google Drive and Amazon S3, restore individual files with just a click, and have access to more tools? Get %2$s! - %1$s',
					'boldgrid-backup'
				),
				$this->core->go_pro->get_premium_url( 'bgbkup-email' ),
				BOLDGRID_BACKUP_TITLE . ' Premium'
			) . "\n\n",
		];
	}

	/**
	 * Get the email to send after a backup has been generated.
	 *
	 * @since 1.5.2
	 *
	 * @param  array $info Archive process information.
	 * @return array
	 */
	public function post_archive_parts( $info ) {
		$this->init_ads();

		$parts = array();

		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		// translators: 1: Site identifier.
		$parts['subject'] = sprintf( __( 'Backup completed for %1$s', 'boldgrid-backup' ), $site_id );

		$parts['body']['main'] = esc_html__( 'Hello', 'boldgrid-backup' ) . ",\n\n";

		if ( $info['dryrun'] ) {
			$body['main'] .= esc_html__( 'THIS OPERATION WAS A DRY-RUN TEST', 'boldgrid-backup' ) . ".\n\n";
		}

		$parts['body']['main'] .= sprintf(
			// translators: 1: Site identifier/name.
			esc_html__( 'A backup archive has been created for %1$s', 'boldgrid-backup' ),
			$site_id
		) . ".\n\n";
		$parts['body']['main'] .= esc_html__( 'Backup details', 'boldgrid-backup' ) . ":\n";
		$parts['body']['main'] .= sprintf( $this->core->configs['lang']['est_pause'], $info['db_duration'] ) . "\n";

		// translators: 1: Backup duration.
		$parts['body']['main'] .= sprintf( esc_html__( 'Duration: %1$s seconds', 'boldgrid-backup' ), $info['duration'] ) . "\n";

		// translators: 1: Total backup size.
		$parts['body']['main'] .= sprintf( esc_html__( 'Total size: %1$s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['total_size'] ) ) . "\n";

		// translators: 1: Archive file path.
		$parts['body']['main'] .= sprintf( esc_html__( 'Archive file path: %1$s', 'boldgrid-backup' ), $info['filepath'] ) . "\n";

		// translators: 1: Archive file size.
		$parts['body']['main'] .= sprintf( esc_html__( 'Archive file size: %1$s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] ) ) . "\n";

		// translators: 1: Archive compressor name.
		$parts['body']['main'] .= sprintf( esc_html__( 'Compressor used: %1$s', 'boldgrid-backup' ), $info['compressor'] ) . "\n";

		if ( ! empty( $info['trigger'] ) ) {
			// translators: 1: What triggered the backup process.
			$parts['body']['main'] .= sprintf( esc_html__( 'Backup triggered by: %1$s', 'boldgrid-backup' ), $info['trigger'] ) . "\n";
		}

		$parts['body']['main'] .= $this->core->folder_exclusion->email_part( $info );

		$parts['body']['main'] .= $this->core->db_omit->email_part( $info );

		$parts['body']['main'] .= "\n";

		$parts['body']['signature'] = sprintf(
			// translators: 1: Plugin title.
			__(
				'You can manage notifications in your WordPress admin panel, under %1$s settings',
				'boldgrid-backup'
			),
			BOLDGRID_BACKUP_TITLE
		) . ".\n\n";

		$parts['body']['signature'] .= sprintf(
			// translators: 1: Plugin title, 2: URL address for help restoring a backup archive file.
			esc_html__( 'For help with restoring a %1$s archive file, please visit: %2$s', 'boldgrid-backup' ),
			BOLDGRID_BACKUP_TITLE,
			esc_url( $this->core->configs['urls']['restore'] )
		) . "\n\n";

		$parts['body']['signature'] .= $this->ads['generic'];

		$parts['body']['signature'] .= esc_html__( 'Best regards', 'boldgrid-backup' ) . ",\n\n";
		$parts['body']['signature'] .= sprintf(
			// translators: 1: Plugin title.
			esc_html__( 'The %1$s plugin', 'boldgrid-backup' ),
			BOLDGRID_BACKUP_TITLE
		) . "\n\n";

		return $parts;
	}

	/**
	 * Send a notification email to the admin email address.
	 *
	 * @since  1.5.2
	 * @access public
	 *
	 * @param  string $subject The email subject.
	 * @param  string $body The email body.
	 * @return bool   Whether or not the notification email was sent.
	 */
	public function send( $subject, $body ) {
		// Abort if subject or body is empty.
		if ( empty( $subject ) || empty( $body ) ) {
			return false;
		}

		// Get settings, for the notification email address.
		$settings = $this->core->settings->get_settings();

		$admin_email = $settings['notification_email'];

		// Get the site title.
		$site_title = get_bloginfo( 'name' );

		// Configure mail headers.
		$headers = 'From: ' . $site_title . ' <' . $admin_email . '>' . "\r\n" . 'X-Mailer: PHP/' .
			phpversion() . "\r\n";

		/*
		 * Send mail.
		 *
		 * The default behaviour is to include $headers in our call to wp_mail. In very rare circumstances,
		 * this will cause the following error:
		 *
		 * # Could not instantiate mail function.
		 * # phpmailer_exception_code 2
		 *
		 * In those rare cases, the user can define BGBKUP_SKIP_EMAIL_HEADERS to skip adding the headers.
		 */
		if ( defined( 'BGBKUP_SKIP_EMAIL_HEADERS' ) ) {
			$status = wp_mail( $admin_email, $subject, $body );
		} else {
			$status = wp_mail( $admin_email, $subject, $body, $headers );
		}

		// Return status.
		return $status;
	}

	/**
	 * Determine if the user wants a certain notification.
	 *
	 * @since 1.5.2
	 *
	 * @param string $task Such as 'backup' or 'restore'.
	 */
	public function user_wants_notification( $task ) {
		$settings = $this->core->settings->get_settings();

		return ! empty( $settings['notifications'][ $task ] );
	}

	/**
	 * Hook into the wp_mail_failed action.
	 *
	 * @since 1.13.4
	 *
	 * @param WP_Error $wp_error A WP error object.
	 */
	public function wp_mail_failed( $wp_error ) {
		// If in the middle of archiving files and an email failed, add info about it to the log.
		if ( $this->core->archiving_files || $this->core->restoring_archive_file ) {
			$errors = array(
				'wp_error'   => $wp_error,
				'last_error' => error_get_last(),
			);

			$this->core->logger->add( 'wp_mail_failed: ' . print_r( $errors, 1 ) ); // phpcs:ignore
		}
	}
}
