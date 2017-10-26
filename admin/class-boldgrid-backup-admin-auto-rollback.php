<?php
/**
 * Boldgrid Backup Admin Auto Rollback.
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
 * Boldgrid Backup Admin Auto Rollback class.
 *
 * We hook into "the upgrader_process_complete" (run when the download process
 * for a plugin install or update finishes). If the user has enabled auto
 * rollback and we have data in the boldgrid_backup_pending_rollback site
 * option, then we add the cron jobs for 5 minutes later to auto rollback.
 *
 * Auto Rollback works with the following site options:
 *
 * boldgrid_backup_pending_rollback When we manually create a backup, if we
 *                                  $_POST['is_updating'] === 'true', then the
 *                                  results of this backup file are saved in
 *                                  this option.
 *
 *                                  To cancel an auto rollback, this option
 *                                  needs to be deleted (and subsequent crons
 *                                  cleared).
 *
 *                                  array (
 *                                      compressor   "pcl_zip"
 *                                      db_duration  "0.16"
 *                                      dryrun       false
 *                                      duration     "20.07"
 *                                      filepath     "/home/user/boldgrid-backup/backup.zip"
 *                                      filesize     262562329
 *                                      lastmodunix  1505912200
 *                                      mail_success true
 *                                      mode         "backup"
 *                                      save         true
 *                                  );
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Auto_Rollback {

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * The amount of time before an auto rollback occurs.
	 *
	 * For example, allow 10 minutes for testing.
	 *
	 * @since  1.5.3
	 * @access public
	 * @var    string
	 */
	public $testing_time = '+15 minutes';

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add cron to permorm auto rollback.
	 *
	 * This method includes the checks for (1) The user enabling auto_rollback
	 * and (2) we have a recent backup labeled as the one to restore for the
	 * rollback.
	 *
	 * Based on scheduler, cron is either system cron or wp cron.
	 *
	 * @since 1.5.1
	 */
	public function add_cron() {
		$settings = $this->core->settings->get_settings();

		// If auto-rollback is not enabled, then abort.
		if ( 1 !== $settings['auto_rollback'] ) {
			$this->core->settings->delete_rollback_option();
			return;
		}

		// If a backup was not made prior to an update (from an update page), then abort.
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		if ( empty( $pending_rollback ) ) {
			return;
		}

		$archives = $this->core->get_archive_list();
		$archive_count = count( $archives );

		// If there are no archives, then abort.
		if ( $archive_count <= 0 ) {
			$this->core->settings->delete_rollback_option();
			return;
		}

		$scheduler = $this->core->scheduler->get();

		switch( $scheduler ) {
			case 'cron':
				$this->core->cron->add_restore_cron();
				break;
			case 'wp-cron':
				$this->core->wp_cron->add_restore_cron();
				break;
		}
	}

	/**
	 * Cancel rollback.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.0.1
	 */
	public function cancel() {
		// Remove any cron jobs for restore actions.
		$this->core->cron->delete_cron_entries( 'restore' );

		// Remove WP option boldgrid_backup_pending_rollback.
		$this->core->settings->delete_rollback_option();
	}

	/**
	 * Show an admin notice if there is a pending rollback.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_countdown_show() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		$deadline = ! empty( $pending_rollback['deadline'] ) ? $pending_rollback['deadline'] : null;
		$deadline_passed = ! empty( $deadline ) && $deadline <= time();
		$updated_and_pending = ! empty( $_GET['action'] ) && ! empty( $pending_rollback );

		// If there is not a pending rollback, then abort.
		if ( empty( $deadline ) && ! $updated_and_pending ) {
			return;
		}

		$archives = $this->core->get_archive_list();
		$archive_count = count( $archives );

		/*
		 * If the deadline has passed or no backup archives to restore, then
		 * remove the pending rollback information and cron.
		 */
		if ( $deadline_passed || 0 === $archive_count ) {
			$this->cancel();
			return;
		}

		wp_enqueue_style(
			'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		wp_register_script(
			'boldgrid-backup-admin-rollback',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-rollback.js',
			array( 'jquery', ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$localize_script_data = array(
			'restoreConfirmText' => esc_html__( 'Please confirm the restoration of this WordPress installation from the archive file', 'boldgrid-backup' ),
			// Include the time (in ISO 8601 format).
			'rolloutDeadline' => date( 'c', $deadline ),
		);
		wp_localize_script( 'boldgrid-backup-admin-rollback', 'localizeScriptData', $localize_script_data );
		wp_enqueue_script( 'boldgrid-backup-admin-rollback' );

		// Create and display our notice.
		$key = 0;
		$archive = $archives[ $key ];
		$args['restore_key'] = $key;
		$args['restore_filename'] = $archive['filename'];
		$notice_markup = $this->notice_countdown_get( $args );
		do_action( 'boldgrid_backup_notice', $notice_markup, 'notice notice-warning' );

		return;
	}

	/**
	 * Generate markup for the rollback notice.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.2
	 * @access private
	 *
	 * @param array $args {
	 * 		An array of arguments.
	 *
	 * 		@type int $restore_key Key index used for restoration.
	 * 		@type string $restore_filename Filename of the backup archive to be restored.
	 * }
	 * @return string The resulting markup.
	 */
	public function notice_countdown_get( $args ) {

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		$update_trigger = $this->notice_trigger_get();

		$notice_markup = sprintf( '
			<div id="cancel-rollback-section">
				<h2 class="header-notice">%1$s</h2>

				<p>%15$s %2$s</p>

				%14$s

				<p>%3$s</p>

				<p>
					<strong>%4$s</strong>:
					<span id="rollback-countdown-timer"></span>
					%16$s
				</p>

				<form action="#" id="cancel-rollback-form" method="POST">
					%5$s
					<p>
						<a id="cancel-rollback-button" class="button button-primary">%6$s</a> <span class="spinner"></span>
					</p>
				</form>
			</div>

			<div id="restore-now-section">
				<form action="%8$s" class="restore-now-form" method="POST">
					<input type="hidden" name="restore_now" value="1" />
					<input type="hidden" name="archive_key" value="%9$s" />
					<input type="hidden" name="archive_filename" value="%10%s" />
					%11$s
					<input type="submit" class="button action-restore" data-key="%12$s" data-filename="%10$s" value="%13$s" />
					<span class="spinner"></span>
				</form>
			</div>

			<div id="cancel-rollback-results"></div>
			',
			/* 1 */ $this->core->lang['heading_update_protection'],
			/* 2 */ esc_html__( 'There is a pending automatic rollback using the most recent backup archive.', 'boldgrid-backup' ),
			/* 3 */ __( 'Now is the time to test your website to ensure the upgrade did not break anything. If the upgrade did cause problems, you can click <strong>Rollback Site Now</strong> to restore your site to just before the update. If the update was a success, click <strong>Cancel Rollback</strong> so a backup is not automatically restored at the end of the countdown.', 'boldgrid-backup' ),
			/* 4 */ esc_html__( 'Countdown', 'boldgrid-backup' ),
			/* 5 */ wp_nonce_field( 'boldgrid_rollback_notice', 'cancel_rollback_auth', true, false ),
			/* 6 */ esc_html__( 'Cancel Rollback', 'boldgrid-backup' ),
			/* 7 */ esc_html__( 'You can click the button below to rollback your site now.', 'boldgrid-backup' ),
			/* 8 */ get_admin_url( null, 'admin.php?page=boldgrid-backup' ),
			/* 9 */ $args['restore_key'],
			/* 10 */ $args['restore_filename'],
			/* 11 */ wp_nonce_field( 'archive_auth', 'archive_auth', true, false ),
			/* 12 */ $args['restore_key'],
			/* 13 */ esc_html__( 'Rollback Site Now', 'boldgrid-backup' ),
			/* 14 */ ! empty( $update_trigger ) ? sprintf( '<p>%1$s</p>', $update_trigger ) : '',
			/* 15 */ $this->core->lang['icon_warning'],
			/* 16 */ ! empty( $pending_rollback['deadline'] ) ? sprintf( '(<em>%1$s</em>)', date( 'g:i a', $this->core->utility->time( $pending_rollback['deadline'] ) ) ) : __( 'n/a', 'boldgrid-backup' )
		);

		return $notice_markup;
	}

	/**
	 * Get the pending rollback deadline (in unix seconds).
	 *
	 * @since 1.2
	 *
	 * @return int The pending rollback deadline in unix seconds, or zero if not present.
	 */
	public function get_deadline() {
		// Get pending rollback information.
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		// Return pending rollback deadline, or 0 if not present.
		if ( empty( $pending_rollback['deadline'] ) ) {
			return 0;
		} else {
			return $pending_rollback['deadline'];
		}
	}

	/**
	 * Create markup to show what was updated.
	 *
	 * @since 1.5.3
	 *
	 * @return mixed String on success, false on failure.
	 */
	public function notice_trigger_get() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		$notice = false;
		$li = array();

		if( empty( $pending_rollback['update_trigger'] ) ) {
			return false;
		}

		$trigger = $pending_rollback['update_trigger'];

		if( 'update' !== $trigger['action'] ) {
			return false;
		}

		if( 'core' === $trigger['type'] ) {
			$wordpress_version = get_bloginfo( 'version' );
			$notice = sprintf( __( 'WordPress was recently updated to version %1$s.', 'boldgrid-backup' ), $wordpress_version );
		} elseif( 'theme' === $trigger['type'] ) {

			foreach( $trigger['themes'] as $theme ) {
				$data = wp_get_theme( $theme );
				$li[] = sprintf( '<strong>%1$s</strong> to version %2$s', $data->get( 'Name' ), $data->get( 'Version' ) );
			}
			$notice = __( 'The following theme(s) were recently updated:', 'boldgrid-backup' ) . '<br />';
			$notice .= implode( '<br />', $li );
		} elseif( 'plugin' === $trigger['type'] ) {

			foreach( $trigger['plugins'] as $plugin ) {
				$path = dirname( BOLDGRID_BACKUP_PATH ) . DIRECTORY_SEPARATOR . $plugin;
				$data = get_plugin_data( $path );
				$li[] = sprintf( '<strong>%1$s</strong> to version %2$s', $data['Name'], $data['Version'] );
			}
			$notice = __( 'The following plugin(s) were recently updated:', 'boldgrid-backup' ) . '<br />';
			$notice .= implode( '<br />', $li );
		}

		return $notice;
	}

	/**
	 * Generate markup for "You should make a backup for updating".
	 *
	 * @since 1.5.3
	 *
	 * @global $pagenow
	 *
	 * @return string
	 */
	public function notice_backup_get() {
		global $pagenow;

		$notice_text = sprintf( '<h2 class="header-notice">%1$s</h2>', __( 'BoldGrid Backup - Update Protection', 'boldgrid-backup' ) );

		$notice_text .= '<p>';

		switch( $pagenow ) {
			case 'update-core.php':
				$notice_text .= __( 'On this page you are able to update WordPress, Plugins, and Themes.' ) . ' ';
				break;
			case 'plugins.php':
				$notice_text .= __( 'On this page you are able to update plugins.' ) . ' ';
				break;
		}

		$notice_text .= __( 'It is recommended to backup your site before performing updates. If you perform a backup here, before performing updates, then an automatic rollback is possible.', 'boldgrid-backup' );

		$notice_text .= '</p>';

		$notice_text .= sprintf(
			'<p id="protection_enabled">%1$s %2$s</p>',
			$this->core->lang['icon_warning'],
			__( 'Update protection not available until you click <strong>Backup Site Now</strong> and a backup is created.', 'boldgrid-backup' )
		);

		return $notice_text;
	}

	/**
	 * Show an admin notice on the WordPress Updates page.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.0
	 */
	public function notice_backup_show() {
		// Get pending rollback information.
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		// If we're in the middle of a countdown, abort.
		if( ! empty( $pending_rollback['deadline'] ) ) {
			return;
		}

		// If there is a pending rollback, then abort.
		if ( ! empty( $pending_rollback['lastmodunix'] ) ) {
			$this->notice_activated_show();
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style( 'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Register the JS.
		wp_register_script(
			'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-home.js',
			array( 'jquery', ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$access_type = get_filesystem_method();
		$archive_nonce = wp_create_nonce( 'archive_auth' );
		$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup&backup_now=1' );
		$localize_script_data = array(
			'archiveNonce' => $archive_nonce,
			'accessType' => $access_type,
			'backupUrl' => $backup_url,
			'updateProtectionActivated' => $this->core->elements['update_protection_activated'],
			'backupCreated' => $this->core->lang['backup_created'],
		);
		wp_localize_script( 'boldgrid-backup-admin-home', 'localizeScriptData', $localize_script_data );
		wp_enqueue_script( 'boldgrid-backup-admin-home' );

		wp_enqueue_script( 'boldgrid-backup-now' );

		// Show admin notice.
		$backup_button = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php';
		$size_data = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-size-data.php';
		$notice = $this->notice_backup_get();
		do_action( 'boldgrid_backup_notice', $notice_text . $size_data . $backup_button, 'notice notice-warning is-dismissible' );

		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-js-templates.php';
	}

	/**
	 * Callback function for the hook "upgrader_process_complete".
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.2
	 *
	 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
	 * @see Boldgrid_Backup_Admin_Cron::add_restore_cron().
	 */
	public function notice_deadline_show( $upgrader_object, $options ) {
		// Add/update restoration cron job.
		$this->add_cron();

		$this->set_update_trigger( $options );

		// If not on an admin page, then abort.
		if ( ! is_admin() ) {
			return;
		}

		// Get pending rollback deadline.
		$deadline = $this->get_deadline();

		// If there is not a pending rollback, then abort.
		if ( empty( $deadline ) ) {
			return;
		}

		// Get the ISO time (in ISO 8601 format).
		$iso_time = date( 'c', $deadline );

		// Print a hidden div with the time, so that JavaScript can read it.
		printf( '<div class="hidden" id="rollback-deadline">%1$s</div>', $iso_time );
	}

	/**
	 * Save the update trigger.
	 *
	 * @since 1.5.3
	 *
	 * @param array $options https://pastebin.com/ah4E048B
	 */
	public function set_update_trigger( $options ) {
		if( empty( $options ) || ! is_array( $options ) ) {
			return;
		}

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		if( empty( $pending_rollback ) || ! is_array( $pending_rollback ) ) {
			return;
		}

		$pending_rollback[ 'update_trigger' ] = $options;

		update_site_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
	}

	/**
	 * Show a message that the user has backup protection.
	 *
	 * @since 1.5.3
	 *
	 * @param array $pending_rollback
	 */
	public function notice_activated_show( $pending_rollback = null ) {
		global $pagenow;

		if( is_null( $pending_rollback ) ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		}

		$message = '<h2 class="header-notice">' . $this->core->lang['heading_update_protection'] . '</h2>';

		$message .= sprintf( '<p>%1$s</p>', $this->core->elements['update_protection_activated'] );

		$message .= '<p>';

		$message .= sprintf(
			__( 'You last made a backup %1$s ago.', 'boldgrid-backup' ),
			human_time_diff( $pending_rollback['lastmodunix'], time() )
		) . ' ';

		switch( $pagenow ) {
			case 'update-core.php':
				$message .= __( 'If you update WordPress, any plugins, or any themes on this page, an auto rollback will occur if anything goes wrong.', 'boldgrid-backup' );
				break;
			case 'plugins.php':
				$message .= __( 'If you update a plugin on this page, an auto rollback will occur if anything goes wrong.', 'boldgrid-backup' );
		}

		$message .= '</p>';

		do_action( 'boldgrid_backup_notice', $message, 'notice notice-success is-dismissible' );
	}

	/**
	 * Callback function for canceling a pending rollback.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.0
	 */
	public function wp_ajax_cancel() {
		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die(
				'<div class="error"><p>' .
				esc_html__( 'Security violation (not authorized).', 'boldgrid-backup' ) .
				'</p></div>'
			);
		}

		// Verify nonce, or die with an error message.
		if ( ! isset( $_POST['cancel_rollback_auth'] ) ||
			1 !== check_ajax_referer( 'boldgrid_rollback_notice', 'cancel_rollback_auth', false ) ) {
				wp_die(
					'<div class="error"><p>' .
					esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ) .
					'</p></div>'
				);
		}

		// Clear rollback information.
		$this->cancel();

		// Echo a success message.
		echo '<p>Automatic rollback has been canceled.</p>';

		// End nicely.
		wp_die();
	}

	/**
	 * Callback for getting the rollback deadline.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * @since 1.2.1
	 */
	public function wp_ajax_get_deadline() {
		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die();
		}

		// Get the rollback deadline.
		$deadline = $this->get_deadline();

		// If there is no deadline, then die.
		if ( empty( $deadline ) ) {
			wp_die();
		}

		// Convert the deadline to ISO time (in ISO 8601 format).
		$iso_time = date( 'c', $deadline );

		wp_die( $iso_time );
	}
}
