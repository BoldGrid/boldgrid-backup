<?php
/**
 * File: boldgrid-backup-admin-backup-modal.php
 *
 * Display a "Backup Site Now" button and modal.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

return sprintf(
	'
	<div id="backup_now_content" style="display:none;">
		<h2>%1$s</h2>

		%6$s

		<p>%2$s</p>

		%3$s

		%4$s

		<div style="height:70px;"></div>

		<div class="plugin-card-bottom">
			%5$s
		</div>
	</div>',
	/* 1 */ __( 'Backup Site Now', 'boldgrid-backup' ),
	/* 2 */ __( 'The <strong>Files and Folders</strong> and <strong>Database</strong> settings below customize which parts of your site to backup.', 'boldgrid-backup' ),
	/* 3 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/settings/folders.php',
	/* 4 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/settings/db.php',
	/* 5 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php',
	/* 6 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/backup-now-modal/title.php'
);
