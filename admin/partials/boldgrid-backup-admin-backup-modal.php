<?php
/**
 * Display a "Backup Site Now" button and modal.
 *
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

defined( 'WPINC' ) ? : die;

return sprintf('
	<div id="backup_now_content" style="display:none;">
		<h2>%1$s</h2>

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
	/* 3 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/folders.php',
	/* 4 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/db.php',
	/* 5 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php'
);


