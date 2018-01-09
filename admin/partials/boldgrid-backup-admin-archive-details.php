<?php
/**
 * This file contains renders the details page of a backup archive.
 *
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 *
 * @param bool $archive_found Whether or not the archive was found.
 */

defined( 'WPINC' ) ? : die;


wp_nonce_field( 'boldgrid_backup_remote_storage_upload' );

?>

<div class="wrap">

	<h1><?php echo __( 'Backup Archive Details', 'boldgrid-backup' )?></h1>

<?php

	include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

	$separator = '<hr class="separator">';

	$details = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/details.php';
	$actions = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/actions.php';
	$remote_storage = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/remote-storage.php';
	$browser = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser.php';
	$db = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/db.php';
	$only_remote = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/only-remote.php';

	if( ! $archive_found && count( $this->remote_storage_li ) > 0 ) {
		echo $details;
		echo $separator . $only_remote;
		echo $remote_storage;
	} else {
		echo $details;
		echo $separator . $actions;
		echo $separator . $remote_storage;
		echo $separator . $browser;
		echo $separator . $db;
	}
?>

</div>