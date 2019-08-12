<?php
/**
 * File: destination.php
 *
 * Show "Destnation" on transfers page.
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/transfers
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

return sprintf(
	'<div class="bgbkup-transfers-destination">
		<h2>%1$s</h2>
		<p>%2$s</p>
		<p>%3$s</p>
		<div id="url-import-section" class="wp-upload-form">
			%4$s <input type="text" name="url" placeholder="%5$s" size="30" />
			<input class="button" type="submit" value="%6$s" />
			<span class="spinner"></span>
			<div id="url-import-notice" class="notice notice-success inline"></div>
			%7$s
		</div>
	</div>',
	esc_html__( 'Use this section if you want to select this WordPress installation as the destination.', 'boldgrid_backup' ),
	esc_html__( 'Retrieve a download link from BoldGrid Backup on another WordPress installation, paste the link in form below, and click "Upload".  Once the download completes, you can either inspect the backup files and database or click "Restore".', 'boldgrid_backup' ),
	esc_html__( 'Note: Performing a restoration in this installation will replace files and the database contents.', 'boldgrid_backup' ),
	esc_html__( 'Import from a download link:', 'boldgrid-backup' ),
	esc_attr__( 'Download URL address', 'boldgrid-backup' ),
	esc_attr__( 'Upload', 'boldgrid-backup' ),
	wp_nonce_field( 'upload_archive_file' ) // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
);
