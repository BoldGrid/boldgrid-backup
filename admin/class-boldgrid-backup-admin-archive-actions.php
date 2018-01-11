<?php
/**
 * Archive Actions class.
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
 * BoldGrid Backup Admin Archive Actions Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Archive_Actions {

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
	 * Enqueue scripts.
	 *
	 * @since 1.5.4
	 */
	public function enqueue_scripts() {
		$access_type = get_filesystem_method();
		$archive_nonce = wp_create_nonce( 'archive_auth' );
		$delete_confirm_text = esc_html__(
			'Please confirm the deletion of the archive file:' . PHP_EOL,
			'boldgrid-backup'
		);
		$restore_confirm_text = esc_html__(
			'Please confirm the restoration of this WordPress installation from the archive file:' .
			PHP_EOL . '"%s"' . PHP_EOL . PHP_EOL .
			'Please be aware that you may get logged-out if your session token does not exist in the database restored.',
			'boldgrid-backup'
		);

		$handle = 'boldgrid-backup-admin-archive-actions';
		wp_register_script( $handle,
			plugin_dir_url( __FILE__ ) . 'js/' . $handle . '.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$translation = array(
			'accessType' => $access_type,
			'archiveNonce' => $archive_nonce,
			'deleteConfirmText' => $delete_confirm_text,
			'restoreConfirmText' => $restore_confirm_text,
		);
		wp_localize_script( $handle, 'BoldGridBackupAdminArchiveActions', $translation );
		wp_enqueue_script( $handle );
	}

	/**
	 * Return a link to delete an archive.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $filename
	 * @return string
	 */
	public function get_delete_link( $filename ){
		$archive = $this->core->archive->get_by_name( $filename );

		if( empty( $archive ) ) {
			$link = '';
		} else {
			$link = sprintf( '
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
	 * @since 1.5.4
	 *
	 * @param  string $filename
	 * @return string
	 */
	public function get_download_button( $filename ) {
		$archive = $this->core->archive->get_by_name( $filename );

		if( empty( $archive ) ) {
			$button = '';
		} else {
			$button = sprintf( '
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
				/* 4 */ __( 'Download', 'boldgrid-backup' )
			);
		}

		return $button;
	}

	/**
	 * Return a link to restore an archive.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $filename
	 * @return string
	 */
	public function get_restore_button( $filename ) {
		$archive = $this->core->archive->get_by_name( $filename );

		if( empty( $archive ) ) {
			$button = '';
		} else {
			$button = sprintf('
				<a
					data-restore-now="1"
					data-archive-key="%2$s"
					data-archive-filename="%3$s"
					data-nonce="%4$s"
					class="button restore-now"
					href="">
					%5$s
				</a>
				%6$s',
				/* 1 */ get_admin_url( null, 'admin.php?page=boldgrid-backup' ),
				/* 2 */ $archive['key'],
				/* 3 */ $filename,
				/* 4 */ wp_create_nonce( 'boldgrid_backup_restore_archive'),
				/* 5 */ __( 'Restore', 'boldgrid-backup' ),
				/* 6 */ $this->core->lang['spinner']
			);
		}

		return $button;
	}
}
