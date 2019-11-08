<?php
/**
 * File: details.php
 *
 * Render the details of a particular backup.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * @param bool $archive_found Whether or not the archive was found.
 */

defined( 'WPINC' ) || die;

$details = '';

$attribute = '<p><strong>%1$s</strong>: <span id="bgb-details-%3$s" data-value="%4$s">%2$s</span></p>';

$datas = array(
	array(
		'key'   => 'trigger',
		'title' => __( 'Backup triggered by', 'boldgrid-backup' ),
	),
	array(
		'key'   => 'compressor',
		'title' => __( 'Compressor', 'boldgrid-backup' ),
	),
	array(
		'key'    => 'duration',
		'title'  => __( 'Total duration', 'boldgrid-backup' ),
		'suffix' => ' ' . __( 'seconds', 'boldgrid-backup' ),
	),
	array(
		'key'    => 'db_duration',
		'title'  => __( 'Time to backup database', 'boldgrid-backup' ),
		'suffix' => ' ' . __( 'seconds', 'boldgrid-backup' ),
	),
	array(
		'key'          => 'mail_success',
		'title'        => __( 'Email sent after backup', 'boldgrid-backup' ),
		'presentation' => 'bool',
	),
	array(
		'key'   => 'folder_include',
		'title' => __( 'Files included', 'boldgrid-backup' ),
	),
	array(
		'key'   => 'folder_exclude',
		'title' => __( 'Files excluded', 'boldgrid-backup' ),
	),
	array(
		'key'          => 'table_exclude',
		'title'        => __( 'Database tables excluded', 'boldgrid-backup' ),
		'presentation' => 'comma_implode',
	),
);

foreach ( $datas as $data ) {
	if ( ! isset( $archive[ $data['key'] ] ) ) {
		continue;
	}

	if ( ! empty( $data['heading'] ) ) {
		$details .= sprintf( '<h2>%1$s:</h2>', $data['heading'] );
	}

	$value      = $archive[ $data['key'] ];
	$value_data = '';

	if ( ! empty( $data['presentation'] ) ) {
		switch ( $data['presentation'] ) {
			case 'bytes_to_human':
				$value      = Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive[ $data['key'] ] );
				$value_data = $archive[ $data['key'] ];
				break;
			case 'bool':
				$value      = $archive[ $data['key'] ] ? __( 'yes', 'boldgrid-backup' ) : __( 'no', 'boldgrid-backup' );
				$value_data = $archive[ $data['key'] ] ? 'Y' : 'N';
				break;
			case 'comma_implode':
				$value      = empty( $value ) ? __( 'n/a', 'boldgrid-backup' ) : implode( ', ', $value );
				$value_data = $value;
				break;
		}
	}

	if ( ! empty( $data['suffix'] ) ) {
		$value .= $data['suffix'];
	}

	if ( ! empty( $data['hidden_input'] ) ) {
		$value .= sprintf( '<input type="hidden" id="%1$s" value="%2$s" />', $data['key'], $archive[ $data['key'] ] );
	}

	$details .= sprintf( $attribute, $data['title'], $value, $data['key'], $value_data );
}

/**
 * Filter the archive details.
 *
 * @since 1.12.0
 */
return apply_filters( 'boldgrid_backup_filter_archive_details', $details, $archive );
