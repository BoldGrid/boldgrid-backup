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

switch ( true ) {
	case $is_premium && $is_premium_active:
		// Has a premium license and the premium plugin activated.
		$encrypt_message = sprintf(
			// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
			__( 'Note: If you are going to import and restore a backup containing encrypted files, then don\'t forget to copy your encryption token to your source site.  You can save your encryption token on the %1$sBackup Security%2$s settings page.', 'boldgrid-backup' ),
			'<a href="' .
				esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_security' ) ) .
				'">',
			'</a>'
		);
		break;

	case ! $is_premium:
		// Does not have a premium license.
		$encrypt_message = sprintf(
			// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
			__( 'If you are going to import and restore a backup containing encrypted files, then a BoldGrid Backup Premium license is required for decryption.  %1$sGet Premium%2$s', 'boldgrid-backup' ),
			'<a class="button button-success" href="' .
				esc_url( 'https://www.boldgrid.com/update-backup?source=bgbkup-settings-transfer-destination' ) .
				'" target="_blank">',
			'</a>'
		);
		break;

	case ! $is_premium_installed:
		// Has a premium license, but no premium plugin is installed.
		$encrypt_message = sprintf(
			// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
			esc_html__( 'The BoldGrid Backup Premium plugin is required for encryption features.  %1$sGet Premium Plugins%2$s', 'boldgrid-backup' ),
			'<a class="button button-success" href="' .
				esc_url( 'https://www.boldgrid.com/central/plugins?source=bgbkup-settings-transfer-destination' ) .
				'" target="_blank">',
			'</a>'
		);
		break;

	case $is_premium_installed && ! $is_premium_active:
		// Has a premium license and the premium plugin installed, but not activated.
		$encrypt_message = sprintf(
			// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
			__( 'BoldGrid Backup Premium is not active and required for encryption features.  Please go to the %1$sPlugins%2$s page to activate it.', 'boldgrid-backup' ),
			'<a href="' .
				esc_url( admin_url( 'plugins.php?s=Boldgrid%20Backup%20Premium&plugin_status=inactive' ) ) .
				'">',
			'</a>'
		);
		break;

	default:
		$encrypt_message = '';
		break;
}

return sprintf(
	'<div class="bgbkup-transfers-destination">
		<h2>%1$s</h2>
		<p>%2$s</p>
		<p>%3$s</p>
		<p>%9$s</p>
		<div id="url-import-section" class="wp-upload-form">
			%4$s <input type="text" name="url" placeholder="%5$s" size="30" />
			<input class="button" type="submit" value="%6$s" />
			<span class="spinner"></span>
			<div id="url-import-notice" class="notice notice-success inline"></div>
			%7$s
			%8$s
		</div>
	</div>',
	esc_html__( 'Use this section if you want to select this WordPress installation as the destination.', 'boldgrid-backup' ),
	esc_html__( 'Retrieve a download link from BoldGrid Backup on another WordPress installation, paste the link in form below, and click "Upload".  Once the download completes, you can either inspect the backup files and database or click "Restore".', 'boldgrid-backup' ),
	esc_html__( 'Note: Performing a restoration in this installation will replace files and the database contents.', 'boldgrid-backup' ),
	esc_html__( 'Import from a download link:', 'boldgrid-backup' ),
	esc_attr__( 'Download URL address', 'boldgrid-backup' ),
	esc_attr__( 'Upload', 'boldgrid-backup' ),
	wp_nonce_field( 'upload_archive_file' ), // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	wp_nonce_field( 'boldgrid_backup_restore_archive', '_wpnonce_restore' ), // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	$encrypt_message
);
