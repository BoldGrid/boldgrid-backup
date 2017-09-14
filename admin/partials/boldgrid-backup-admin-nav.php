<?php
/**
 * This file contains the navbar for all BoldGrid Backup pages.
 *
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

$active = 'nav-tab-active';
$backups_active = ! empty( $_GET['page'] ) && 'boldgrid-backup' === $_GET['page'] ? $active : '';
$settings_active = ! empty( $_GET['page'] ) && 'boldgrid-backup-settings' === $_GET['page'] ? $active : '';
$preflight_active = ! empty( $_GET['page'] ) && 'boldgrid-backup-test' === $_GET['page'] ? $active : '';

?>

<h2 class="nav-tab-wrapper">
	<a class="nav-tab <?php echo $backups_active; ?>" href="admin.php?page=boldgrid-backup" ><?php echo __( 'Backups', 'boldgrid-backup' ); ?></a>
	<a class="nav-tab <?php echo $settings_active; ?>" href="admin.php?page=boldgrid-backup-settings"><?php echo __( 'Settings', 'boldgrid-backup' ); ?></a>
	<a class="nav-tab <?php echo $preflight_active; ?>" href="admin.php?page=boldgrid-backup-test"><?php echo __( 'Preflight Check', 'boldgrid-backup' ); ?></a>
</h2>
