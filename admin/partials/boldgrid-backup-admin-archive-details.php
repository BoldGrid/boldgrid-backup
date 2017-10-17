<?php
/**
 * This file contains renders the details page of a backup archive.
 *
 * The content created by this page will be renered in an iframe.
 *
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

$attribute = '<p><strong>%1$s</strong>: %2$s</p>';

$datas = array(
	array(
		'key' => 'filepath',
		'title' => __( 'File path', 'boldgrid-backup' ),
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
);

wp_nonce_field( 'boldgrid_backup_remote_storage_upload' );

?>

<!--  <img src="//repo.boldgrid.com/assets/banner-backup-772x250.png" id="header_banner" /> -->

<div class="wrap">

<h1><?php echo __( 'Backup Archive Details', 'boldgrid-backup' )?></h1>

<?php include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php'; ?>

<?php
foreach( $datas as $data ) {
	if( ! isset( $archive[ $data['key'] ] ) ) {
		continue;
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
		}
	}

	if( ! empty( $data['suffix'] ) ) {
		$value .= $data['suffix'];
	}

	if( ! empty( $data['hidden_input' ] ) ) {
		$value .= sprintf( '<input type="hidden" id="%1$s" value="%2$s" />', $data['key'], $archive[ $data['key'] ] );
	}

	printf( $attribute, $data['title'], $value );
}

include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/remote-storage.php';

include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser.php';
?>

</div>