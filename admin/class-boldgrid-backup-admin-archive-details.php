<?php
/**
 * File: class-boldgrid-backup-admin-archive-details.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Archive_Details
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Details {
	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of remote storage locations.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    array
	 */
	public $remote_storage_li = array();

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get a link for an archive's details page.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename Filename.
	 * @return string
	 */
	public function get_url( $filename ) {
		return get_admin_url( null, 'admin.php?page=boldgrid-backup-archive-details&filename=' . $filename );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'boldgrid-backup-admin-zip-browser',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-zip-browser.css',
			array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		wp_register_script(
			'boldgrid-backup-admin-archive-details',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-archive-details.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION
		);
		$translations = array(
			'uploading'  => __( 'Uploading', 'boldgrid-backup' ),
			'uploaded'   => __( 'Uploaded', 'boldgrid-backup' ),
			'failUpload' => __( 'Unable to upload backup file.', 'boldgrid-backup' ),
		);
		wp_localize_script( 'boldgrid-backup-admin-archive-details', 'boldgrid_backup_archive_details', $translations );
		wp_enqueue_script( 'boldgrid-backup-admin-archive-details' );

		wp_register_script(
			'boldgrid-backup-admin-zip-browser',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-zip-browser.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION
		);
		$unknown_error = __( 'An unknown error has occurred.', 'boldgrid-backup' );
		$translations  = array(
			'loading'            => __( 'Loading', 'boldgrid-backup' ),
			'home'               => __( 'Home', 'boldgrid-backup' ),
			'restoring'          => __( 'Restoring', 'boldgrid-backup' ),
			'confirmDbRestore'   => __( 'Are you sure you want to restore this database backup?', 'boldgrid-backup' ),
			'unknownBrowseError' => __( 'An unknown error has occurred when trying to get a listing of the files in this archive.', 'boldgrid-backup' ),
			'unknownError'       => $unknown_error,
			'unknownErrorNotice' => sprintf( '<div class="%1$s"><p>%2$s</p></div>', $this->core->notice->lang['dis_error'], $unknown_error ),
		);
		wp_localize_script( 'boldgrid-backup-admin-zip-browser', 'boldgrid_backup_zip_browser', $translations );
		wp_enqueue_script( 'boldgrid-backup-admin-zip-browser' );

		wp_enqueue_style( 'bglib-ui-css' );

		wp_enqueue_script( 'bglib-attributes-js' );
		wp_enqueue_style( 'bglib-attributes-css' );

		/**
		 * Allow other plugins to enqueue scripts on this page.
		 *
		 * @since 1.5.3
		 */
		do_action( 'boldgrid_backup_enqueue_archive_details' );
	}

	/**
	 * Render the details page of an archive.
	 *
	 * @since 1.5.1
	 */
	public function render_archive() {
		if ( ! empty( $_POST['delete_now'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$this->core->delete_archive_file();
		}

		$this->enqueue_scripts();
		$this->core->archive_actions->enqueue_scripts();
		$this->core->auto_rollback->enqueue_home_scripts();

		$archive_found = false;

		$filename = ! empty( $_GET['filename'] ) ? sanitize_file_name( $_GET['filename'] ) : false; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( ! $filename ) {
			esc_html_e( 'No archive specified.', 'boldgrid-backup' );
			return;
		}

		// Get our archive.
		$archive = $this->core->archive->get_by_name( $filename );
		if ( $archive ) {
			$log           = $this->core->archive_log->get_by_zip( $archive['filepath'] );
			$archive       = array_merge( $log, $archive );
			$archive_found = true;
			$dump_file     = $this->core->get_dump_file( $archive['filepath'] );
		} else {
			$archive = array(
				'filename' => $filename,
				'filepath' => $this->core->backup_dir->get_path_to( $filename ),
			);
		}

		// Initialize the archive. We will need it in our included template below.
		$this->core->archive->init( $archive['filepath'] );
		$title       = $this->core->archive->get_attribute( 'title' );
		$description = $this->core->archive->get_attribute( 'description' );

		$settings = $this->core->settings->get_settings();
		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );
		wp_enqueue_style( 'bglib-ui-css' );

		$in_modal = true;
		$modal    = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
		$in_modal = false;

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html__( 'Total Upkeep Archive Details', 'boldgrid-backup' ) . '</h1>
						<div class="page-title-actions">
						<a href="#TB_inline?width=800&amp;height=600&amp;inlineId=backup_now_content" class="thickbox page-title-action page-title-action-primary">' .
							esc_html__( 'Backup Site Now', 'boldgrid-backup' ) . '
						</a>
						<a class="page-title-action add-new">' . esc_html__( 'Upload Backup', 'boldgrid-backup' ) . '</a>
					</div>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		echo $modal; //phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-archive-details.php';
		echo '
				</div>
			</div>
		</div>';
	}

	/**
	 * Validate the nonce on the Backup Archive Details page.
	 *
	 * On the backup archive page, there is a nonce used by several different
	 * methods, bgbkup_archive_details_page. This method is an easy
	 * way to validate the nonce.
	 *
	 * The boldgrid_backup_remote_storage_upload nonce is also used on this page. If ever consolidating
	 * these two nonces into one, remember backwards compatibility + premium plugin. One click Google
	 * Drive / etc uploads uses this nonce.
	 *
	 * @since 1.6.0
	 *
	 * @see Boldgrid_Backup_Admin_Archive_Browser::authorize()
	 */
	public function validate_nonce() {
		return check_ajax_referer( 'bgbkup_archive_details_page', 'security', false ) ||
			check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false );
	}

	/**
	 * Handle the ajax request to "Update" from a backup archive page.
	 *
	 * @since 1.7.0
	 */
	public function wp_ajax_update() {
		if ( ! $this->validate_nonce() ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		$attributes = ! empty( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ? $_POST['attributes'] : []; // phpcs:ignore WordPress.CSRF.NonceVerification
		$filename   = ! empty( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : false; // phpcs:ignore WordPress.CSRF.NonceVerification
		$filepath   = $this->core->backup_dir->get_path_to( $filename );

		if ( empty( $filename ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( __( 'Invalid archive filepath.', 'boldgrid-backup' ) );
		}

		$this->core->archive->init( $filepath );

		foreach ( $attributes as $key => $value ) {
			$this->core->archive->set_attribute( $key, stripslashes( $value ) );
		}

		// Take action if we've updated either the backup's title or description.
		$title_description_update = ! empty( $attributes['title'] ) || ! empty( $attributes['description'] );

		if ( $title_description_update && isset( $this->core->activity ) ) {
			$this->core->activity->add( 'update_title_description', 1, $this->core->rating_prompt_config );
		}

		wp_send_json_success();
	}
}
