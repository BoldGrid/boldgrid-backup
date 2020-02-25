<?php
/**
 * File: class-boldgrid-backup-admin-plugin-notices.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.13.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Plugin_Notices
 *
 * @since 1.13.1
 */
class Boldgrid_Backup_Admin_Plugin_Notices {
	/**
	 * Filter the plugin notice counts.
	 *
	 * @since 1.13.1
	 *
	 * @param  array $translation An array of plugin notice info.
	 * @return array
	 */
	public function filter( $translation ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		// Setup the plugin notice count for the main menu item.
		$plugin_count = $core->plugin->getUnreadCount();
		if ( $plugin_count ) {
			$translation['counts'][] = [
				'href'  => 'admin.php?page=boldgrid-backup-dashboard',
				'count' => $plugin_count,
			];
		}

		// Setup plugin notice counts for sub menu items.
		foreach ( $core->configs['pages'] as $page ) {
			$page_count = $core->plugin->getPageBySlug( $page )->getUnreadCount();

			if ( $page_count ) {
				$translation['counts'][] = [
					'href'  => 'admin.php?page=' . $page,
					'count' => $page_count,
				];
			}
		}

		return $translation;
	}
}
