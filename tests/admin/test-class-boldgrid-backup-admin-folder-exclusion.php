<?php
/**
 * File: test-class-boldgrid-backup-admin-folder-exclusion.php
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Folder_Exclusion
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Admin_Folder_Exclusion extends WP_UnitTestCase {
	/**
	 * Get a fresh folder exclusion object.
	 *
	 * @since SINCEVERSION
	 *
	 * @return Boldgrid_Backup_Admin_Folder_Exclusion
	 */
	private function get_folder_exclusion() {
		$core = new Boldgrid_Backup_Admin_Core();

		// Ensure we are not in a forced "full backup" state, which bypasses stored/custom values.
		$core->is_backup_full                 = false;
		$core->is_archiving_update_protection = false;
		$core->pre_auto_update                = false;
		$core->is_backup_now                  = false;

		return new Boldgrid_Backup_Admin_Folder_Exclusion( $core );
	}

	/**
	 * Test that Linux core dumps are excluded by default.
	 *
	 * Issue #538: exclude Linux core dump files (core.*) from backups by default.
	 *
	 * @since SINCEVERSION
	 */
	public function test_core_dumps_in_default_exclude() {
		$folder_exclusion = $this->get_folder_exclusion();

		$this->assertStringContainsString(
			'core.*',
			$folder_exclusion->default_exclude,
			'The default exclude list should contain the core.* pattern.'
		);
	}

	/**
	 * Test the core.* pattern matches Linux core dump filenames.
	 *
	 * @since SINCEVERSION
	 */
	public function test_core_dump_pattern_matches() {
		$folder_exclusion = $this->get_folder_exclusion();

		// Core dumps in the root and in subdirectories should match.
		$this->assertTrue( $folder_exclusion->is_match( 'core.*', 'core.12345' ) );
		$this->assertTrue( $folder_exclusion->is_match( 'core.*', 'wp-content/uploads/core.98765' ) );
		$this->assertTrue( $folder_exclusion->is_match( 'core.*', 'core.' ) );

		// Unrelated files that merely contain "core" should not match.
		$this->assertFalse( $folder_exclusion->is_match( 'core.*', 'wp-content/plugins/core-plugin/file.php' ) );
		$this->assertFalse( $folder_exclusion->is_match( 'core.*', 'wp-content/plugins/mycore.php' ) );
		$this->assertFalse( $folder_exclusion->is_match( 'core.*', 'wp-includes/index.php' ) );
	}

	/**
	 * Test that a core dump file is excluded when using the default settings.
	 *
	 * @since SINCEVERSION
	 */
	public function test_core_dump_excluded_by_default() {
		$folder_exclusion = $this->get_folder_exclusion();

		// Simulate default include/exclude settings.
		$folder_exclusion->include = '*';
		$folder_exclusion->exclude = $folder_exclusion->default_exclude;

		$this->assertFalse(
			$folder_exclusion->allow_file( 'wp-content/uploads/core.12345' ),
			'A Linux core dump should be excluded when using the default exclude settings.'
		);

		// A normal file should still be allowed.
		$this->assertTrue(
			$folder_exclusion->allow_file( 'wp-content/uploads/photo.png' ),
			'A normal file should be included when using the default settings.'
		);
	}

	/**
	 * Test that users can override the default and keep core dumps in their backup.
	 *
	 * @since SINCEVERSION
	 */
	public function test_core_dump_included_when_default_overridden() {
		$folder_exclusion = $this->get_folder_exclusion();

		// User removed core.* from their exclude list.
		$folder_exclusion->include = '*';
		$folder_exclusion->exclude = '.git,node_modules,wp-content/cache';

		$this->assertTrue(
			$folder_exclusion->allow_file( 'wp-content/uploads/core.12345' ),
			'A Linux core dump should be included when the user overrides the default exclude settings.'
		);
	}

	/**
	 * Test that the default exclude value can be filtered.
	 *
	 * @since SINCEVERSION
	 */
	public function test_default_exclude_is_filterable() {
		$callback = function () {
			return '.git,node_modules';
		};

		add_filter( 'boldgrid_backup_default_folder_exclude', $callback );

		$folder_exclusion = $this->get_folder_exclusion();

		$this->assertEquals( '.git,node_modules', $folder_exclusion->default_exclude );

		remove_filter( 'boldgrid_backup_default_folder_exclude', $callback );
	}
}
