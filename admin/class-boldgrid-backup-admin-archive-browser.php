<?php
/**
 * Archive Browser class.
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
 * BoldGrid Backup Admin Archive Browser Class.
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Archive_Browser {

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
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Render html markup for a .sql file.
	 *
	 * When a user is browsing an archive and they click on a .sql file, this
	 * method will create a table showing all the tables in that backup.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath Zip file.
	 * @param  string $file     Sql file name.
	 * @return string
	 */
	public function file_actions_sql( $filepath, $file ) {
		$tables_with_records = $this->core->db_dump->get_insert_count( $filepath, $file );
		$prefixed_tables = $this->core->db_get->prefixed_count();

		$intro = sprintf( __( 'This file, %1$s, is a database dump. The following data is found within:', 'boldgrid-backup' ), $file );
		$in_backup = __( '# Records in this backup', 'boldgrid-backup' );
		$in_current = __( '# Records in current database', 'boldgrid-backup' );

		$return = sprintf( '
			<p>%1$s</p>
			<table class="wp-list-table fixed striped widefat">
			<thead>
				<tr>
					<th>Table</th>
					<th>%2$s</th>
					<th>%3$s</th>
				</tr>
			</thead>
			<tbody>',
			$intro,
			$in_backup,
			$in_current
		);

		foreach( $prefixed_tables as $table => $record_count ) {
			$return .= sprintf(
				'<tr>
					<td>%1$s</td>
					<td>%2$s</td>
					<td>%3$s</td>
				</tr>',
				esc_html( $table ),
				isset( $tables_with_records[$table] ) ? $tables_with_records[$table] : '0',
				esc_html( $record_count )
			);
		}

		$return .= '</tbody></table>';

		return $return;
	}

	/**
	 * Allow the user to browse an archive file.
	 *
	 * Returns a formatted table to the browser.
	 *
	 * @since 1.5.3
	 */
	public function wp_ajax_browse_archive() {
		$error = __( 'Unable to get contents of archive file:', 'boldgrid-backup' );

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( $error . ' ' . __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false ) ) {
			wp_send_json_error( $error . ' ' . __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$filepath = ! empty( $_POST['filepath'] ) ? $_POST['filepath'] : false;
		if( empty( $filepath ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( $error . ' ' . __( 'Invalid archive filepath.', 'boldgrid-backup' ) );
		}

		$dir = ! empty( $_POST['dir'] ) ? $_POST['dir'] : null;

		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );
		$contents = $zip->browse( $filepath, $dir );

		$tr = '';
		$empty_directory = '<tr><td colspan="3">' . __( 'Empty directory', 'boldgrid-backup' ) . '</td></tr>';

		$table = sprintf(
			'<table class="wp-list-table fixed striped remote-storage widefat">
				<thead>
					<tr>
						<th>%1$s</th>
						<th>%2$s</th>
						<th>%3$s</th>
					</tr>
				</thead>
				<tbody>
			',
			__( 'Name', 'boldgrid-backup' ),
			__( 'Size', 'boldgrid-backup' ),
			__( 'Last Modified', 'boldgrid-backup' )
		);

		foreach( $contents as $file ) {
			$tr .= include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser-entry.php';
		}

		$table .= empty( $tr ) ? $empty_directory : $tr;

		$table .= '</tbody></table>';

		wp_send_json_success( $table );
	}

	/**
	 * Show available actions for a single file.
	 *
	 * When the user clicks on a single file in a backup archive, show them
	 * what options they have available.
	 *
	 * @since 1.5.3
	 */
	public function wp_ajax_file_actions() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$filepath = ! empty( $_POST['filepath'] ) ? $_POST['filepath'] : false;
		$file = ! empty( $_POST['file'] ) ? $_POST['file'] : false;
		if( empty( $filepath ) || empty( $file ) ) {
			wp_send_json_error( __( 'Invalid file / filepath.', 'boldgrid-backup' ) );
		}

		$parts = pathinfo( $file );

		// Here's the default message.
		$upgrade_message = __( 'With BoldGrid Backup Premium, you can view and restore files from here.', 'boldgrid-backup' );

		// For other file types, we can do more.
		switch( $parts['extension'] ) {
			case 'sql':
				$upgrade_message = $this->file_actions_sql( $filepath, $file );
				break;
		}

		/**
		 * Allow other plugins to add functionality.
		 *
		 * @since 1.5.3
		 *
		 * @param string $upgrade_message
		 */
		$upgrade_message = apply_filters( 'boldgrid_backup_file_actions', $upgrade_message );

		wp_send_json_success( $upgrade_message );
	}
}
