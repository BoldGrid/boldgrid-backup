<?php
/**
 * File: class-boldgrid-backup-admin-storage-local.php
 *
 * Local storage.
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/storage
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Storage_Local
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Storage_Local {
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
	 * Delete a local backup file.
	 *
	 * This method is registered to the "boldgrid_backup_delete_local" action.
	 * If the user does not wish to keep local copies of backups, after all
	 * remote backup providers have been run, this method will run and delete
	 * it locally.
	 *
	 * @since 1.5.2
	 *
	 * @param string $filepath Full path to backup file.
	 */
	public function delete_local( $filepath ) {
		return $this->core->wp_filesystem->delete( $filepath );
	}

	/**
	 * Action to take after a backup file has been created.
	 *
	 * While this method's name is vague, "post_archive_files", it has a very specific purpose:
	 * If the user has not chosen to keep local copies, this method adds the "delete local copy" to
	 * the jobs queue.
	 *
	 * This method is ran after every single backup is made. Because of its very specific purpose, we
	 * have several checks in the beginning of the method to ensure we actually need to schedule the
	 * job to delete the backup.
	 *
	 * @since 1.5.2
	 *
	 * @see self::delete_local()
	 *
	 * @param array $info Archive information.
	 */
	public function post_archive_files( $info ) {
		/*
		 * Do not add a job to delete this local backup if this is not an automated backup.
		 *
		 * We only want to add this to the jobs queue if we're in the middle of an automatic backup.
		 * If the user simply clicked on "Backup site now", we don't want to automatically delete the
		 * backup, there's a button for that.
		 */
		if ( ! $this->core->doing_cron ) {
			return;
		}

		/*
		 * If the user wants to keep backups locally, there's no need to delete it via a job. It will
		 * get deleted in time during the retention process.
		 */
		if ( $this->core->remote->is_enabled( 'local' ) ) {
			return;
		}

		/*
		 * At this point, we know they don't have local storage enabled, so we need to delete ths backup
		 * via a job to respect those settings.
		 *
		 * HOWEVER, if the user ALSO doesn't have any REMOTE storage providers enabled, then in essence
		 * we've created a local backup only to delete it right away.
		 *
		 * To protect users from themselves, in the following scenario:
		 *
		 * 1. If the user DOES NOT have local storage enabled (which is true as we've progressed this
		 *    far into the method) AND
		 * 2. They DO NOT have a remote storage provider enabled...
		 *
		 * ... Abort and do not add the job to remove the local backup. Doing so will mean that we
		 * creating a backup before an auto update, but we never uploaded it remotely and we deleted
		 * it locally, which seems pointless.
		 *
		 * INSTEAD, we'll have this case (the lesser of 2 evils):
		 *
		 * 1. The user will have enabled backups before auto updates.
		 * 2. The user will have disabled local storage.
		 * 3. The user will not have any remote storage enabled.
		 */
		if ( ! $this->core->remote->any_enabled( true ) ) {
			return;
		}

		$args = array(
			'filepath'     => $info['filepath'],
			'action'       => 'boldgrid_backup_delete_local',
			'action_data'  => $info['filepath'],
			'action_title' => __( 'Delete backup from Web Server', 'boldgrid-backup' ),
		);

		$this->core->jobs->add( $args );
	}

	/**
	 * Add submenu pages.
	 *
	 * @since 1.7.0
	 */
	public function add_submenus() {
		add_submenu_page(
			'boldgrid-backup-settings',
			__( 'Web Server', 'boldgrid-backup' ),
			__( 'Web Server', 'boldgrid-backup' ),
			'administrator',
			'boldgrid-backup-web-server',
			array(
				$this,
				'webserver_subpage',
			)
		);
	}

	/**
	 * Generate the submenu page for the Web Server settings page.
	 *
	 * @since 1.7.0
	 */
	public function webserver_subpage() {
		wp_enqueue_style( 'boldgrid-backup-admin-hide-all' );
		wp_enqueue_style( 'bglib-ui-css' );
		wp_enqueue_script( 'bglib-ui-js' );
		wp_enqueue_script( 'bglib-sticky' );

		wp_enqueue_script(
			'boldgrid-backup-admin-settings',
			plugin_dir_url( dirname( __FILE__ ) ) . 'js/boldgrid-backup-admin-settings.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$this->webserver_subpage_save();

		$settings = $this->core->settings->get_settings();

		include BOLDGRID_BACKUP_PATH . '/admin/partials/remote/local.php';
	}

	/**
	 * Process the user's request to update their Amazon S3 settings.
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Admin_Test::run_functionality_tests()
	 * @see Boldgrid_Backup_Admin_Settings::get_settings()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::create()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::is_valid()
	 *
	 * @uses $_POST[] Settings.
	 *
	 * @return bool
	 */
	public function webserver_subpage_save() {
		if ( empty( $_POST ) || ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if ( ! check_ajax_referer( 'bgb-settings-webserver', 'webserver_auth' ) ) {
			do_action(
				'boldgrid_backup_notice',
				__( 'Unauthorized request.', 'boldgrid-backup' ),
				'notice error is-dismissible'
			);

			return false;
		}

		if ( ! $this->core->test->run_functionality_tests() ) {
			$this->core->settings->errors[] = sprintf(
				// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
				__(
					'Functionality test has failed.  You can go to %1$sFunctionality Test%2$s to view a report.',
					'boldgrid-backup'
				),
				'<a href="' . admin_url( 'admin.php?page=boldgrid-backup-test' ) . '">',
				'</a>'
			);
		} else {
			$settings = $this->core->settings->get_settings();

			$original_backup_directory = $settings['backup_directory'];

			// For consistency, untrailingslashit the input backup dir, or use the default.
			$settings['backup_directory'] = ! empty( $_POST['backup_directory'] ) ?
				untrailingslashit(
					str_replace( '\\\\', '\\', trim( $_POST['backup_directory'] ) )
				) : $this->core->backup_dir->get();

			$this->core->backup_dir->create( $settings['backup_directory'] );

			if ( ! $this->core->backup_dir->is_valid( $settings['backup_directory'] ) ) {
				$this->core->settings->errors[] = __( 'Invalid backup directory', 'boldgrid-backup' );
			} elseif ( $original_backup_directory !== $settings['backup_directory'] &&
				isset( $_POST['move-backups'] ) && 'on' === $_POST['move-backups'] ) {
					// Move backups to the new directory, if changed and opted.
					$backups_moved = $this->core->settings->move_backups(
						$original_backup_directory,
						$settings['backup_directory']
					);

				if ( ! $backups_moved ) {
					$this->core->settings->errors[] = sprintf(
						// translators: 1: Original backup directory, 2: New backup directory.
						__(
							'Unable to move backups from %1$s to %2$s',
							'boldgrid-backup'
						),
						$original_backup_directory,
						$settings['backup_directory']
					);
				}
			}

			$settings['retention_count'] = (
				! empty( $_POST['retention_count'] ) ?
					(int) $_POST['retention_count'] : $this->core->config->get_default_retention()
			);
		}

		$success = empty( $this->core->settings->errors );

		if ( ! $success ) {
			do_action( 'boldgrid_backup_notice', implode( '<br /><br />', $this->core->settings->errors ) );
		} else {
			update_site_option( 'boldgrid_backup_settings', $settings );

			do_action(
				'boldgrid_backup_notice',
				__( 'Settings saved.', 'boldgrid-backup' ),
				'notice updated is-dismissible'
			);
		}

		return $success;
	}

	/**
	 * Are the web server settings configured?
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Admin_Settings::get_settings
	 * @see Boldgrid_Backup_Admin_Backup_Dir::is_valid
	 *
	 * @return bool
	 */
	public function is_webserver_setup() {
		$settings            = $this->core->settings->get_settings();
		$has_valid_dir       = $this->core->backup_dir->is_valid( $settings['backup_directory'] );
		$has_valid_retention = is_int( $settings['retention_count'] ) &&
			0 < $settings['retention_count'];

		return $has_valid_dir && $has_valid_retention;
	}

	/**
	 * Determine if local web server storage is setup/configured.
	 *
	 * @since 1.7.0
	 *
	 * @see self::is_webserver_setup()
	 * @see self::get_webserver_details()
	 */
	public function is_setup_ajax() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if ( ! check_ajax_referer( 'boldgrid_backup_settings', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$location = $this->get_webserver_details();

		$tr = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage-location.php';

		if ( $this->is_webserver_setup() ) {
			wp_send_json_success( $tr );
		} else {
			wp_send_json_error( $tr );
		}
	}

	/**
	 * Get the web server storage details.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public function get_webserver_details() {
		$settings = $this->core->settings->get_settings();

		return array(
			'title'     => __( 'Web Server', 'boldgrid-backup' ),
			'key'       => 'local',
			'configure' => 'admin.php?page=boldgrid-backup-web-server',
			'is_setup'  => $this->is_webserver_setup(),
			'enabled'   => ! empty( $settings['remote']['local']['enabled'] ) &&
				true === $settings['remote']['local']['enabled'],
		);
	}
}
