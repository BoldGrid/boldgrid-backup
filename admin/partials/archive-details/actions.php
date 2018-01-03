<?php
/**
 * Display the Archive Actions section on the Archive Details page.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 *
 * @param array $archive Please see https://pastebin.com/PCRaYK5v
 */

defined( 'WPINC' ) ? : die;

$delete_link = $this->core->archive_actions->get_delete_link( $archive['filename'] );
$download_button = $this->core->archive_actions->get_download_button( $archive['filename'] );
$restore_button = $this->core->archive_actions->get_restore_button( $archive['filename'] );

printf( '
	<h2>%1$s</h2>
	<div style="float:right;">%4$s</div>
	<p>
		%3$s
		%2$s
	</p>',
	/* 1 */ __( 'Archive Options', 'boldgrid-backup' ),
	/* 2 */ $restore_button,
	/* 3 */ $download_button,
	/* 4 */ $delete_link
);

?>