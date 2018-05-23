<?php
/**
 * Email.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * Email.
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Email {

	/**
	 * An array of ads.
	 *
	 * @since  1.5.4
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
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Create a generic email body.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $message
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
		$email_body .= __( 'The BoldGrid Backup plugin', 'boldgrid-backup' ) . "\n\n";

		return $email_body;
	}

	/**
	 * Init our ads.
	 *
	 * @since 1.5.4
	 */
	public function init_ads() {
		$this->ads = array(
			'generic' => $this->core->config->get_is_premium() ? '' : sprintf(
				__( 'Want to store your backups on Amazon S3, restore individual files with just a click, and have access to more tools? Get BoldGrid Backup Premium! - %1$s', 'boldgrid-backup' ),
				Boldgrid_Backup_Admin_Go_Pro::$url
			) . "\n\n",
		);
	}

	/**
	 * Get the email to send after a backup has been generated.
	 *
	 * @since 1.5.2
	 *
	 * @param  array $info
	 * @return array
	 */
	public function post_archive_parts( $info ) {
		$this->init_ads();

		$parts = array();

		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		$parts['subject'] = sprintf( __( 'Backup completed for %s', 'boldgrid-backup' ), $site_id );

		$parts['body']['main'] = esc_html__( 'Hello', 'boldgrid-backup' ) . ",\n\n";
		if ( $info['dryrun'] ) {
			$body['main'] .= esc_html__( 'THIS OPERATION WAS A DRY-RUN TEST', 'boldgrid-backup' ) . ".\n\n";
		}
		$parts['body']['main'] .= sprintf( esc_html__( 'A backup archive has been created for %s', 'boldgrid-backup' ), $site_id ) . ".\n\n";
		$parts['body']['main'] .= esc_html__( 'Backup details', 'boldgrid-backup' ) . ":\n";
		$parts['body']['main'] .= sprintf( $this->core->configs['lang']['est_pause'], $info['db_duration'] ) . "\n";
		$parts['body']['main'] .= sprintf( esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ), $info['duration'] ) . "\n";
		$parts['body']['main'] .= sprintf( esc_html__( 'Total size: %s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['total_size'] ) ) . "\n";
		$parts['body']['main'] .= sprintf( esc_html__( 'Archive file path: %s', 'boldgrid-backup' ), $info['filepath'] ) . "\n";
		$parts['body']['main'] .= sprintf( esc_html__( 'Archive file size: %s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] ) ) . "\n";
		$parts['body']['main'] .= sprintf( esc_html__( 'Compressor used: %s', 'boldgrid-backup' ), $info['compressor'] ) . "\n";

		if ( ! empty( $info['trigger'] ) ) {
			$parts['body']['main'] .= sprintf( esc_html__( 'Backup triggered by: %1$s', 'boldgrid-backup' ), $info['trigger'] ) . "\n";
		}

		$parts['body']['main'] .= $this->core->folder_exclusion->email_part( $info );

		$parts['body']['main'] .= $this->core->db_omit->email_part( $info );

		$parts['body']['main'] .= "\n";

		$parts['body']['signature'] = esc_html__( 'You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings', 'boldgrid-backup' ) . ".\n\n";
		$parts['body']['signature'] .= sprintf( esc_html__( 'For help with restoring a BoldGrid Backup archive file, please visit: %s', 'boldgrid-backup' ), esc_url( $this->core->configs['urls']['restore'] ) ) . "\n\n";

		$parts['body']['signature'] .= $this->ads['generic'];

		$parts['body']['signature'] .= esc_html__( 'Best regards', 'boldgrid-backup' ) . ",\n\n";
		$parts['body']['signature'] .= esc_html__( 'The BoldGrid Backup plugin', 'boldgrid-backup' ) . "\n\n";

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

		// Send mail.
		$status = wp_mail( $admin_email, $subject, $body, $headers );

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
}
