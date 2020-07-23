<?php
/**
 * File: boldgrid-backup-admin-home.php
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

/*
 * Variables passed by scope.
 *
 * @param int $archives_count The archive file count.
 * @param int $archives_size The total size of all archive files.
 * @param array $archives {
 * 	A numbered array of arrays containing the following indexes.
 * 	@type string $filepath Archive file path.
 * 	@type string $filename Archive filename.
 * 	@type string $filedate Localized file modification date.
 * 	@type int $filesize The archive file size in bytes.
 * 	@type int $lastmodunix The archive file modification time in unix seconds.
 * }
 * @param string $backup_identifier The backup identifier for this installation.
 * @param string $table             Markup for a table of current backups. If no
 *                                  backups, it's a p tag with appropriate message.
 */

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

// Premium advertisement on the bottom of the archives page.
$premium_url = $this->go_pro->get_premium_url( 'bgbkup-archives-bottom' );
$ad          = $this->config->is_premium_done ? '' : sprintf(
	'
	<div class="bg-box-bottom premium wp-clearfix">
		%1$s
		<p style="margin:0;">%2$s</p>
	</div>',
	$this->go_pro->get_premium_button( $premium_url, esc_html__( 'Unlock Feature', 'boldgrid-backup' ) ),
	$this->lang['want_to']
);

// Backup now modal.
$in_modal = true;
$modal    = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
$in_modal = false;

echo '
	<div class="wrap">
		<div id="bglib-page-container" class="bgbkup-page-container">
			<div id="bglib-page-top">
				<div id="bglib-page-header" class="bglib-has-logo">
					<h1>' . esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Backup Archives', 'boldgrid-backup' ) ) . '</h1>

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
echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

echo $table; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

echo $modal; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

/*
 * @todo Revisit the usefulness of this notice in this location. Commented out @since 1.10.1
 * require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/note-pre-backup.php';
 */

// Bombard the user with an ad before they've even made their first backup. I mean, don't.
if ( ! empty( $this->archives_all->all ) ) {
	echo $ad; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
}

echo '
			</div>
		</div>
	</div>';
