<?php
/**
 * File: class-boldgrid-backup-admin-ftp-hooks.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/remote
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/*
 * AJAX callback functions in this class have their nonce verified by validate_nonce() in the Boldgrid_Backup_Admin_Archive_Details class.
 *
 * phpcs:disable WordPress.VIP, WordPress.CSRF.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.NoNonceVerification
 */

/**
 * Class: Boldgrid_Backup_Admin_Ftp_Hooks
 *
 * The only purpose this class is to be used for is to separate methods that are
 * used for registering a new remove provider. All of these methods are called
 * via hooks.
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Ftp_Hooks {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

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
	 * Add menu items.
	 *
	 * @since 1.6.0
	 */
	public function add_menu_items() {
		$capability = 'administrator';

		add_submenu_page(
			'boldgrid-backup-settings',
			__( 'FTP Settings', 'boldgrid-backup' ),
			__( 'FTP Settings', 'boldgrid-backup' ),
			$capability,
			'boldgrid-backup-ftp',
			array(
				$this->core->ftp->page,
				'settings',
			)
		);
	}

	/**
	 * Hook into the filter to add all ftp backups to the full list of backups.
	 *
	 * @since 1.6.0
	 */
	public function filter_get_all() {
		$contents = $this->core->ftp->get_contents( true, $this->core->ftp->get_folder_name() );
		$contents = $this->core->ftp->format_raw_contents( $contents );

		foreach ( $contents as $item ) {
			$filename = $item['filename'];

			$backup = array(
				'filename'      => $filename,
				'last_modified' => $item['time'],
				'size'          => $item['size'],
				'locations'     => array(
					array(
						'title'            => $this->core->ftp->nickname,
						'on_remote_server' => true,
						'title_attr'       => $this->core->ftp->title_attr,
					),
				),
			);

			$this->core->archives_all->add( $backup );
		}
	}

	/**
	 * Determine if FTP is setup.
	 *
	 * @since 1.6.0
	 */
	public function is_setup_ajax() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if ( ! check_ajax_referer( 'boldgrid_backup_settings', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$settings = $this->core->settings->get_settings();

		$location = $this->core->ftp->get_details();
		$tr       = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage-location.php';

		if ( $this->core->ftp->is_setup() ) {
			wp_send_json_success( $tr );
		} else {
			wp_send_json_error( $tr );
		}
	}

	/**
	 * Actions to take after a backup file has been generated.
	 *
	 * @since 1.6.0
	 *
	 * @param array $info Archive information.
	 */
	public function post_archive_files( $info ) {
		/*
		 * We only want to add this to the jobs queue if we're in the middle of
		 * an automatic backup. If the user simply clicked on "Backup site now",
		 * we don't want to automatically send the backup to Amazon, there's a
		 * button for that.
		 */
		if ( ! $this->core->doing_cron ) {
			return;
		}

		if ( ! $this->core->remote->is_enabled( $this->core->ftp->key ) || $info['dryrun'] || ! $info['save'] ) {
			return;
		}

		$args = array(
			'filepath'     => $info['filepath'],
			'action'       => 'boldgrid_backup_' . $this->core->ftp->key . '_upload_post_archive',
			'action_data'  => $info['filepath'],
			// translators: 1: FTP account title/name.
			'action_title' => sprintf( __( 'Upload backup file to %1$s', 'boldgrid-backup' ), $this->core->ftp->title ),
		);

		$this->core->jobs->add( $args );
	}

	/**
	 * Register FTP as a storage location.
	 *
	 * @since 1.6.0
	 *
	 * @param array $storage_locations Storage locations.
	 */
	public function register_storage_location( $storage_locations ) {
		$storage_locations[] = $this->core->ftp->get_details( true );

		return $storage_locations;
	}

	/**
	 * Register FTP on the archive details page.
	 *
	 * @since 1.6.0
	 *
	 * @param string $filepath File path.
	 */
	public function single_archive_remote_option( $filepath ) {
		$allow_upload = $this->core->ftp->is_setup();
		$uploaded     = $this->core->ftp->is_uploaded( $filepath );

		$this->core->archive_details->remote_storage_li[] = array(
			'id'           => $this->core->ftp->key,
			'title'        => $this->core->ftp->nickname,
			'title_attr'   => $this->core->ftp->title_attr,
			'uploaded'     => $uploaded,
			'allow_upload' => $allow_upload,
			'is_setup'     => $this->core->ftp->is_setup(),
		);
	}

	/**
	 * Hook into WordPress' shutdown action and close any open FTP connections.
	 *
	 * Closing the connections now, rather than when we're done with a specific ftp action, will
	 * eliminate numerous ftp connections being opened on a single admin page.
	 *
	 * @since 1.7.2
	 */
	public function shutdown() {
		$this->core->ftp->disconnect();
	}

	/**
	 * Upload a file (triggered by jobs queue).
	 *
	 * The jobs queue will call this method to upload a file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filepath File path.
	 */
	public function upload_post_archiving( $filepath ) {
		$success = $this->core->ftp->upload( $filepath );

		return $success;
	}

	/**
	 * Handle the ajax request to download an FTP backup locally.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_download() {
		$error = __( 'Unable to download backup from FTP', 'bolgrid-bakcup' );

		$allowed_html = array(
			'h2'     => array(),
			'p'      => array(),
			'strong' => array(),
		);

		// Validation, user role.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$this->core->notice->add_user_notice(
				sprintf( $error . ': ' . __( 'Permission denied.', 'boldgrid-backup' ) ),
				'notice notice-error'
			);
			wp_send_json_error();
		}

		// Validation, nonce.
		if ( ! $this->core->archive_details->validate_nonce() ) {
			$this->core->notice->add_user_notice(
				sprintf( $error . ': ' . __( 'Invalid nonce.', 'boldgrid-backup' ) ),
				'notice notice-error'
			);
			wp_send_json_error();
		}

		// Validation, $_POST data.
		$filename = ! empty( $_POST['filename'] ) ?
			sanitize_file_name( $_POST['filename'] ) : false;

		if ( empty( $filename ) ) {
			$this->core->notice->add_user_notice(
				sprintf( $error . ': ' . __( 'Invalid filename.', 'boldgrid-backup' ) ),
				'notice notice-error'
			);
			wp_send_json_error();
		}

		$result = $this->core->ftp->download( $filename );

		if ( $result ) {
			$this->core->notice->add_user_notice(
				wp_kses(
					sprintf(
						// translators: 1: Plugin title,2: Filename.
						__(
							'<h2>%1$s - FTP Download</h2><p>Backup file <strong>%2$s</strong> successfully downloaded from FTP.</p>',
							'boldgrid-backup'
						),
						$filename
					), $allowed_html
				),
				BOLDGRID_BACKUP_TITLE . ' Premium',
				'notice notice-success'
			);
			wp_send_json_success();
		}
	}

	/**
	 * Upload a file (triggered by ajax).
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_upload() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if ( ! $this->core->archive_details->validate_nonce() ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$filename = ! empty( $_POST['filename'] ) ?
			sanitize_file_name( $_POST['filename'] ) : false;

		$filepath = $this->core->backup_dir->get_path_to( $filename );

		if ( empty( $filename ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( __( 'Invalid archive filepath.', 'boldgrid-backup' ) );
		}

		$uploaded = $this->core->ftp->upload( $filepath );

		if ( $uploaded ) {
			wp_send_json_success( 'uploaded!' );
		} else {
			$error = ! empty( $this->core->ftp->errors ) ? implode( '<br /><br />', $this->core->ftp->errors ) : '';
			wp_send_json_error( $error );
		}
	}
}
