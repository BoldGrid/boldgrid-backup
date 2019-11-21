<?php
/**
 * File: source.php
 *
 * Show "Source" on transfers page.
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

$archive_list = $this->core->archives->get_table(
	[
		'show_link_button' => true,
		'transfers_mode'   => true,
	]
);

$contains_encrypted = false !== strpos( $archive_list, 'bgbkup-db-encrypted' );

switch ( true ) {
	case ! $contains_encrypted:
		// Has no encrypted files.
		$encrypt_message = '';
		break;

	case $contains_encrypted && $is_premium && $is_premium_active:
		// Has encrypted files, a premium license, and the premium plugin activated.
		$encrypt_message = sprintf(
			// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
			__( 'Note: If you are going to migrate and restore a backup containing encrypted files, then don\'t forget to copy your encryption token to your destination site.  You can retrieve your encryption token on the %1$sBackup Security%2$s settings page. ', 'boldgrid-backup' ),
			'<a href="' .
				esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_security' ) ) .
				'">',
			'</a>'
		);
		break;

	case $contains_encrypted && ! $is_premium:
		// Has encrypted files but no premium license.
		$get_premium_url = 'https://www.boldgrid.com/update-backup?source=bgbkup-settings-transfer-source';
		$encrypt_message = sprintf(
			// translators: 1: Get premium button/link, 2: Premium plugin title.
			__( 'If you are going to migrate and restore a backup containing encrypted files, then a %2$s license is required for decryption.  %1$s', 'boldgrid-backup' ),
			$this->core->go_pro->get_premium_button( $get_premium_url, __( 'Get Premium', 'boldgrid-backup' ) ), // phpcs:ignore
			BOLDGRID_BACKUP_TITLE . ' Premium'
		);
		break;

	case $contains_encrypted && ! $is_premium_installed:
		// Has encrypted files and a premium license, but no premium plugin installed.
		$get_plugins_url = 'https://www.boldgrid.com/central/plugins?source=bgbkup-settings-transfer-source';
		$encrypt_message = sprintf(
			// translators: 1: Unlock Feature button/link, 2: Premium plugin title.
			esc_html__( 'The %2$s plugin is required for encryption.  %1$s', 'boldgrid-backup' ),
			$this->core->go_pro->get_premium_button( $get_plugins_url, __( 'Unlock Feature', 'boldgrid-backup' ) ), // phpcs:ignore
			BOLDGRID_BACKUP_TITLE . ' Premium'
		);
		break;

	case $contains_encrypted && $is_premium_installed && ! $is_premium_active:
		// Has encrypted files, a premium license, premium plugin installed, but not activated.
		$encrypt_message = sprintf(
			// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag, 3: Premium plugin title.
			__( '%3$s is not active and required for encryption features.  Please go to the %1$sPlugins%2$s page to activate it.', 'boldgrid-backup' ),
			'<a href="' .
				esc_url( admin_url( 'plugins.php?s=Boldgrid%20Backup%20Premium&plugin_status=inactive' ) ) .
				'">',
			'</a>',
			BOLDGRID_BACKUP_TITLE . ' Premium'
		);
		break;

	default:
		$encrypt_message = '';
		break;
}

return sprintf(
	'<div class="bgbkup-transfers-source">
		<h2>%1$s</h2>
		<p>%2$s</p>
		<p>%3$s</p>
		<p>%4$s</p>
		%5$s
	</div>',
	esc_html__( 'Use this section if you want to select this WordPress installation as the source.', 'boldgrid_backup' ),
	esc_html__(
		'Choose a full backup in the list, click the "Get Download Link" button, and then click the "Copy Link" button.  The download link is valid for a limited time and can be used to import the backed-up website into another WordPress installation using',
		'boldgrid_backup'
	) . ' ' . BOLDGRID_BACKUP_TITLE . '.',
	esc_html__( 'Note: Backup archives only existing in remote storage must first be downloaded to this web server in order to get a download link.  Click the "View Details" for an archive and use the details page to download from remote storage.', 'boldgrid_backup' ),
	$encrypt_message,
	$archive_list
);
