<?php
/**
 * File: class-boldgrid-backup-admin-archive-actions.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archive_Actions
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Archive_Actions {

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
	 * Enqueue scripts.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {
		$access_type         = get_filesystem_method();
		$archive_nonce       = wp_create_nonce( 'archive_auth' );
		$delete_confirm_text = __(
			'Please confirm the deletion of the archive file:',
			'boldgrid-backup'
		) . PHP_EOL;

		// translators: 1: Archive filename.
		$restore_confirm_text = __(
			"Please confirm the restoration of this WordPress installation from the archive file:\n\"%s\"\n\nPlease be aware that you may get logged-out if your session token does not exist in the database restored.",
			'boldgrid-backup'
		);

		$link_error_text      = __( 'Could not generate link.', 'boldgrid-backup' );
		$unknown_error_text   = __( 'Unknown error.', 'boldgrid-backup' );
		$copy_text            = __( 'Copy Link', 'boldgrid-backup' );
		$copied_text          = __( 'Copied!', 'boldgrid-backup' );
		$expires_text         = __( 'This link expires in:', 'boldgrid-backup' );
		$link_disclaimer_text = __(
			'Please keep this link private, as the download file contains sensitive data.',
			'boldgrid-backup'
		);
		$token_mismatch_text  = __(
			'The database was encrypted with a token that does not match the one saved in your settings and cannot be imported.  In order to restore the encrypted database, the matching encryption token is required.  If you have the matching token, then go to the Backup Security settings page to save it.',
			'boldgrid-backup'
		);

		$handle = 'boldgrid-backup-admin-archive-actions';

		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/' . $handle . '.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$translation = array(
			'accessType'         => $access_type,
			'archiveNonce'       => $archive_nonce,
			'deleteConfirmText'  => $delete_confirm_text,
			'restoreConfirmText' => $restore_confirm_text,
			'linkErrorText'      => $link_error_text,
			'unknownErrorText'   => $unknown_error_text,
			'copyText'           => $copy_text,
			'copiedText'         => $copied_text,
			'expiresText'        => $expires_text,
			'linkDisclaimerText' => $link_disclaimer_text,
			'tokenMismatchText'  => $token_mismatch_text,
		);

		wp_localize_script( $handle, 'BoldGridBackupAdminArchiveActions', $translation );
		wp_enqueue_script( $handle );

		wp_enqueue_script( 'clipboard' );
	}

	/**
	 * Return a link to delete an archive.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename Filename.
	 * @return string
	 */
	public function get_delete_link( $filename ) {
		$archive = $this->core->archive->get_by_name( $filename );

		if ( empty( $archive ) ) {
			$link = '';
		} else {
			$link = sprintf(
				'
				<form method="post" id="delete-action" >
					<input type="hidden" name="delete_now" value="1" />
					<input type="hidden" name="archive_key" value="%2$s" />
					<input type="hidden" name="archive_filename" value="%3$s" />
					%4$s
					<a href="" class="submitdelete" data-key="%2$s" data-filename="%3$s">%5$s</a>
					<span class="spinner"></span>
				</form>',
				/* 1 */ get_admin_url( null, 'admin.php?page=boldgrid-backup-archive-details' ),
				/* 2 */ $archive['key'],
				/* 3 */ $archive['filename'],
				/* 4 */ wp_nonce_field( 'archive_auth', 'archive_auth', true, false ),
				/* 5 */ __( 'Delete backup', 'boldgrid-backup' )
			);
		}

		return $link;
	}

	/**
	 * Return a link to download an archive.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename Filename.
	 * @return string
	 */
	public function get_download_button( $filename ) {
		$archive = $this->core->archive->get_by_name( $filename );

		if ( empty( $archive ) ) {
			$button = '';
		} else {
			$button = sprintf(
				'
				<a
					id="backup-archive-download-%1$s"
					class="button button-primary action-download"
					href="#"
					data-key="%1$s"
					data-filepath="%2$s"
					data-filename="%3$s">
					%4$s
				</a>',
				/* 1 */ $archive['key'],
				/* 2 */ $archive['filepath'],
				/* 3 */ $archive['filename'],
				/* 4 */ __( 'Download to Local Machine', 'boldgrid-backup' )
			);
		}

		return $button;
	}

	/**
	 * Return a link to restore an archive.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename Filename.
	 * @param  array  $args     Arguments for the link/button.
	 * @return string
	 */
	public function get_restore_button( $filename, $args = [] ) {
		$defaults = [
			'button_text' => __( 'Restore', 'boldgrid-backup' ),
		];

		$args = wp_parse_args( $args, $defaults );

		$archive = $this->core->archive->get_by_name( $filename );

		if ( empty( $archive ) ) {
			$button = '';
		} else {
			$button = sprintf(
				'
				<a
					data-restore-now="1"
					data-archive-key="%1$s"
					data-archive-filename="%2$s"
					data-nonce="%3$s"
					class="button restore-now"
					href="">
					%4$s
				</a>
				%5$s',
				/* 1 */ $archive['key'],
				/* 2 */ $filename,
				/* 3 */ wp_create_nonce( 'boldgrid_backup_restore_archive' ),
				/* 4 */ esc_html( $args['button_text'] ),
				/* 5 */ $this->core->lang['spinner']
			);
		}

		return $button;
	}

	/**
	 * Return a button link to request to generate a public link to download an archive file.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $filename Filename.
	 * @return string
	 */
	public function get_download_link_button( $filename ) {
		$link    = '';
		$archive = $this->core->archive->get_by_name( $filename );

		if ( ! empty( $archive ) ) {
			$link = sprintf(
				'<a
					id="download-link-button"
					class="button"
					href="#"
					data-filename="%1$s"
					data-nonce="%2$s"
					>
					%3$s
				</a>
				<span class="spinner"></span>
				',
				/* 1 */ $archive['filename'],
				/* 2 */ wp_create_nonce( 'boldgrid_backup_download_link' ),
				/* 3 */ __( 'Get Download Link', 'boldgrid-backup' )
			);
		}

		return $link;
	}

	/**
	 * Callback function for generating a public link to download an archive file.
	 *
	 * Used on the backup archive details page.  The link is only valid for a limited time, which
	 * is configurable in a configuration file.
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Admin_Archive::generate_download_link()
	 *
	 * @uses $_POST['archive_filename'] Backup archive filename.
	 *
	 * @return string
	 */
	public function wp_ajax_generate_download_link() {
		$archive_filename = ! empty( $_POST['archive_filename'] ) ?
			sanitize_file_name( $_POST['archive_filename'] ) : null;

		if ( check_admin_referer( 'boldgrid_backup_download_link', 'archive_auth' ) &&
			current_user_can( 'update_plugins' ) && $archive_filename ) {
				wp_send_json_success(
					$this->core->archive->generate_download_link(
						$archive_filename
					)
				);
		} else {
			wp_send_json_error();
		}
	}
}
