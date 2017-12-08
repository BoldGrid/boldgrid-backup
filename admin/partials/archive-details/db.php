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

printf( '<h2>%1$s</h2>', __( 'Database', 'boldgrid-backup' ) );

if( empty( $dump_file ) ) {
	printf( '<p>%1$s</p>', __( 'This archive does not contain a database backup.', 'boldgrid-backup' ) );
	return;
}

$contains = __( 'This archive contains the following database backup: <strong>%1$s</strong>', 'boldgrid-backup' );
$basename = basename( $dump_file );

printf( '
	<p>%1$s</p>
	<p data-file="%4$s">
		<a class="restore-db button button-primary">%2$s</a>
		<a class="view-db button">%3$s</a>
	</p>
	<div id="db_details"></div>',
	/* 1 */ sprintf( $contains, $basename ),
	/* 2 */ __( 'Restore this database', 'boldgrid-backup' ),
	/* 3 */ __( 'View details', 'boldgrid-backup' ),
	/* 4 */ $basename
);

?>
