<?php
/**
 * Render the details of a particular backup.
 *
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 *
 * @param bool $archive_found Whether or not the archive was found.
 */

defined( 'WPINC' ) ? : die;

$details = '';

$attribute = '<p><strong>%1$s</strong>: %2$s</p>';

$datas = array(
	array(
		'key' => 'filename',
		'title' => __( 'Filename', 'boldgrid-backup' ),
		'hidden_input' => true,
	),
	array(
		'key' => 'filesize',
		'title' => __( 'File size', 'boldgrid-backup' ),
		'presentation' => 'bytes_to_human',
	),
	array(
		'key' => 'filedate',
		'title' => __( 'Backup date', 'boldgrid-backup' ),
	),
	array(
		'key' => 'trigger',
		'title' => __( 'Backup triggered by', 'boldgrid-backup' ),
	),
	array(
		'key' => 'compressor',
		'title' => __( 'Compressor', 'boldgrid-backup' ),
	),
	array(
		'key' => 'duration',
		'title' => __( 'Total duration', 'boldgrid-backup' ),
		'suffix' => ' ' . __( 'seconds', 'boldgrid-backup' ),
	),
	array(
		'key' => 'db_duration',
		'title' => __( 'Time to backup database', 'boldgrid-backup' ),
		'suffix' => ' ' . __( 'seconds', 'boldgrid-backup' ),
	),
	array(
		'key' => 'mail_success',
		'title' => __( 'Email sent after backup', 'boldgrid-backup' ),
		'presentation' => 'bool',
	),
	array(
		'key' => 'folder_include',
		'title' => __( 'Included', 'boldgrid-backup' ),
		'heading' => __( 'Files and Folders', 'boldgrid-backup' ),
	),
	array(
		'key' => 'folder_exclude',
		'title' => __( 'Excluded', 'boldgrid-backup' ),
	),
	array(
		'key' => 'table_exclude',
		'title' => __( 'Tables excluded', 'boldgrid-backup' ),
		'heading' => __( 'Database', 'boldgrid-backup' ),
		'presentation' => 'comma_implode',
	),
);

foreach( $datas as $data ) {
	if( ! isset( $archive[ $data['key'] ] ) ) {
		continue;
	}

	if( ! empty( $data['heading'] ) ) {
		$details .= sprintf( '<h2>%1$s:</h2>', $data['heading'] );
	}

	$value = $archive[ $data['key'] ];
	if( ! empty( $data['presentation'] ) ) {
		switch( $data['presentation'] ) {
			case 'bytes_to_human':
				$value = Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive[ $data['key'] ] );
				break;
			case 'bool':
				$value = $archive[ $data['key'] ] ? __( 'yes', 'boldgrid-backup' ) : __( 'no', 'boldgrid-backup' );
				break;
			case 'comma_implode':
				$value = empty( $value ) ? __( 'n/a', 'boldgrid-backup' ) : implode( ', ', $value );
				break;
		}
	}

	if( ! empty( $data['suffix'] ) ) {
		$value .= $data['suffix'];
	}

	if( ! empty( $data['hidden_input' ] ) ) {
		$value .= sprintf( '<input type="hidden" id="%1$s" value="%2$s" />', $data['key'], $archive[ $data['key'] ] );
	}

	$details .= sprintf( $attribute, $data['title'], $value );
}

return $details;

?>