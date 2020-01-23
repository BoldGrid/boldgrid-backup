<?php
/**
 * File: boldgrid-backup-admin-nav.php
 *
 * This file contains the navbar for all plugin pages.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

defined( 'WPINC' ) || die;

$core = apply_filters( 'boldgrid_backup_get_core', null );

$active = 'nav-tab-active';

// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
$navs = [
	[
		'title' => __( 'Dashboard', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-dashboard',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-dashboard' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Backups', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Transfers', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-transfers',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-transfers' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Tools', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-tools',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-tools' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Settings', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-settings',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-settings' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Preflight Check', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-test',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-test' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Support', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-support',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-support' === $_GET['page'] ? $active : '',
	],
	[
		'title' => __( 'Premium Features', 'boldgrid-backup' ),
		'href'  => 'admin.php?page=boldgrid-backup-premium-features',
		'class' => ! empty( $_GET['page'] ) && 'boldgrid-backup-premium-features' === $_GET['page'] ? $active : '',
		'count' => $core->plugin->getPageBySlug( 'boldgrid-backup-premium-features' )->getUnreadMarkup(),
	],
];
// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

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
		'<a class="nav-tab %1$s" href="%2$s">%3$s',
		esc_attr( $nav['class'] ),
		esc_url( $nav['href'] ),
		esc_html( $nav['title'] )
	);
	if ( isset( $nav['count'] ) ) {
		$markup .= sprintf(
			' %1$s',
			$nav['count'] // This value is escaped already by Library\Plugin\Page::getUnreadMarkup
		);
	}
	$markup .= '</a>';
}
$markup .= '</h2>';

return $markup;
