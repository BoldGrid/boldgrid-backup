<?php
/**
 * File: test-class-boldgrid-backup-admin-notices.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Notice_Counts
 *
 * @since 1.11.0
 */
class Test_Boldgrid_Backup_Admin_Notice_Counts extends WP_UnitTestCase {

	public $core;

	public function setUp() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );
	}
	public function test_notice_not_displayed_first_install() {
		$this->assertTrue( $this->notice_counts_in_nav( '1.16.16', '1.16.16', 0 ) );
	}

	// public function test_one_notice_displayed() {
	// 	$this->assertTrue( $this->notice_counts_in_nav( '1.12.16', '1.16.16', 1 ) );
	// }

	// public function test_new_feature_added() {
	// 	$new_feature = [
	// 		'id'      => 'bgbkup_google_drive',
	// 		'page'    => 'boldgrid-backup-premium-features',
	// 		'version' => '1.18.16',
	// 	];
	// 	$this->notice_counts_in_nav( '1.12.16', '1.16.16', 1 );
	// 	$this->assertTrue( $this->notice_counts_in_nav( '1.12.16', '1.16.16', 2, $new_feature = $new_feature ) );
	// }

	// public function test_mark_feature_read() {
	// 	$this->assertTrue( $this->notice_counts_in_nav( '1.12.16', '1.16.16', 2 ) );
	// 	$this->core->plugin->getPageBySlug( 'boldgrid-backup-premium-features' )->setAllNoticesRead();
	// 	$this->assertTrue( $this->notice_counts_in_nav( '1.12.16', '1.16.16', 0 ) );
	// }

	public function notice_counts_in_nav( $first_version, $this_version, $expected_count, $new_feature = null ) {
		$this->core->plugin->pluginData = $this->get_plugin_data( $this_version );
		$plugin_config                  = $this->get_plugin_config();
		if ( $new_feature ) {
			$plugin_config['page_notices'][]  = $new_feature;
			$this->core->plugin->pluginConfig = $plugin_config;
			$this->core->plugin->setPages();
		} else {
			$this->core->plugin->pluginConfig = $plugin_config;
		}
		$this->set_versions( $first_version, $this_version );

		$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

		$unread_count = 0;
		foreach ( $navs as $nav_item ) {
			if ( isset( $nav_item['count'] ) ) {
				$unread_count += intval( wp_kses( $nav_item['count'], 'span' ) );
			}
		}
		return $expected_count === $unread_count;
	}

	public function get_plugin_data( $this_version ) {

		return [
			'Name'        => 'Total Upkeep',
			'PluginURI'   => 'https://www.boldgrid.com/boldgrid-backup/',
			'Version'     => $this_version,
			'Description' => 'Automated backups, remote backup to Amazon S3 and Google Drive, stop website crashes before they happen and more. Total Upkeep is the backup solution you need. By BoldGrid.',
			'Author'      => 'BoldGrid',
			'AuthorURI'   => 'https://www.boldgrid.com/',
			'TextDomain'  => 'boldgrid-backup',
			'DomainPath'  => '/languages',
		];
	}

	public function get_plugin_config() {
		return [
			'pages'        => [
				'boldgrid-backup-premium-features',
			],
			'page_notices' => [
				[
					'id'      => 'bgbkup_database_encryption',
					'page'    => 'boldgrid-backup-premium-features',
					'version' => '1.19.16',
				],
			],
		];
	}

	public function set_versions( $first_version, $current_version ) {
		$boldgrid_settings = [
			'plugins_checked' => [
				'boldgrid-backup/boldgrid-backup.php' => [
					$first_version   => 1580131675,
					$current_version => 1580141541,
				],
			],
		];
		update_option( 'boldgrid_settings', $boldgrid_settings );
	}
}
