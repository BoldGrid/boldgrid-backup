<?php
/**
 * BoldGrid Backup Admin Archive Fail.
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
 * BoldGrid Backup Admin Archive Fail Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Fail {
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
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add actions to "boldgrid_backup_archive_files_init".
	 *
	 * The "boldgrid_backup_archive_files_init" action is done as the first
	 * thing within the archive files method.
	 *
	 * @since 1.5.2
	 */
	public function archive_files_init() {
		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	/**
	 * Send an email if a backup failed during cron.
	 *
	 * @since 1.5.2
	 *
	 * @param  array $data
	 * @retrun bool True on success
	 */
	public function cron_fail_email( $data ) {
		$subject = __( 'Backup failed for', 'boldgrid-backup' ) . ' ' . get_site_url();
		return $this->core->email->send( $subject, $data['message'] );
	}

	/**
	 * Hook into shutdown.
	 *
	 * @since 1.5.2
	 */
	public function shutdown() {
		/*
		 * If an archive fails, there may be a rogue db dump sitting out there.
		 * If it exists, delete it, it should be in the archive file.
		 */
		if( $this->core->wp_filesystem->exists( $this->core->db_dump_filepath ) ) {
			$this->core->wp_filesystem->delete( $this->core->db_dump_filepath );
		}

		$last_error = error_get_last();

		/*
		 * If there's no error or this is not fatal, abort.
		 *
		 * @see http://php.net/manual/en/errorfunc.constants.php
		*/
		if( empty( $last_error ) || 1 !== $last_error['type'] ) {
			return;
		}

		$unable_to_backup = __( 'We were unable to create a backup of your website due to the following:', 'boldgrid-backup' );
		$error_text = __( 'We were unable to create a backup of your website due to the following:', 'boldgrid-backup' ) . '<br />';

		$error_message = sprintf(
			'<strong>%1$s</strong>: %2$s in %3$s on line %4$s',
			__( 'Fatal error', 'boldgrid-backup' ),
			$last_error['message'],
			$last_error['file'],
			$last_error['line']
		);

		/*
		 * If we're backing up via cron, we may have hit a memory limit or
		 * something else disastrous. It's too dangerous to send an email now,
		 * we're in an unreliable state. Instead, schedule a warning email for
		 * the future.
		 */
		if( $this->core->doing_cron ) {
			$message = $unable_to_backup . "\n\n" . strip_tags( $error_message );
			$email_body = $this->core->email->fill_generic_template( $message );

			$args = array(
				'action' => 'boldgrid_backup_cron_fail_email',
				'action_data' => array(
					'message' => $email_body,
				),
				'action_title' => __( 'Send warning email because backup failed', 'boldgrid-backup' ),
			);
			$this->core->jobs->add( $args );

			return;
		}

		$data['errorText'] = $unable_to_backup . '<br />' . $error_message;

		wp_send_json_error( $data );
	}
}
