<?php
/**
 * This file contains the navbar for all BoldGrid Backup pages.
 *
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

defined( 'WPINC' ) ? : die;

$active = 'nav-tab-active';

$navs = array(
	array(
		'title' => __( 'Backups', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup' === $_GET['page'] ? $active : '',
	),
	array(
		'title' => __( 'Settings', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-settings',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-settings' === $_GET['page'] ? $active : '',
	),
	array(
		'title' => __( 'Preflight Check', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-test',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-test' === $_GET['page'] ? $active : '',
	),
	array(
		'title' => __( 'Tools', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-tools',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-tools' === $_GET['page'] ? $active : '',
	),
);

/**
 * Allow the update of our nav menu items.
 *
 * @since 1.5.3
 *
 * @param array $navs
 */
$navs = apply_filters( 'boldgrid_backup_navs', $navs );

$markup = '<h2 class="nav-tab-wrapper">';
foreach ( $navs as $nav ) {
	$markup .= sprintf(
		'<a class="nav-tab %1$s" href="%2$s">%3$s</a>',
		$nav['class'],
		$nav['href'],
		$nav['title']
	);
}
$markup .= '</h2>';

return $markup;


