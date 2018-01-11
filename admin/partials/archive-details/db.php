<?php
/**
 * Display the Database section on the Archive Details page.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 */

defined( 'WPINC' ) ? : die;

if( empty( $dump_file ) ) {
	$db = sprintf( '<p>%1$s</p>', __( 'This archive does not contain a database backup.', 'boldgrid-backup' ) );
	return $db;
}

$contains = __( 'This archive contains the following database backup: <strong>%1$s</strong>', 'boldgrid-backup' );
$basename = basename( $dump_file );

$db = array(
	'browser' => sprintf( '
		<div class="hidden" data-view-type="db">
			<input type="hidden" id="dump_filename" value="%4$s" />
			<!--
			<p>%1$s</p>
			<p>
				<a class="restore-db button button-primary">%2$s</a>
				<a class="view-db button">%3$s</a>
			</p>
			-->
			<div id="db_details" data-rendered="false"></div>
		</div>',
		/* 1 */ sprintf( $contains, $basename ),
		/* 2 */ __( 'Restore this database', 'boldgrid-backup' ),
		/* 3 */ __( 'View details', 'boldgrid-backup' ),
		/* 4 */ $basename
	),
	'buttons' => sprintf(
		'<a class="restore-db button button-primary" data-view-type="db" style="display:none;">%1$s</a>',
		__( 'Restore this database', 'boldgrid-backup' )
	),
);

return $db;

?>
