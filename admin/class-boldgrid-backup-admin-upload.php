<?php
/**
 * The admin-specific core functionality of the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.2.2
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin upload class.
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
			'zip'
		);

		if ( ! in_array( $validate['ext'], $allowed_file_ext, true ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				sprintf(
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
	 * @return string The file save path.
	 */
	public function get_save_path() {
		// Get the upload file basename.
		$file_basename = basename( $_FILES['file']['name'] );

		// Get backup identifier.
		$backup_identifier = $this->core->get_backup_identifier();

		// Get the backup directory.
		$backup_directory = $this->core->backup_dir->get();

		// Create an array of strings to remove from the filename.
		$remove_strings = array(
			'boldgrid-backup-',
			$backup_identifier,
			'uploaded-',
		);

		// Remove references from filename.
		foreach ( $remove_strings as $remove_string ) {
			$file_basename = str_replace( $remove_string, '', $file_basename );
		}

		// Reformat the filename.
		$file_basename = 'boldgrid-backup-' . $backup_identifier . '-uploaded-' . $file_basename;

		// Remove extra dashes.
		$file_basename = preg_replace( '#-+#', '-', $file_basename );

		// Create the file save path.
		$file_save_path = $backup_directory . DIRECTORY_SEPARATOR . $file_basename;

		// Update the base filename.
		$_FILES['file']['name'] = $file_basename;

		return $file_save_path;
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
		add_filter( 'upload_dir',
			array(
				$this->core->config,
				'custom_upload_dir',
			)
		);

		$movefile = wp_handle_upload( $_FILES['file'], $upload_overrides );

		// Remove the temporary filter for a custom upload directory.
		remove_filter( 'upload_dir',
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
		$file_save_path = $this->get_save_path();

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
					esc_html__( 'Upload has failed; %s.', 'boldgrid-backup' ),
					$movefile['error']
				),
				'notice notice-error is-dismissible'
			);

			return false;
		}
	}
}
