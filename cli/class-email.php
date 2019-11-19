<?php
/**
 * File: class-email.php
 *
 * Email functions
 *
 * @link       https://www.boldgrid.com
 * @since      1.10.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cli
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.WP.AlternativeFunctions
 */

namespace Boldgrid\Backup\Cli;

/**
 * Class: Email
 *
 * @since 1.10.0
 */
class Email {
	/**
	 * Recipient email addresss.
	 *
	 * @since 1.10.0
	 * @access private
	 *
	 * @var string
	 */
	private $recipient;

	/**
	 * Constructor.
	 *
	 * @since 1.10.0
	 *
	 * @link https://www.php.net/manual/en/function.mail.php
	 * @link http://www.faqs.org/rfcs/rfc2822
	 *
	 * @param string $recipient Recipient email address (with or without name).
	 */
	public function __construct( $recipient ) {
		$this->recipient = trim( $recipient );
	}

	/**
	 * Send a notification email.
	 *
	 * @since 1.10.0
	 *
	 * @param  string $subject   Message subject.
	 * @param  string $message   Message body.
	 * @return bool
	 */
	public function send( $subject, $message ) {
		$headers = 'From: ' . $this->recipient . "\r\nX-Mailer: PHP/" . phpversion() . "\r\n";

		$message = "Hello,\r\n\r\n" . trim( $message ) . "\r\n\r\n" .
			"Best regards,\r\n\r\nThe Total Upkeep plugin\r\n";

		return mail( $this->recipient, $subject, $message, $headers );
	}
}
