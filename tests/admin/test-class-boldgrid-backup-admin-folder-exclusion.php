<?php
/**
 * File: test-class-boldgrid-backup-admin-folder-exclusion.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.17.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Folder_Exclusion
 *
 * @since 1.17.5
 */
class Test_Boldgrid_Backup_Admin_Folder_Exclusion extends WP_UnitTestCase {
	/**
	 * Folder exclusion instance.
	 *
	 * @var Boldgrid_Backup_Admin_Folder_Exclusion
	 */
	private $folder_exclusion;

	/**
	 * Tokens that must remain in the default exclude list.
	 *
	 * @var array
	 */
	private $required_default_excludes = array(
		'.git',
		'node_modules',
		'wp-content/cache',
		'*.zip',
		'*.gz',
		'*.tar',
		'*.wpress',
		'*.tmp',
	);

	/**
	 * Setup.
	 */
	public function set_up() {
		parent::set_up();

		$core                                 = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Core' )
			->disableOriginalConstructor()
			->getMock();
		$core->is_archiving_update_protection = false;
		$core->is_backup_full                 = false;
		$core->pre_auto_update                = false;
		$core->is_backup_now                  = false;
		$core->settings                       = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Settings' )
			->disableOriginalConstructor()
			->getMock();
		$core->settings->is_saving_settings   = false;

		$this->folder_exclusion = new Boldgrid_Backup_Admin_Folder_Exclusion( $core );
	}

	/**
	 * Default exclude list includes cache and archive globs.
	 */
	public function test_default_exclude_includes_archive_globs() {
		$parts = array_map( 'trim', explode( ',', $this->folder_exclusion->default_exclude ) );

		foreach ( $this->required_default_excludes as $expected ) {
			$this->assertTrue(
				in_array( $expected, $parts, true ),
				$expected . ' missing from default_exclude'
			);
		}
	}

	/**
	 * Glob patterns match nested files and do not swallow unrelated extensions.
	 */
	public function test_is_match_archive_globs() {
		$cases = array(
			array( 'wp-content/cache', 'wp-content/cache/file.php', true ),
			array( '*.zip', 'wp-content/uploads/site.zip', true ),
			array( '*.zip', 'testing.zip', true ),
			array( '*.zip', 'wp-content/uploads/photo.jpg', false ),
			array( '*.zip', 'wp-content/uploads/file.zip.php', false ),
			array( '*.gz', 'wp-content/backup.tar.gz', true ),
			array( '*.gz', 'wp-content/file.css', false ),
			array( '*.tar', 'wp-content/archive.tar', true ),
			array( '*.tar', 'wp-content/archive.tar.gz', false ),
			array( '*.wpress', 'wp-content/ai1wm.wpress', true ),
			array( '*.tmp', 'wp-admin/orphan.tmp', true ),
			array( '*.tmp', 'wp-admin/orphan.tmp.php', false ),
		);

		foreach ( $cases as $case ) {
			list( $pattern, $file, $expected ) = $case;
			$this->assertSame(
				$expected,
				$this->folder_exclusion->is_match( $pattern, $file ),
				$pattern . ' vs ' . $file
			);
		}
	}

	/**
	 * "Backup all files" picks up the class default even if a pre-upgrade
	 * exclude string is stored.
	 */
	public function test_from_settings_full_type_uses_new_default() {
		$settings = array(
			'folder_exclusion_type'    => 'full',
			'folder_exclusion_exclude' => '.git,node_modules',
			'folder_exclusion_include' => 'WPCORE,/wp-content',
		);

		$this->assertSame(
			$this->folder_exclusion->default_exclude,
			$this->folder_exclusion->from_settings( 'exclude', $settings )
		);
	}

	/**
	 * Custom exclude lists are not rewritten on upgrade.
	 */
	public function test_from_settings_custom_type_keeps_stored_exclude() {
		$custom   = '.git,node_modules,my-custom-dir';
		$settings = array(
			'folder_exclusion_type'    => 'custom',
			'folder_exclusion_exclude' => $custom,
			'folder_exclusion_include' => 'WPCORE,/wp-content',
		);

		$this->assertSame( $custom, $this->folder_exclusion->from_settings( 'exclude', $settings ) );
	}
}
