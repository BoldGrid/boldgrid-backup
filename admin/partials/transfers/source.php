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

return sprintf(
	'<div class="bgbkup-transfers-source">
		<h2>%1$s</h2>
		<p>%2$s</p>
		<p>%3$s</p>
		%4$s
	</div>',
	esc_html__( 'Use this section if you want to select this WordPress installation as the source.', 'boldgrid_backup' ),
	esc_html(
		__(
			'Choose a full backup in the list, click the "Get Download Link" button, and then click the "Copy Link" button.  The download link is valid for a limited time and can be used to import the backed-up website into another WordPress installation using',
			'boldgrid_backup'
		) . ' ' . BOLDGRID_BACKUP_TITLE . '.'
	),
	esc_html__( 'Note: Backup archives only existing in remote storage must first be downloaded to this web server in order to get a download link.  Click the "View Details" for an archive and use the details page to download from remote storage.', 'boldgrid_backup' ),
	$archive_list // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
);
