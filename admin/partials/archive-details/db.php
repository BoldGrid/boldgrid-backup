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

$db = array(
	'browser' => '',
	'buttons' => '',
);

if ( empty( $dump_file ) ) {
	$db['browser'] = sprintf( '
		<div class="hidden" data-view-type="db">
			<p>%1$s</p>
		</div>',
		__( 'This archive does not contain a database backup.', 'boldgrid-backup' )
	);
	return $db;
}

$contains = __( 'This archive contains the following database backup: <strong>%1$s</strong>', 'boldgrid-backup' );
$basename = basename( $dump_file );

$db = array(
	'browser' => sprintf( '
		<div class="hidden" data-view-type="db">
			<input type="hidden" id="dump_filename" value="%1$s" />
			<div id="db_details" data-rendered="false"></div>
		</div>',
		$basename
	),
	'buttons' => sprintf(
		'<a class="restore-db button button-primary" data-file="%2$s" data-view-type="db" style="display:none;">%1$s</a>%3$s',
		__( 'Restore this database', 'boldgrid-backup' ),
		esc_attr( $basename ),
		$this->core->lang['spinner']
	),
);

return $db;


