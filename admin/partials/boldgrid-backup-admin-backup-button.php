<?php
/**
 * Display a "Backup Site Now" button.
 *
 * @since 1.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archives
 */

return sprintf(
	'<form action="#" id="backup-site-now-form" method="POST">
		%1$s
		<p>
			<a id="backup-site-now" class="button button-primary" disabled="disabled" >
				%2$s
			</a>
			<span class="spinner"></span>
		</p>
	</form>
	<div id="backup-site-now-results"></div>',
	wp_nonce_field( 'boldgrid_backup_now', 'backup_auth' ),
	esc_html( 'Backup Site Now', 'boldgrid-backup' )
);

?>