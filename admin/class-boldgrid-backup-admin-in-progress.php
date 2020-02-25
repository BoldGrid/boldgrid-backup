<?php
/**
 * File: class-boldgrid-backup-admin-in-progress.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_In_Progress
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_In_Progress {
	/**
	 * The core class object.
	 *
	 * @since  1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Our tmp class.
	 *
	 * @since 1.13.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_In_Progress_Tmp
	 */
	private $tmp;

	/**
	 * A unix timestamp indicating when a backup was started.
	 *
	 * Currently this property is only used before and after a database is dumped,
	 * see $this->pre_dump() and $this->post_dump().
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    int
	 */
	protected $in_progress;

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->tmp = new Boldgrid_Backup_Admin_In_Progress_Tmp( $this->core );
	}

	/**
	 * Add a notice telling the user there's a backup in progress.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $notices Array of notices to display.
	 * @return array
	 */
	public function add_notice( $notices ) {
		$in_progress = $this->get();

		if ( empty( $in_progress ) ) {
			return $notices;
		}

		/*
		 * If we are in the middle of a backup, we'll want to increase the rate
		 * of the heartbeat so that we can more quickly update the user when
		 * the backup has completed.
		 */
		wp_enqueue_script( 'heartbeat' );

		wp_enqueue_script( 'jquery-ui-progressbar' );

		$elapsed = time() - $in_progress;
		$limit   = 15 * MINUTE_IN_SECONDS;

		$notice = $this->get_notice();
		if ( false === $notice ) {
			return $notices;
		}

		/*
		 * @todo If the backup takes longer than 15 minutes, the user needs more
		 * help with troubleshooting.
		 */
		if ( $elapsed > $limit ) {
			$notice['message'] .= __(
				' Most backups usually finish before this amount of time, so we will stop displaying this notice.',
				'boldgrid-backup'
			);

			$this->end();
		}

		$notices[] = $notice;

		return $notices;
	}

	/**
	 * Stop.
	 *
	 * Specify that we are no longer backing up a website.
	 *
	 * @since 1.6.0
	 */
	public function end() {
		$settings = $this->core->settings->get_settings( true );

		if ( ! empty( $settings['in_progress'] ) ) {
			unset( $settings['in_progress'] );
		}

		$this->core->settings->save( $settings );
	}

	/**
	 * Get the in progress value.
	 *
	 * The value is the time we started the backup.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public function get() {
		$settings = $this->core->settings->get_settings( true );

		$in_progress = ! empty( $settings['in_progress'] ) ? $settings['in_progress'] : null;

		return $in_progress;
	}

	/**
	 * Get the markup of an error message.
	 *
	 * This method is similar to self::get_notice() and self::get_notice_markup(), except those methods
	 * are for success and this one for errors.
	 *
	 * @since 1.11.2
	 *
	 * @return string
	 */
	public function get_error_markup() {
		$error = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'error' );

		$notice = [
			'class'   => 'notice notice-error boldgrid-backup-in-progress',
			'message' => '<div class="notice"><p><strong>' . __( 'Error:', 'boldgrid-backup' ) . '</strong><br />' .
				'<em>' . esc_html( $error ) . '</em></p></div>' .
				'<p>' . $this->core->lang['get_support'] . '</p>',
			'heading' => BOLDGRID_BACKUP_TITLE . ' - ' . __( 'Error creating backup', 'boldgrid-backup' ),
		];

		$markup = $this->core->notice->get_notice_markup( $notice['class'], $notice['message'], $notice['heading'] );

		return $markup;
	}

	/**
	 * Get our in progress notice.
	 *
	 * @since 1.6.0
	 *
	 * @return mixed Array on success, false when there's no backup in progress.
	 */
	public function get_notice() {
		$in_progress = $this->get();

		if ( empty( $in_progress ) ) {
			return false;
		}

		/*
		 * Create our notice for atop the page.
		 *
		 * Initially started out as "backup in progress". Has expanded to include a progress bar.
		 */
		$loading = __( 'Loading...', 'bgtfw' );
		$message = '<p>' . sprintf(
			// translators: 1: Plugin title, 2: Time since the last backup was initiated.
			__( '%1$s began archiving your website %2$s ago.', 'boldgrid-backup' ),
			BOLDGRID_BACKUP_TITLE,
			human_time_diff( $in_progress, time() )
		) . '</p>';

		$message .= Boldgrid_Backup_Admin_In_Progress_Data::get_markup( $loading );
		$notice   = [
			'class'   => 'notice notice-warning boldgrid-backup-in-progress',
			'message' => $message,
			'heading' => BOLDGRID_BACKUP_TITLE . ' - ' . __( 'Backup in progress', 'boldgrid-backup' ),
		];

		return $notice;
	}

	/**
	 * Get our notice markup.
	 *
	 * @since 1.6.0
	 *
	 * @return mixed Returns a string (html markup), a WordPress admin notice or
	 *               false if we don't have a notice.
	 */
	public function get_notice_markup() {
		$notice = $this->get_notice();
		$markup = false;

		if ( $notice ) {
			$markup = $this->core->notice->get_notice_markup( $notice['class'], $notice['message'], $notice['heading'] );
		}

		return $markup;
	}

	/**
	 * Take action when the heartbeat is received.
	 *
	 * Include data in the heartbeat to let the user know if their backup is
	 * still in progress, or it has finished.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $response Response.
	 * @param  array $data     Data in heartbeat.
	 * @return array
	 */
	public function heartbeat_received( $response, $data ) {
		$key = 'boldgrid_backup_in_progress';

		if ( empty( $data[ $key ] ) ) {
			return $response;
		}

		// An int specifiying when the current "in progress" backup started.
		$response[ $key ] = $this->get();

		// Our "backup complete!" admin notice.
		$response['boldgrid_backup_complete'] = $this->core->notice->get_backup_complete();

		$response['in_progress_data'] = Boldgrid_Backup_Admin_In_Progress_Data::get_args();

		// If we have an error message, add an "error message notice".
		$response['boldgrid_backup_error'] = '';
		if ( ! empty( $response['in_progress_data']['error'] ) ) {
			$response['boldgrid_backup_error'] = $this->get_error_markup();
		}

		// Steps to take if we're on the last step, step 3, closing the archive.
		if ( 3 === Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'step' ) ) {
			$tmp = $this->tmp->get();
			if ( ! empty( $tmp ) ) {
				$response['in_progress_data']['tmp'] = $tmp;
			}
		}

		return $response;
	}

	/**
	 * Action to take before a database is dumped.
	 *
	 * @since 1.6.0
	 */
	public function post_dump() {
		/*
		 * After the database has been dumped, restore the flag stating a backup
		 * is still in progress.
		 *
		 * @see documentation in $this->pre_dump().
		 */
		if ( ! empty( $this->in_progress ) ) {
			$this->set( $this->in_progress );
		}
	}

	/**
	 * Action to take after a database is dumped.
	 *
	 * @since 1.6.0
	 */
	public function pre_dump() {
		/*
		 * Cancel any "Backup in progress" statuses.
		 *
		 * Avoid this issue:
		 * Before we begin creating a backup, we set a flag stating there is a
		 * "Backup in progress". So, when we create a backup of the database,
		 * that flag is in the backup. When we restore a backup, that flag will
		 * be restored, even if we're not in the middle of making a backup, thus
		 * giving us a false positive.
		 */
		$this->in_progress = $this->get();
		$this->end();
	}

	/**
	 * Set that we are in progress of backing up a website.
	 *
	 * @since 1.6.0
	 *
	 * @param int $time A unix timestamp indicating the time a backup started.
	 */
	public function set( $time = null ) {
		$settings = $this->core->settings->get_settings( true );

		$settings['in_progress'] = ! empty( $time ) ? $time : time();

		$this->core->settings->save( $settings );
	}

	/**
	 * Via ajax, get our "in progress" admin notice.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_get_progress_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if ( ! check_ajax_referer( 'boldgrid_backup_customizer', 'nonce', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$in_progress_markup = $this->get_notice_markup();

		wp_send_json_success( $in_progress_markup );
	}
}
