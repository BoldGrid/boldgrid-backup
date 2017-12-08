<?php
/**
 * Display a section that shows disk and db site data.
 *
 * @since 1.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archives
 */

defined( 'WPINC' ) ? : die;

return sprintf(
	'<div id="size-data">
		%1$s
		<p><span class="spinner inline"></span>%2$s</p>
	</div>',
	wp_nonce_field( 'boldgrid_backup_sizes', 'sizes_auth' ),
	esc_html__( 'Calculating disk space...' )
);

?>