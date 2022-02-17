<?php
/**
 * File: class-boldgrid-backup-admin-upload.php
 *
 * @link https://www.boldgrid.com
 * @since 1.2.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Upload
 *
 * @since 1.2.2
 */
class Boldgrid_Backup_Admin_Upload {
	/**
	 * The core class object.
	 *
	 * @since 1.2.2
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.2.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		$this->core = $core;
	}

	/**
	 * Verify upload archive access and validate input.
	 *
	 * @since 1.2.2
	 *
	 * @see current_user_can() in wp-includes/capabilities.php
	 * @see wp_verify_nonce() in wp-includes/pluggable.php
	 * @see Boldgrid_Backup_Admin_Utility::translate_upload_error()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 *
	 * @return bool
	 */
	public function verify_upload_access() {
		// Verify capability.
		if ( ! current_user_can( 'upload_files' ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Security violation (not authorized).', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Verify the WordPress nonce.
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'upload_archive_file' ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Abort if upload was not sent from our form.
		if ( empty( $_POST['uploading'] ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Upload file was not send from the proper form.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Abort if there is no file upload.
		if ( empty( $_FILES['file'] ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'File upload error.  Please try again.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Abort if the files was not uploaded via HTTP POST.
		if ( ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'File upload error.  Please try again.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

			// Abort with a notice if there was an upload error.
		if ( $_FILES['file']['error'] ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				Boldgrid_Backup_Admin_Utility::translate_upload_error( $_FILES['file']['error'] ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Get the backup directory.
		$backup_directory = $this->core->backup_dir->get();

		// Abort if the backup directory is not configured.
		if ( empty( $backup_directory ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The backup directory is not configured.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		if ( ! preg_match( '/boldgrid-backup-.*-\d{8}-\d{6}/', $_FILES['file']['name'] ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Uploaded File is not a Total Upkeep backup file.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		return true;
	}

	/**
	 * Bump upload limits.
	 *
	 * Set PHP INI runtime settings to allow an upload up to the specified amount.
	 * Default is 1G.
	 *
	 * @since 1.2.2
	 *
	 * @see Boldgrid_Backup_Admin_Utility::bump_upload_limit()
	 * @see Boldgrid_Backup_Admin_Utility::bump_memory_limit()
	 * @see Boldgrid_Backup_Admin_Utility::bump_max_execution()
	 *
	 * @param string $limit A php.ini style string.
	 */
	public function bump_upload_limits( $limit = '1G' ) {
		Boldgrid_Backup_Admin_Utility::bump_upload_limit( $limit );
		Boldgrid_Backup_Admin_Utility::bump_memory_limit( $limit );
		Boldgrid_Backup_Admin_Utility::bump_max_execution( '0' );
	}

	/**
	 * Check filetype and extension.
	 *
	 * @since 1.2.2
	 *
	 * @see wp_check_filetype_and_ext() in wp-includes/functions.php
	 *
	 * @return bool
	 */
	public function check_filetype_ext() {
		// Validate input.
		if ( empty( $_FILES['file'] ) ) {
			return false;
		}

		// Get the upload file basename.
		$file_basename = basename( $_FILES['file']['name'] );

		// Validate the filename and mime type.
		$validate = wp_check_filetype_and_ext( $_FILES['file']['tmp_name'], $file_basename );

		// Abort if the file is an incorrect extension.
		// Currently only "zip"; others to be added in the future.
		// @todo Write a method to get the allowed file extensions, based on available compressors.
		$allowed_file_ext = array(
			'zip',
		);

		if ( ! in_array( $validate['ext'], $allowed_file_ext, true ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				sprintf(
					// translators: 1: File extension.
					esc_html__(
						'Upload file extension type %s is not allowed.',
						'boldgrid-backup'
					),
					( ! empty( $validate['ext'] ) ? '"' . $validate['ext'] . '"' : '' )
				),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		return true;
	}

	/**
	 * Get file save path and update the base filename.
	 *
	 * @since 1.2.2
	 *
	 * @see Boldgrid_Backup_Admin_Config::get_backup_identifier()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 *
	 * @param  string $filename Filename.
	 * @return string
	 */
	public function get_save_path( $filename ) {
		$backup_identifier = $this->core->get_backup_identifier();

		// Ensure that the input filename is a basename and remove any query string.
		$filename = preg_replace( '/\?.*$/', '', basename( $filename ) );

		// Create an array of strings to remove from the filename.
		$remove_strings = array(
			'boldgrid-backup-',
			$backup_identifier,
			'uploaded-',
			'admin-ajax.php',
			'.zip',
		);

		// Remove references from filename.
		foreach ( $remove_strings as $remove_string ) {
			$filename = str_replace( $remove_string, '', $filename );
		}

		// If the filename is now empty, then make is a unix timestamp.
		if ( empty( $filename ) ) {
			$filename = current_time( 'timestamp', true );
		}

		// Reformat the filename.
		$filename = 'boldgrid-backup-' . $backup_identifier . '-uploaded-' . $filename . '.zip';

		// Remove extra dashes.
		$filename = preg_replace( '#-+#', '-', $filename );

		$backup_directory = $this->core->backup_dir->get();

		// Return the full file path.
		return $backup_directory . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Handle upload.
	 *
	 * @since 1.2.2
	 *
	 * @see wp_handle_upload() in wp-admin/includes/file.php
	 *
	 * @return array The results of wp_handle_upload().
	 */
	public function handle_upload() {
		// Ensure that "wp-admin/includes/file.php" is loaded for wp_handle_upload().
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Use wp_handle_upload() (with overrides and custom options), to perform the actual upload.
		$upload_overrides = array(
			'test_form' => false,
		);

		// Add a filter to specify a custom upload directory (the backup directory).
		add_filter(
			'upload_dir',
			array(
				$this->core->config,
				'custom_upload_dir',
			)
		);

		$movefile = wp_handle_upload( $_FILES['file'], $upload_overrides );

		// Remove the temporary filter for a custom upload directory.
		remove_filter(
			'upload_dir',
			array(
				$this->core->config,
				'custom_upload_dir',
			)
		);

		return $movefile;
	}

	/**
	 * Upload archive file.
	 *
	 * The index used for $_FILES should be "file".
	 *
	 * @since 1.2.2
	 *
	 * @see Boldgrid_Backup_Admin_Upload::verify_upload_access()
	 * @see Boldgrid_Backup_Admin_Upload::bump_limits()
	 *
	 * @return bool Success of the operation.
	 */
	public function upload_archive_file() {
		// Verify upload archive access and validate input.
		if ( ! $this->verify_upload_access() ) {
			return false;
		}

		// Close any PHP session, so another session can open during the upload.
		session_write_close();

		// Set PHP INI runtime settings to allow an upload up to 1G.
		$this->bump_upload_limits( '1G' );

		// Validate the filename and mime type.
		if ( ! $this->check_filetype_ext() ) {
			return false;
		}

		// Create the file save path, and update the destination base filename..
		$file_save_path = $this->get_save_path( $_FILES['file']['name'] );

		// Update the filename.
		$_FILES['file']['name'] = basename( $file_save_path );

		// Handle the upload.
		$movefile = $this->handle_upload();

		// Determine success and produce and admin notice.
		if ( $movefile && ! isset( $movefile['error'] ) ) {
			// Modify the archive file permissions to help protect from public access.
			Boldgrid_Backup_Admin_Utility::chmod( $file_save_path, 0600 );

			// Display an success notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Upload successful.', 'boldgrid-backup' ),
				'notice notice-success is-dismissible'
			);

			// Enforce retention setting.
			$this->core->enforce_retention();

			return true;
		} else {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				sprintf(
					// translators: 1: Error message.
					esc_html__( 'Upload has failed; %s.', 'boldgrid-backup' ),
					$movefile['error']
				),
				'notice notice-error is-dismissible'
			);

			return false;
		}
	}

	/**
	 * Callback function for importing a backup archive file via URL address.
	 *
	 * Used on the backup archives page.
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get_path_to()
	 * @see Boldgrid_Backup_Admin_Archive_Log::path_from_zip()
	 * @see Boldgrid_Backup_Admin_Archive_Log::restore_by_zip()
	 * @see Boldgrid_Backup_Admin_Remote::post_download()
	 *
	 * @uses $_POST['url'] URL address.
	 */
	public function ajax_url_import() {
		$logger = new Boldgrid_Backup_Admin_Log( $this->core );
		$logger->init( 'transfer-archive.log' );
		$logger->add_separator();
		$logger->add( 'Beginning ajax_url_import...' );

		// Check user permissions.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'User access violation!', 'boldgrid-backup' ),
				)
			);
		}

		// Check security nonce and referer.
		if ( ! check_admin_referer( 'upload_archive_file' ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Security violation! Please try again.', 'boldgrid-backup' ),
				)
			);
		}

		$url = ! empty( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : null;

		$archive_fetcher = new Boldgrid_Backup_Archive_Fetcher( $url );
		$archive_fetcher->download();

		if ( $archive_fetcher->has_error() ) {
			wp_send_json_error( [ 'error' => $archive_fetcher->get_error() ] );
		} else {
			wp_send_json_success( $archive_fetcher->get_info() );
		}
	}

	/**
	 * Archive Upload Action
	 *
	 * @since 1.14.0
	 */
	public function archive_upload_action() {
		$page_is_bgbkup = apply_filters( 'is_boldgrid_backup_page', null );
		if ( $page_is_bgbkup && ! empty( $_FILES['file'] ) ) {
			$this->core->upload->upload_archive_file();
		}
	}
}
