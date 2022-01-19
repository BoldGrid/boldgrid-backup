<?php
/**
 * File: class-boldgrid-backup-admin-archive-fail.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archive_Fail
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
	 * A string that will hold memory for emergency purposes.
	 *
	 * @since  1.5.2
	 * @access public
	 * @var    string
	 * @see    archive_files_init
	 */
	public $memory = '';

	/**
	 * Generic lang string stating unable to backup.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    string
	 */
	public $unable_to_backup;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->unable_to_backup = __( 'We were unable to create a backup of your website due to the following:', 'boldgrid-backup' );
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

		/*
		 * Store ~0.35 MB of memory for use in an emergency.
		 *
		 * If there's a fatal error, our shutdown method will need enough
		 * memory to complete a few tasks. 0.25 MB seems sufficient, but we'll
		 * save 0.35 to be on the safe side.
		 *
		 * Tests have shown the following memory limits are sufficient enough
		 * for executing our shutdown function:
		 * # YES   1 MB
		 * # YES  .5 MB
		 * # YES .25 MB
		 * #  NO .10 MB shutdown function fails early on when calling
		 *              error_get_last().
		 */
		$mb           = 1000000;
		$this->memory = str_repeat( '0', ( 0.35 * $mb ) );
	}

	/**
	 * Send an email if a backup failed during cron.
	 *
	 * @since 1.5.2
	 *
	 * @param  array $data Array of data, containing a message to send via email.
	 * @return bool
	 */
	public function cron_fail_email( $data ) {
		$subject = __( 'Backup failed for', 'boldgrid-backup' ) . ' ' . get_site_url();
		return $this->core->email->send( $subject, $data['message'] );
	}

	/**
	 * Create a "backup failed" email and schedule it to be sent via jobs.
	 *
	 * @since 1.6.0
	 *
	 * @param string $message Error message.
	 */
	public function schedule_fail_email( $message ) {
		$message = sprintf(
			$this->unable_to_backup . "\n\n%1\$s",
			strip_tags( $message )
		);

		$email_body = $this->core->email->fill_generic_template( $message, false );

		$args = array(
			'action'       => 'boldgrid_backup_cron_fail_email',
			'action_data'  => array(
				'message' => $email_body,
			),
			'action_title' => __( 'Send warning email because backup failed', 'boldgrid-backup' ),
		);

		$this->core->jobs->add( $args );
	}

	/**
	 * Hook into shutdown.
	 *
	 * @since 1.5.2
	 */
	public function shutdown() {
		// Free up memory so we have enough to complete this method.
		$this->memory = null;

		// Tell the system we're no longer backing up.
		$this->core->in_progress->end();

		$last_error = error_get_last();

		/*
		 * If there's no error or this is not fatal, abort.
		 *
		 * @see http://php.net/manual/en/errorfunc.constants.php
		*/
		if ( empty( $last_error ) || 1 !== $last_error['type'] ) {
			return;
		}

		$error_message = sprintf(
			'<strong>%1$s</strong>: %2$s in %3$s on line %4$s',
			__( 'Fatal error', 'boldgrid-backup' ),
			$last_error['message'],
			$last_error['file'],
			$last_error['line']
		);

		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'shutdown_fatal_error', $error_message );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'success', false );

		Boldgrid_Backup_Admin_Archiver_Cancel::delete_files();

		$this->schedule_fail_email( $error_message );

		if ( ! $this->core->doing_cron ) {
			$data['errorText'] = $this->unable_to_backup . '<br />' . $error_message;
			wp_send_json_error( $data );
		}
	}
}
