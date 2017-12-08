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
	<a href="#TB_inline?width=800&height=600&inlineId=backup_now_content" class="thickbox button button-primary">%1$s</a>

	<div id="backup_now_content" style="display:none;">
		<h2>%6$s</h2>

		<p>
			%7$s<br /><br />

			<input type="radio" name="backup_now_type" value="full" checked="checked" />%8$s<br />
			<input type="radio" name="backup_now_type" value="custom" />%9$s
		</p>

		<div id="customize_backup_now" class="hidden">
			<hr />

			<p>%4$s</p>

			<div class="setting-section">%2$s</div>

			<div class="setting-section last">%5$s</div>
		</div>

		<div class="plugin-card-bottom">
			%3$s
		</div>
	</div>',
	/* 1 */ __( 'Backup Site Now', 'boldgrid-backup' ),
	/* 2 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/folders.php',
	/* 3 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php',
	/* 4 */ __( 'The <strong>Files and Folders</strong> and <strong>Database</strong> settings below customize which parts of your site to backup. The current settings are those currently saved in your settings page.', 'boldgrid-backup' ),
	/* 5 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/db.php',
	/* 6 */ __( 'Backup Site Now', 'boldgrid-backup' ),
	/* 7 */ __( 'Choose <strong>Full Backup</strong> to backup all of your files and all database tables. If you would like to customize what is backed up, choose <strong>Custom Backup</strong>.', 'boldgrid-backup' ),
	/* 8 */ __( 'Full Backup', 'boldgrid-backup' ),
	/* 9 */ __( 'Custom Backup', 'boldgrid-backup' )
);

?>