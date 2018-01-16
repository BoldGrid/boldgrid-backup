<?php
/**
 * Display a "Backup Site Now" button.
 *
 * @since 1.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archives
 */

defined( 'WPINC' ) ? : die;

$pagenow_enqueue_rollback = array( 'plugins.php', 'update-core.php' );

return sprintf(
	'<div id="backup-site-now-section">
		<form action="#" id="backup-site-now-form" method="POST">
			%1$s
			<p id="you_may_leave" class="hidden">
				%4$s
			</p>
			<p>
				<a id="backup-site-now" class="button button-primary" %3$s >
					%2$s
				</a>
				<span class="spinner"></span>
			</p>
		</form>
	</div>
	<div id="backup-site-now-results"></div>',
	wp_nonce_field( 'boldgrid_backup_now', 'backup_auth' ),
	esc_html( 'Backup Site Now', 'boldgrid-backup' ),
	in_array( $pagenow, $pagenow_enqueue_rollback, true ) ? 'data-updating="true"' : '',
	/* 4 */ __( 'You may leave this page, doing so will not stop your backup.', 'boldgrid-backup' )
);

?>