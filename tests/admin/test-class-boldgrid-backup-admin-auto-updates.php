<?php
/**
 * File: test-class-boldgrid-backup-admin-auto-updates.php
 *
 * @link https://www.boldgrid.com
 * @since     1.10.7
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

require_once ABSPATH . 'wp-includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once dirname( __FILE__ ) . '/class-license.php';

/**
 * Class: Test_Boldgrid_Backup_Admin_Auto_Updates.
 *
 * @since 1.10.7
 *
 * @group auto-updates
 */
class Test_Boldgrid_Backup_Admin_Auto_Updates extends WP_UnitTestCase {

	/**
	 * Auto Updates.
	 *
	 * @since 1.14.0
	 * @var Boldgrid_Backup_Admin_Auto_Updates
	 */
	private $auto_updates;

	/**
	 * Default Test Settings.
	 *
	 * @since 1.14.0
	 * @var array
	 */
	private $default_test_settings;

	/**
	 * Install Plugin.
	 *
	 * @since 1.14.0
	 * @param string $slug Plugin Slug.
	 * @param string $version Plugin Version to Install.
	 */
	public function install_plugin( $slug, $version ) {
		global $wp_filesystem;
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		$plugin_info     = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'downloadlink' => true,
					'versions'     => true,
				),
			)
		);
		$this_plugin_dir = ABSPATH . 'wp-content/plugins/' . $slug;

		$url      = isset( $plugin_info->versions[ $version ] ) ? $plugin_info->versions[ $version ] : '';
		$zip_file = ! empty( $url ) ? download_url( $url ) : '';
		if ( true === is_dir( ABSPATH . 'wp-content/plugins/' . $slug ) ) {
			$deleted = $wp_filesystem->delete( $this_plugin_dir, true );
		}
		$unzip = ! empty( $zip_file ) ? unzip_file( $zip_file, ABSPATH . 'wp-content/plugins/' ) : false;
		return $unzip;
	}

	/**
	 * Setup Tests.
	 *
	 * @since 1.14.0
	 */
	public function set_up() {
		$this->default_test_settings = array(
			'auto_update' => array(
				'timely-updates-enabled' => '0',
				'days'                   => 0,
				'wpcore'                 => array(
					'all'         => '0',
					'major'       => '0',
					'minor'       => '1',
					'dev'         => '0',
					'translation' => '0',
				),
				'plugins'                => array(
					'default' => '0',
				),
				'themes'                 => array(
					'default' => '0',
				),
			),
		);

		$installed_themes = wp_get_themes();
		$theme_slug       = 'twentytwentytwo'; // Replace with your desired theme slug
		$installed        = isset( $installed_themes[$theme_slug] );

		if ( ! $installed ) {
			$upgrader  = new Theme_Upgrader();
			$installed = $upgrader->install("https://downloads.wordpress.org/theme/{$theme_slug}.latest-stable.zip");
		}
	}

	/**
	 * Test Constructor.
	 *
	 * @since 1.14.0
	 */
	public function test_constructor() {
		// Test that WP's Auto Updater is NOT disabled after init.
		$auto_updates = new Boldgrid_Backup_Admin_Auto_Updates();
		$this->assertFalse( apply_filters( 'automatic_updater_disabled', true ) );
	}

	/** Test set_settings.
	 *
	 * @since 1.14.0
	 */
	public function test_set_settings() {
		$auto_updates = new Boldgrid_Backup_Admin_Auto_Updates();

		// Test that set_settings assignes the 'boldgrid_backup_settings[auto_update]' contents to $this->settings.
		update_option( 'boldgrid_backup_settings', $this->default_test_settings );
		$auto_updates->set_settings();
		$this->assertEquals( $this->default_test_settings['auto_update'], $auto_updates->settings );
	}

	/**
	 * Test maybe_update_plugin.
	 *
	 * @since 1.14.0
	 */
	public function test_maybe_update_plugin() {
		$this->install_plugin( 'akismet', '4.0' );
		activate_plugin( 'akismet' );

		// Test does not update if this plugin hasn't been configured yet.
		$akismet_test_settings = $this->default_test_settings;
		update_option( 'boldgrid_backup_settings', $akismet_test_settings );
		$auto_updates = new Boldgrid_backup_Admin_Auto_Updates();
		$this->assertFalse( $auto_updates->maybe_update_plugin( 'akismet' ) );

		// Test does not update if this plugin has updates disabled.
		$akismet_test_settings['auto_update']['plugins']['akismet/akismet.php'] = '0';
		update_option( 'boldgrid_backup_settings', $akismet_test_settings );
		$auto_updates->set_settings();
		$this->assertFalse( $auto_updates->maybe_update_plugin( 'akismet' ) );

		// Test that updates do occur if enabled.
		$akismet_test_settings['auto_update']['plugins']['akismet/akismet.php'] = '1';
		update_option( 'boldgrid_backup_settings', $akismet_test_settings );
		$auto_updates->set_settings();

		$plugins = \Boldgrid\Library\Library\Plugin\Plugins::getAllPlugins();
		$akismet = \Boldgrid\Library\Library\Plugin\Plugins::getBySlug( $plugins, 'akismet' );
		$akismet->setUpdateData();
		$days = $akismet->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName

		// Create mock object to simulate premium plugin being inactive.
		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'is_premium_done' ) )
			->getMock();
		$mock_auto_updates->method( 'is_premium_done' )
			->will( $this->returnValue( false ) );
		$this->assertTrue( $mock_auto_updates->maybe_update_plugin( 'akismet' ) );

		// Test updates will occur if within the day's option with premium active.
		// Create mock object to simulate premium plugin being inactive.
		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'is_premium_done' ) )
			->getMock();
		$mock_auto_updates->method( 'is_premium_done' )
			->will( $this->returnValue( true ) );
		$this->assertTrue( $mock_auto_updates->maybe_update_plugin( 'akismet' ) );

		// Test updates will not occur if not within the day's option with premium active.
		$akismet_test_settings['auto_update']['days']                   = (int) $days + 10;
		$akismet_test_settings['auto_update']['timely-updates-enabled'] = true;
		update_option( 'boldgrid_backup_settings', $akismet_test_settings );
		$mock_auto_updates->set_settings();
		$this->assertFalse( $mock_auto_updates->maybe_update_plugin( 'akismet' ) );
	}

	/**
	 * Test Maybe Update Theme.
	 *
	 * @since 1.14.0
	 */
	public function test_maybe_update_theme() {
		// Test does not update if this theme hasn't been configured yet.
		$twentytwentytwo_test_settings = $this->default_test_settings;
		update_option( 'boldgrid_backup_settings', $twentytwentytwo_test_settings );
		$auto_updates = new Boldgrid_backup_Admin_Auto_Updates();
		$this->assertFalse( $auto_updates->maybe_update_theme( 'twentytwentytwo' ) );

		// Test does not update if this theme has updates disabled.
		$twentytwentytwo_test_settings['auto_update']['themes']['twentytwentytwo'] = '0';
		update_option( 'boldgrid_backup_settings', $twentytwentytwo_test_settings );
		$auto_updates->set_settings();
		$this->assertFalse( $auto_updates->maybe_update_theme( 'twentytwentytwo' ) );

		// Test that updates do occur if enabled.
		$twentytwentytwo_test_settings['auto_update']['themes']['twentytwentytwo'] = '1';
		update_option( 'boldgrid_backup_settings', $twentytwentytwo_test_settings );
		$auto_updates->set_settings();
		$this->assertTrue( $auto_updates->maybe_update_theme( 'twentytwentytwo' ) );

		$themes = new \Boldgrid\Library\Library\Theme\Themes();
		$theme  = $themes->getFromStylesheet( 'twentytwentytwo' );
		$theme->setUpdateData();
		$days = $theme->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName

		// Create mock object to simulate premium plugin being inactive.
		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'is_premium_done' ) )
			->getMock();
		$mock_auto_updates->method( 'is_premium_done' )
			->will( $this->returnValue( false ) );
		$this->assertTrue( $mock_auto_updates->maybe_update_theme( 'twentytwentytwo' ) );

		// Test updates will occur if within the day's option with premium active.
		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'is_premium_done' ) )
			->getMock();
		$mock_auto_updates->method( 'is_premium_done' )
			->will( $this->returnValue( true ) );
		$this->assertTrue( $mock_auto_updates->maybe_update_theme( 'twentytwentytwo' ) );

		// Test updates will not occur if not within the day's option with premium active.
		$twentytwentytwo_test_settings['auto_update']['days']                   = (int) $days + 10;
		$twentytwentytwo_test_settings['auto_update']['timely-updates-enabled'] = true;
		update_option( 'boldgrid_backup_settings', $twentytwentytwo_test_settings );
		$mock_auto_updates->set_settings();
		$this->assertFalse( $mock_auto_updates->maybe_update_theme( 'twentytwentytwo' ) );
	}

	/**
	 * Test Auto Update Plugins.
	 *
	 * @since 1.14.0
	 */
	public function test_auto_update_plugins() {
		$this->install_plugin( 'akismet', '4.0' );
		activate_plugin( 'akismet' );

		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'maybe_update_plugin' ) )
			->getMock();
		$mock_auto_updates->method( 'maybe_update_plugin' )
			->will( $this->onConsecutiveCalls( true, false ) );
		$this->assertTrue( $mock_auto_updates->auto_update_plugins( true, (object) array( 'slug' => 'akismet' ) ) );
		$this->assertFalse( $mock_auto_updates->auto_update_plugins( true, (object) array( 'slug' => 'akismet' ) ) );
	}

	/**
	 * Test Auto Update Themes.
	 *
	 * @since 1.14.0
	 */
	public function test_auto_update_themes() {
		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'maybe_update_theme' ) )
			->getMock();
		$mock_auto_updates->method( 'maybe_update_theme' )
			->will( $this->returnValue( true ) );
		$themes = new \Boldgrid\Library\Library\Theme\Themes();
		foreach ( $themes->get() as $theme ) {
			if ( 'twentytwentytwo' === $theme->stylesheet ) {
				$this->assertTrue( $mock_auto_updates->auto_update_themes( true, (object) array( 'theme' => 'twentytwentytwo' ) ) );
			}
		}

		$mock_auto_updates = $this->getMockBuilder( Boldgrid_backup_Admin_Auto_Updates::class )
			->setMethods( array( 'maybe_update_theme' ) )
			->getMock();
		$mock_auto_updates->method( 'maybe_update_theme' )
			->will( $this->returnValue( false ) );
		$themes = new \Boldgrid\Library\Library\Theme\Themes();
		foreach ( $themes->get() as $theme ) {
			if ( 'twentytwentytwo' === $theme->stylesheet ) {
				$this->assertFalse( $mock_auto_updates->auto_update_themes( true, (object) array( 'theme' => 'twentytwentytwo' ) ) );
			}
		}
	}

	/**
	 * Test Auto Update Core.
	 *
	 * @since 1.14.0
	 */
	public function test_auto_update_core() {
		wp_cache_flush();
		$wp_core_test_settings = $this->default_test_settings;
		update_option( 'boldgrid_backup_settings', $wp_core_test_settings );

		$auto_updates = new Boldgrid_backup_Admin_Auto_Updates();

		// test default config.
		$this->apply_wpcore_filters( $auto_updates, array( 1, 1, 0, 0, 0 ) );

		// test major only config.
		$this->apply_wpcore_filters( $auto_updates, array( 1, 0, 1, 0, 0 ) );

		// test major only config.
		$this->apply_wpcore_filters( $auto_updates, array( 1, 0, 1, 0, 0 ) );
	}

	/**
	 * Apply wp core filters.
	 *
	 * @since 1.14.0
	 *
	 * @param Boldgrid_backup_Admin_Auto_Updates $auto_updates Auto Updates.
	 * @param array                              $expected_results Expected Results.
	 */
	public function apply_wpcore_filters( $auto_updates, $expected_results ) {
		global $current_screen;
		$included_files = get_included_files();

		$filters = array(
			'auto_update_core',
			'allow_minor_auto_core_updates',
			'allow_major_auto_core_updates',
		);

		$i            = 0;
		$filter_count = count( $filters );
		while ( $i < $filter_count ) {
			wp_cache_flush();
			$expected_result       = (bool) $expected_results[ $i ];
			$filter                = $filters[ $i ];
			$wp_core_test_settings = $this->default_test_settings;

			$wp_core_test_settings['auto_update']['wpcore'] = array(
				'all'         => (bool) $expected_results[0],
				'minor'       => (bool) $expected_results[1],
				'major'       => (bool) $expected_results[2],
				'translation' => (bool) $expected_results[3],
				'dev'         => (bool) $expected_results[4],
			);

			update_option( 'boldgrid_backup_settings', $wp_core_test_settings );
			$auto_updates->set_settings();

			$screen = WP_Screen::get( 'admin_init' );
			$auto_updates->auto_update_core();

			$this->assertEquals(
				$expected_result,
				apply_filters( $filter, $expected_result )
			);
			$i++;
		}
	}
}
