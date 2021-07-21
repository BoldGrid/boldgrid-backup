<?php
/**
 * File: class-boldgrid-backup-admin-auto-rollback.php
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

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Auto_Rollback
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
	 * Time data.
	 *
	 * An array of time about when our auto rollback needs to occur.
	 *
	 * When we are setting our auto rollback cron, we need to get the time for 15 minutes from now
	 * (or whatever time limit is set in the config). When we parse that time, we split out the minute,
	 * hour, etc, to help build the cron command.
	 *
	 * After parsing the time, we save it in this class property.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var array
	 */
	private $time_data = [];

	/**
	 * Whether or not we are on an update page.
	 *
	 * An update page is a page that allows the user to update either WP, a plugin,
	 * or a theme. Defined in the constructor.
	 *
	 * Used by the backup now button to determine if the backup being made is
	 * for update protection.
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    bool
	 */
	public $on_update_page = false;

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
	 * An array of pagenow's in which the user has the option to update either
	 * WP, a plugin, or a theme.
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    array
	 */
	protected $update_pages = array(
		'customize.php',
		'plugins.php',
		'plugin-install.php',
		'themes.php',
		'update-core.php',
	);

	/**
	 * Whether or not we are in the middle of upgrading core.
	 *
	 * When we've clicked "Update Now" on Dashboards > Updates, we are redirected
	 * to wp-admin/update-core.php?action=do-core-upgrade
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    bool
	 */
	public $updating_core = false;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->updating_core = 'update-core.php' === $this->core->pagenow &&
			! empty( $_GET['action'] ) && 'do-core-upgrade' === $_GET['action']; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

		$this->on_update_page = in_array( $this->core->pagenow, $this->update_pages, true );
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

		$archives      = $this->core->get_archive_list();
		$archive_count = count( $archives );

		// If there are no archives, then abort.
		if ( $archive_count <= 0 ) {
			$this->core->settings->delete_rollback_option();
			return;
		}

		$scheduler = $this->core->scheduler->get();

		switch ( $scheduler ) {
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
	 * Enqueue backup scripts.
	 *
	 * Backup scripts are those needed to handle any "Backup" buttons.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_backup_scripts() {
		$handle = 'boldgrid-backup-admin-backup-now';

		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-backup-now.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		wp_enqueue_script( $handle );
	}

	/**
	 * Enqueue scripts within the customizer.
	 *
	 * Currently this includes adding Update Protection to the themes area where
	 * users can upgrade themes.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_customize_controls() {
		$handle = 'boldgrid-backup-admin-customizer';

		wp_enqueue_style(
			$handle,
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-customizer.css', array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-customizer.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$translations = array(
			'update_data'         => wp_get_update_data(),
			'in_progress_notice'  => $this->core->in_progress->get_notice_markup(),
			'nonce'               => wp_create_nonce( 'boldgrid_backup_customizer' ),
			'is_rollback_enabled' => $this->is_enabled(),
		);
		wp_localize_script( $handle, 'boldgridBackupCustomizer', $translations );
		wp_enqueue_script( $handle );

		$this->enqueue_backup_scripts();

		$this->enqueue_rollback_scripts();

		$this->enqueue_update_selectors();
	}

	/**
	 * Enqueue scripts needed for the home page (the archives page).
	 *
	 * @since 1.6.0
	 */
	public function enqueue_home_scripts() {
		$handle = 'boldgrid-backup-admin-home';

		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-home.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$translation = array(
			'savedTo'      => __( 'File saved to: ', 'boldgrid-backup' ),
			'viewDetails'  => __( 'View details', 'boldgrid-backup' ),
			'invalidUrl'   => __( 'You must enter valid a URL address.', 'boldgrid-backup' ),
			'notZip'       => __( 'The URL address is not a ZIP file.', 'boldgrid-backup' ),
			'unknownError' => __( 'Unknown error.', 'boldgrid-backup' ),
			'ajaxError'    => __( 'Could not reach the URL address. HTTP error: ', 'boldgrid-backup' ),
			'urlRegex'     => $this->core->configs['url_regex'],
			'restore'      => __( 'Restore', 'boldgrid-backup' ),
		);

		wp_localize_script( $handle, 'BoldGridBackupAdminHome', $translation );
		wp_enqueue_script( $handle );
	}

	/**
	 * Enqueue scripts required for the rollback functionality.
	 *
	 * The $deadline param may not always be passed in because there may not be
	 * a deadline yet. Here's such a scenario:
	 * # We're showing the "backup now for upgrade protection" notice.
	 * # The user creates a backup.
	 * # The user does an ajaxy theme update.
	 * # After the update, we show the countdown via ajax.
	 * These scripts were already enqueued on page load for the possiblity that
	 * the user ajaxy updates. If they do, the countdown notice will be shown
	 * and the button clicks need to be handled.
	 *
	 * @since 1.6.0
	 *
	 * @param int $deadline Auto rollback deadline (unix timestamp).
	 */
	public function enqueue_rollback_scripts( $deadline = null ) {
		$handle = 'boldgrid-backup-admin-rollback';

		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-rollback.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		if ( ! empty( $deadline ) ) {
			$localize_script_data = array(
				// Include the time (in ISO 8601 format).
				'rolloutDeadline' => date( 'c', $deadline ),
			);
			wp_localize_script( $handle, 'boldgrid_backup_admin_rollback', $localize_script_data );
		}

		wp_enqueue_script( $handle );

		/*
		 * If there is a countdown showing in the customizer, there's also a
		 * rollback button, which is handled by the archive_actions class.
		 */
		$this->core->archive_actions->enqueue_scripts();
	}

	/**
	 * Enqueue update-selectors script.
	 *
	 * The update-selectors script is intended to help dynamically disable /
	 * enable any "update" buttons or links on a page.
	 *
	 * For example, if you are in the middle of making a backup, you probably
	 * shouldn't be performing any updates.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_update_selectors() {
		$handle = 'boldgrid-backup-admin-update-selectors';

		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-update-selectors.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$localize_script_data = array(
			// Generally used as the title attr of a disable update button.
			'backupInProgress' => __( 'Your website is currently being backed up. You can perform updates when the backup is complete.', 'boldgrid-backup' ),
			'waitClass'        => 'bgbu-wait',
		);

		wp_localize_script( $handle, 'boldgrid_backup_admin_update_selectors', $localize_script_data );

		wp_enqueue_script( $handle );
	}

	/**
	 * Show an admin notice if there is a pending rollback.
	 *
	 * Prior to @1.5.3 this method was in the core class.
	 *
	 * This method is called in the admin_notices hook.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_countdown_show() {
		// Process GET / POST info.
		$action      = ! empty( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : null; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		$restore_now = ! empty( $_POST['restore_now'] ); // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		$deadline         = ! empty( $pending_rollback['deadline'] ) ? $pending_rollback['deadline'] : null;
		$deadline_passed  = ! empty( $deadline ) && $deadline <= time();

		if ( $this->on_update_page ) {
			$this->enqueue_rollback_scripts();
		}

		/*
		 * Updated and pending.
		 *
		 * The initial implementation of this variable is not very well
		 * documented. As of 1.6.0, this is the best interpretation.
		 *
		 * It appears that this var is meant to tell us when we're on a page in
		 * which a plugin/theme is being updated, such as:
		 * wp-admin/update-core.php?action=do-plugin-upgrade
		 * ... and we have a backup that is pending restoration.
		 *
		 * When you update a plugin via
		 * wp-admin/update-core.php?action=do-plugin-upgrade
		 * the $deadline won't be set until the iframe doing the upgrade completes.
		 * Example iframe: update.php?action=update-selected&plugins=plugin.php&_wpnonce=1234
		 *
		 * As long as we know we're on a page upgrading a plugin AND we have a
		 * pending rollback, we'll show the countdown (even though we don't have
		 * a deadline). Once the iframe loads, we'll read the deadline from the
		 * iframe and update the countdown.
		 *
		 * @todo Clean up the above comment in the future once determined this
		 * is accurate.
		 */
		$updated_and_pending = 'update-core.php' === $this->core->pagenow && ! empty( $action ) && ! empty( $pending_rollback );

		// If we're restoring a file, we don't need to show any notices.
		if ( $restore_now ) {
			return;
		}

		// If there is not a pending rollback, then abort.
		if ( empty( $deadline ) && ! $updated_and_pending ) {
			return;
		}

		$archives      = $this->core->get_archive_list();
		$archive_count = count( $archives );

		/*
		 * If the deadline has passed or no backup archives to restore, then
		 * remove the pending rollback information and cron.
		 */
		if ( $deadline_passed || 0 === $archive_count ) {
			$this->cancel();
			return;
		}

		/*
		 * Abort if we are updating core.
		 *
		 * The update of core is different than the updating of plugins or themes
		 * because instead of sitting on this page after the upgrade, the user
		 * is redirected to about.php.
		 *
		 * Because we're redirecting, there's no need to show the countdown on
		 * this page.
		 */
		if ( $this->updating_core ) {
			return;
		}

		wp_enqueue_style(
			'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		$this->enqueue_rollback_scripts( $deadline );

		/*
		 * Create and display our notice.
		 *
		 * The boldgrid-backup-countdown class was added as of 1.6.0 so we can
		 * uniquely identify this notice.
		 */
		$notice_markup = $this->notice_countdown_get();
		do_action( 'boldgrid_backup_notice', $notice_markup, 'notice notice-warning is-dismissible boldgrid-backup-countdown' );
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
	 *      An array of arguments.
	 *
	 *      @type int $restore_key Key index used for restoration.
	 *      @type string $restore_filename Filename of the backup archive to be restored.
	 * }
	 * @return string The resulting markup.
	 */
	public function notice_countdown_get( $args = array() ) {

		// By default we will restore the newest backup.
		if ( empty( $args ) ) {
			$key      = 0;
			$archives = $this->core->get_archive_list();
			$args     = array(
				'restore_key'      => $key,
				'restore_filename' => $archives[ $key ]['filename'],
			);
		}

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		$deadline         = ! empty( $pending_rollback['deadline'] ) ? sprintf( '(<em>%1$s</em>)', date( 'g:i a', $this->core->utility->time( $pending_rollback['deadline'] ) ) ) : '';

		$update_trigger = $this->notice_trigger_get();
		$update_trigger = ! empty( $update_trigger ) ? sprintf( '<p>%1$s</p>', $update_trigger ) : '';

		$nonce = wp_nonce_field( 'boldgrid_rollback_notice', 'cancel_rollback_auth', true, false );

		$button_args    = array(
			'button_text' => __( 'Rollback Site Now', 'boldgrid-backup' ),
		);
		$restore_button = $this->core->archive_actions->get_restore_button( $args['restore_filename'], $button_args );

		$iso_time          = ! empty( $pending_rollback['deadline'] ) ? date( 'c', $pending_rollback['deadline'] ) : null;
		$rollback_deadline = sprintf( '<input type="hidden" id="rollback-deadline" value="%1$s" />', $iso_time );

		$notice_markup = '
			<div id="cancel-rollback-section">
				<h2 class="header-notice">' . $this->core->lang['heading_update_protection'] . '</h2>

				<p>
					' . $this->core->lang['icon_warning'] . ' ' . __( 'There is a pending automatic rollback using the most recent backup archive.', 'boldgrid-backup' ) . '
				</p>

				' . $update_trigger . '

				<p>
					' . __( 'Now is the time to test your website to ensure the upgrade did not break anything. If the upgrade did cause problems, you can click <strong>Rollback Site Now</strong> to restore your site to just before the update. If the update was a success, click <strong>Cancel Rollback</strong> so a backup is not automatically restored at the end of the countdown.', 'boldgrid-backup' ) . '
				</p>

				<p>
					' .
			sprintf(
				// translators: 1: URL address.
				__(
					'<strong>Update Protection</strong> for <em>future updates</em> can be configured on your <a href="%1$s">Settings</a> page.',
					'boldgrid-backup'
				),
				admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_updates' )
			) . '
				</p>

				<p>
					<strong>' . __( 'Countdown', 'boldgrid-backup' ) . '</strong>:
					<span id="rollback-countdown-timer">
						<span class="spinner inline"></span>
					</span>
					' . $deadline . '
				</p>

				<form action="#" id="cancel-rollback-form" method="POST">
					' . $nonce . '
					<p>
						<a id="cancel-rollback-button" class="button button-primary">' . __( 'Cancel Rollback', 'boldgrid-backup' ) . '</a> <span class="spinner"></span>
					</p>
				</form>
			</div>

			<p>' . $restore_button . '</p>

			<div id="cancel-rollback-results"></div>
			' . $rollback_deadline;

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
	 * Get our auto rollback time data.
	 *
	 * This code was originally contained within Boldgrid_Backup_Admin_Cron::add_restore_cron, but
	 * has since been separated out for reusability.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_time_data() {
		if ( ! empty( $this->time_data ) ) {
			return $this->time_data;
		}

		$time = [];

		// Get the unix time for 5 minutes from now.
		$time_5_minutes_later = strtotime( $this->testing_time );

		// Get the system's localized current time (HH:MM:SS), 5 minutes in the future.
		$system_time = $this->core->execute_command(
			'date "+%H|%M|%S|%a %d %b %Y %I:%M:00 %p %Z" -d "' . $this->testing_time . '"'
		);

		// Split the time into hour, minute, and second.
		if ( ! empty( $system_time ) ) {
			list(
				$time['hour'],
				$time['minute'],
				$time['second'],
				$time['system_time_iso']
			) = explode( '|', $system_time );
		}

		// Validate hour; use system hour, or the date code for hour ("G").
		if ( ! isset( $time['hour'] ) ) {
			$time['hour'] = 'G';
		}

		// Validate hour; use system hour, or the date code for minute ("i").
		if ( ! isset( $time['minute'] ) ) {
			$time['minute'] = 'i';
		}

		// Mark the deadline.
		if ( ! empty( $time['system_time_iso'] ) ) {
			$time['deadline'] = strtotime( $time['system_time_iso'] );
		} else {
			$time['deadline'] = $time_5_minutes_later;
		}

		$this->time_data = $time;

		return $this->time_data;
	}

	/**
	 * Return a bool indicating whether or not auto_rollback is enabled.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$settings = $this->core->settings->get_settings();

		return isset( $settings['auto_rollback'] ) && 1 === $settings['auto_rollback'];
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
		$notice           = false;
		$li               = array();

		if ( empty( $pending_rollback['update_trigger'] ) ) {
			return false;
		}

		$trigger = $pending_rollback['update_trigger'];

		if ( 'update' !== $trigger['action'] ) {
			return false;
		}

		switch ( $trigger['type'] ) {
			case 'core':
				$wordpress_version = get_bloginfo( 'version' );
				$notice            = sprintf(
					// translators: 1: WordPress version string.
					__(
						'WordPress was recently updated to version %1$s.',
						'boldgrid-backup'
					),
					$wordpress_version
				);
				break;
			case 'theme':
				foreach ( $trigger['themes'] as $theme ) {
					$data = wp_get_theme( $theme );
					$li[] = sprintf( '<strong>%1$s</strong> to version %2$s', $data->get( 'Name' ), $data->get( 'Version' ) );
				}
				$notice  = __( 'The following theme(s) were recently updated:', 'boldgrid-backup' ) . '<br />';
				$notice .= implode( '<br />', $li );
				break;
			case 'plugin':
				$plugins = ! empty( $trigger['plugins'] ) ? $trigger['plugins'] : array( $trigger['plugin'] );

				foreach ( $plugins as $plugin ) {
					$data = $this->core->utility->get_plugin_data( $plugin );
					$li[] = sprintf( '<strong>%1$s</strong> to version %2$s', $data['Name'], $data['Version'] );
				}
				$notice  = __( 'The following plugin(s) were recently updated:', 'boldgrid-backup' ) . '<br />';
				$notice .= implode( '<br />', $li );
				break;
		}

		return $notice;
	}

	/**
	 * Generate markup for "You should make a backup for updating".
	 *
	 * It does not generate the entire markup of the notice, nor the entire contents of the notice.
	 *
	 * @since 1.5.3
	 *
	 * @return string
	 */
	public function notice_backup_get() {
		$notice_text = sprintf(
			'<h2 class="header-notice">%1$s</h2>',
			BOLDGRID_BACKUP_TITLE . ' - ' . __( 'Update Protection', 'boldgrid-backup' )
		);

		$notice_text .= '<p>';

		switch ( $this->core->pagenow ) {
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
	 *
	 * @global string $pagenow
	 */
	public function notice_backup_show() {
		$action = ! empty( $_GET['action'] ) ? $_GET['action'] : null; // phpcs:ignore

		/*
		 * The $_GET action that specifies an update has just occurred. These generally happen from
		 * Dashboard > Updates > Update.
		 */
		$update_actions = [
			'do-theme-upgrade',
			'do-plugin-upgrade',
			'do-core-reinstall',
			'do-core-upgrade',
		];

		// Whether or not we just updated something.
		$just_updated = 'update-core.php' === $this->core->pagenow && in_array( $action, $update_actions, true );

		/*
		 * This method is hooked into admin_notices. If we don't have auto_rollback
		 * enabled, then we can abort right now.
		 */
		if ( ! $this->is_enabled() ) {
			return;
		}

		/*
		 * If we just activated the plugin, we'll be showing a, "Thanks for installing!" notice. We
		 * don't want to bombard the user with notices, so don't show this notice too.
		 */
		if ( Boldgrid_Backup_Activator::on_post_activate() ) {
			return;
		}

		// Don't show notice if we just updated.
		if ( $just_updated ) {
			return;
		}

		$display = false;

		$configs = array(
			array(
				'pagenow' => 'plugins.php',
				'check'   => 'plugins',
			),
			array(
				'pagenow' => 'themes.php',
				'check'   => 'themes',
			),
			array(
				'pagenow' => 'update-core.php',
				'check'   => 'total',
			),
		);

		/**
		 * Allow other plugins to filter the pages the backup notice shows on.
		 *
		 * @since 1.6.0
		 *
		 * @param array $configs
		 */
		$configs = apply_filters( 'boldgrid_backup_notice_show_configs', $configs );

		/*
		 * Based on our $configs, determine if we need to show a notice.
		 *
		 * The nested if's below are designed to save resources and only call
		 * wp_get_update_data() if we are on a $pagenow that should show the
		 * notice.
		 */
		foreach ( $configs as $config ) {
			if ( $this->core->pagenow === $config['pagenow'] ) {
				$update_data = ! isset( $update_data ) ? wp_get_update_data() : $update_data;
				if ( $update_data['counts'][ $config['check'] ] ) {
					$display = true;
					break;
				}
			}
		}

		if ( ! $display || $this->updating_core ) {
			return;
		}

		// Get pending rollback information.
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		// If we're in the middle of a countdown, abort.
		if ( ! empty( $pending_rollback['deadline'] ) ) {
			return;
		}

		// If there is a pending rollback (backup within the last hour), then abort.
		if ( ! empty( $pending_rollback['lastmodunix'] ) ) {
			$this->notice_activated_show();

			return;
		}

		$this->enqueue_backup_scripts();

		/*
		 * Show admin notice.
		 *
		 * The boldgrid-backup-protect-now class was added to the notice as of
		 * 1.6.0 so that we can uniquely identify this notice on the page.
		 */
		$backup_button = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php';
		$in_progress   = Boldgrid_Backup_Admin_In_Progress::get_notice( true );
		$notice        = $this->notice_backup_get();
		do_action( 'boldgrid_backup_notice', $notice . $backup_button . $in_progress['message'], 'notice notice-warning is-dismissible boldgrid-backup-protect-now' );
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
	 * @link https://pastebin.com/ah4E048B
	 *
	 * @param object $upgrader_object Plugin_Upgrader Object.
	 * @param array  $options         Options array.
	 */
	public function notice_deadline_show( $upgrader_object, $options ) {
		/*
		 * This method is ran both when a plugin/theme/WP is updated, and when
		 * a plugin is simply uploaded. As of 1.6.0, this plugin does not offer
		 * update protection for plugin uploads and activation, only for updates.
		 *
		 * @todo Allow update protection for plugin activation.
		 */
		if ( empty( $options['action'] ) || 'update' !== $options['action'] ) {
			return;
		}

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

		// Print a hidden div with the time (in ISO 8601 format), so that JavaScript can read it.
		?>
		<div class="hidden" id="rollback-deadline"><?php echo esc_html( date( 'c', $deadline ) ); ?></div>
		<?php
	}

	/**
	 * Save the update trigger.
	 *
	 * @since 1.5.3
	 *
	 * @link https://pastebin.com/ah4E048B
	 *
	 * @param array $options Option array.
	 */
	public function set_update_trigger( $options ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			return;
		}

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		if ( empty( $pending_rollback ) || ! is_array( $pending_rollback ) ) {
			return;
		}

		$pending_rollback['update_trigger'] = $options;

		update_site_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
	}

	/**
	 * Get our 'activated' notice.
	 *
	 * This is the notice that says you're protected, go ahead and update.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public function notice_activated_get() {

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		$theme_message = __( 'If you update a theme on this page, an auto rollback will occur if anything goes wrong.', 'boldgrid-backup' );

		$message = '<h2 class="header-notice">' . $this->core->lang['heading_update_protection'] . '</h2>';

		$message .= sprintf( '<p>%1$s</p>', $this->core->elements['update_protection_activated'] );

		$message .= '<p>';

		$message .= sprintf(
			// translators: 1: Time difference.
			__( 'You last made a backup %1$s ago.', 'boldgrid-backup' ),
			human_time_diff( $pending_rollback['lastmodunix'], time() )
		) . ' ';

		switch ( $this->core->pagenow ) {
			case 'update-core.php':
				$message .= __( 'If you update WordPress, any plugins, or any themes on this page, an auto rollback will occur if anything goes wrong.', 'boldgrid-backup' );
				break;
			case 'plugins.php':
				$message .= __( 'If you update a plugin on this page, an auto rollback will occur if anything goes wrong.', 'boldgrid-backup' );
				break;
			case 'themes.php':
				$message .= $theme_message;
				break;
		}

		// Customize our message for the "update theme" feature within the customizer.
		$path = wp_parse_url( wp_get_referer(), PHP_URL_PATH );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && 'customize.php' === substr( $path, -1 * strlen( 'customize.php' ) ) ) {
			$message .= $theme_message;
		}

		$message .= '</p>';

		$message = array(
			'html'  => $message,
			'class' => 'notice notice-success is-dismissible boldgrid-backup-protected',
		);

		return $message;
	}

	/**
	 * Show a message that the user has backup protection.
	 *
	 * If we have a pending $pending_rollback['lastmodunix'], tell the user,
	 * "You're protected" rather than, "Create a backup and protect yourself".
	 *
	 * This method is called via:
	 * action:admin_notices > self::notice_backup_show().
	 *
	 * @since 1.5.3
	 */
	public function notice_activated_show() {
		/*
		 * If we're in the middle of upgrading something, such as:
		 * update-core.php?action=do-theme-upgrade
		 * Then there's no need to show a message.
		 */
		if ( ! empty( $_GET['action'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			return;
		}

		$message = $this->notice_activated_get();

		do_action( 'boldgrid_backup_notice', $message['html'], $message['class'] );
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
	 * Usage example includes customizer: on load an ajax call (action:boldgrid_backup_deadline) is made
	 * and this method handles it. If a deadline is returned, the deadline is put in the notice and
	 * more actions occur.
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
		wp_die( esc_html( date( 'c', $deadline ) ) );
	}

	/**
	 * Get the countdown method via an ajax call.
	 *
	 * Useful when plugins are updated via ajaxy and we need to show the
	 * countdown without refreshing the page.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_get_countdown_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error();
		}

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		if ( empty( $pending_rollback ) ) {
			wp_send_json_error();
		}

		$notice = $this->notice_countdown_get();
		$notice = '<div class="notice notice-warning is-dismissible boldgrid-backup-countdown">' . $notice . '</div>';

		wp_send_json_success( $notice );
	}

	/**
	 * Get our "protect" notice.
	 *
	 * This will return either the "get protected" or "you are protected" notice.
	 *
	 * Example usage in the customzer: When accessing the "change theme" section, an ajax call is made
	 * (action:boldgrid_backup_get_protect_notice) and this method handles it. We return an entire notice
	 * and customizer.js will add it to the page.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_get_protect_notice() {
		$response = array();

		if ( ! current_user_can( 'update_plugins' ) || ! $this->core->test->run_functionality_tests() ) {
			wp_send_json_error();
		}

		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		if ( ! empty( $pending_rollback ) ) {
			// You're protected, go ahead and update.
			$message            = $this->notice_activated_get();
			$response['notice'] = sprintf( '<div class="%1$s">%2$s</div>', $message['class'], $message['html'] );
		} else {
			// You're not protected, make a backup first.
			$notice             = $this->notice_backup_get();
			$backup_button      = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php';
			$in_progress        = Boldgrid_Backup_Admin_In_Progress::get_notice( true );
			$response['notice'] = '<div class="notice notice-warning is-dismissible boldgrid-backup-protect-now">' . $notice . $backup_button . $in_progress['message'] . '</div>';
		}

		$response['is_done'] = Boldgrid_Backup_Admin_In_Progress::is_done();

		wp_send_json_success( $response );
	}

	/**
	 * Validate the rollback option when retrieved.
	 *
	 * @since 1.7.0
	 *
	 * @param  array|false $value  WordPress option value for "boldgrid_backup_pending_rollback".
	 * @param  string      $option Option name.
	 * @return array|false
	 */
	public function validate_rollback_option( $value, $option ) {
		$is_coutdown_active = ! empty( $value['deadline'] );
		$is_recent_backup   = ! empty( $value['lastmodunix'] ) &&
			strtotime( '-1 HOUR' ) <= $value['lastmodunix'];

		if ( ! $is_recent_backup && ! $is_coutdown_active ) {
			delete_site_option( $option );
			$value = false;
		}

		return $value;
	}

	/**
	 * Callback function for canceling a pending rollback from the cli process.
	 *
	 * This admin-ajax call is unprovileged, so that the CLI script can make the call.
	 * The only validation that we use is the backup identifier.
	 * Nobody will be trying to cancel rollbacks (with a 15-minute window) anyways.
	 *
	 * @since 1.10.7
	 */
	public function wp_ajax_cli_cancel() {
		$backup_id_match = ! empty( $_GET['backup_id'] ) && $this->core->get_backup_identifier() === sanitize_key( $_GET['backup_id'] ); // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

		if ( $backup_id_match ) {
			$this->cancel();
			wp_send_json_success( __( 'Rollback canceled', 'boldgrid-backup' ) );
		} else {
			wp_send_json_error( __( 'Invalid arguments', 'boldgrid-backup' ), 400 );
		}
	}
}
