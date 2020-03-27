<?php
/**
 * File: test-class-boldgrid-backup-admin-premium-features-page.php
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
 * Class: Test_Boldgrid_Backup_Admin_Premium_Features_Page
 *
 * @since 1.11.0
 */

class Test_Boldgrid_Backup_Admin_Premium_Features_Page extends WP_UnitTestCase {

	public
		$card_files,
		$core,
		$premium_page;

	public function setUp() {

		$files = array(
			'/vendor/boldgrid/library/src/Library/Ui/Card.php',
			'/vendor/boldgrid/library/src/Library/Ui/Dashboard.php',
		);

		$this->card_files = array(
			'/admin/card/class-database-encryption.php',
			'/admin/card/class-amazon-s3.php',
			'/admin/card/class-dream-objects.php',
			'/admin/card/class-google-drive.php',
			'/admin/card/class-historical-versions.php',
			'/admin/card/class-history.php',
			'/admin/card/class-one-click-restoration.php',
			'/admin/card/class-plugin-editor-tools.php',
			'/admin/card/class-find-modified-files.php',
			'/admin/card/class-timely-auto-updates.php',
		);

		foreach ( $files as $file ) {
			require_once BOLDGRID_BACKUP_PATH . $file;
		}
		foreach ( $this->card_files as $card ) {
			require_once BOLDGRID_BACKUP_PATH . $card;
		}

		$this->core         = new \Boldgrid_Backup_Admin_Core();
		$this->premium_page = new \Boldgrid_Backup_Admin_Premium_Features( $this->core );
	}

	public function test_get_cards() {
		$cards = $this->premium_page->get_cards();
		$this->assertEquals( count( $cards ), count( $this->card_files ) );
		$this->premium_page->core->plugin = new \Boldgrid\Library\Library\Plugin\Plugin( 'boldgrid-backup', $this->core->configs );
	}

	public function test_admin_enqueue_scripts() {
		global $wp_styles;
		if ( ! ( $wp_styles instanceof WP_Styles ) ) {
			$wp_styles = new WP_Styles();
		}
		$_REQUEST['page'] = 'boldgrid-backup-premium-features';
		$this->premium_page->admin_enqueue_scripts( 'boldgrid-backup-admin-premium-features' );
		$this->assertEquals( true, wp_style_is( 'boldgrid-backup-admin-premium-features' ) );
		wp_dequeue_style( 'boldgrid-backup-admin-premium-features' );
		$_REQUEST['page'] = 'boldgrid-backup-dashboard';
		$this->premium_page->admin_enqueue_scripts( 'boldgrid-backup-admin-premium-features' );
		$this->assertEquals( false, wp_style_is( 'boldgrid-backup-admin-premium-features' ) );

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
