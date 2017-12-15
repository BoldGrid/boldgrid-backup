<?php
/**
 * FTP Hooks class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * FTP Hooks class.
 *
 * The only purpose this class is to be used for is to separate methods that are
 * used for registering a new remove provider. All of these methods are called
 * via hooks.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Ftp_Hooks {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add menu items.
	 *
	 * @since 1.5.4
	 */
	public function add_menu_items() {
		$capability = 'administrator';

		add_submenu_page(
			null,
			__( 'FTP Settings', 'boldgrid-backup' ),
			__( 'FTP Settings', 'boldgrid-backup' ),
			$capability,
			'boldgrid-backup-ftp',
			array(
				$this->core->ftp,
				'submenu_page',
			)
		);
	}

	/**
	 * Determine if FTP is setup.
	 *
	 * @since 1.5.4
	 */
	public function is_setup_ajax() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_settings', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$settings = $this->core->settings->get_settings();

		$location = $this->core->ftp->get_details();
		$tr = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage-location.php';

		if( $this->core->ftp->is_setup() ) {
			wp_send_json_success( $tr );
		} else {
			wp_send_json_error( $tr );
		}
	}

	/**
	 * Actions to take after a backup file has been generated.
	 *
	 * @since 1.5.4
	 *
	 * @param array $info
	 */
	public function post_archive_files( $info ) {

		/*
		 * We only want to add this to the jobs queue if we're in the middle of
		 * an automatic backup. If the user simply clicked on "Backup site now",
		 * we don't want to automatically send the backup to Amazon, there's a
		 * button for that.
		 */
		if( ! $this->core->doing_cron ) {
			return;
		}

		if( ! $this->core->remote->is_enabled( $this->core->ftp->key ) || $info['dryrun'] || ! $info['save'] ) {
			return;
		}

		$args = array(
			'filepath' => $info['filepath'],
			'action' => 'boldgrid_backup_' . $this->core->ftp->key . '_upload_post_archive',
			'action_data' => $info['filepath'],
			'action_title' => sprintf( __( 'Upload backup file to %1$s', 'boldgrid-backup' ), $this->core->ftp->title ),
		);

		$this->core->jobs->add( $args );
	}

	/**
	 * Register FTP as a storage location.
	 *
	 * @since 1.5.4
	 */
	public function register_storage_location( $storage_locations ) {
		$storage_locations[] = $this->core->ftp->get_details();

		return $storage_locations;
	}

	/**
	 * Register FTP on the archive details page.
	 *
	 * @since 1.5.4
	 *
	 * @param string $filepath
	 */
	public function single_archive_remote_option( $filepath ) {
		$allow_upload = $this->core->ftp->is_setup();
		$uploaded = $this->core->ftp->is_uploaded( $filepath );

		$this->core->archive_details->remote_storage_li[] = array(
			'id' => $this->core->ftp->key,
			'title' => $this->core->ftp->title,
			'uploaded' => $uploaded,
			'allow_upload' => $allow_upload,
		);
	}

	/**
	 * Upload a file (triggered by jobs queue).
	 *
	 * The jobs queue will call this method to upload a file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filepath
	 */
	public function upload_post_archiving( $filepath ) {
		$success = $this->core->ftp->upload( $filepath );

		return $success;
	}

	/**
	 * Upload a file (triggered by ajax).
	 */
	public function wp_ajax_upload() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$filepath = ! empty( $_POST['filepath'] ) ? $_POST['filepath'] : false;
		if( empty( $filepath ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( __( 'Invalid archive filepath.', 'boldgrid-backup' ) );
		}

		$uploaded = $this->core->ftp->upload( $filepath );

		if( $uploaded ) {
			wp_send_json_success( 'uploaded!' );
		} else {
			wp_send_json_error( 'fail' );
		}
	}
}
