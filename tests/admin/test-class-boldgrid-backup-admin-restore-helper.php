<?php
/**
 * File: test-class-boldgrid-backup-admin-restore-helper.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Restore_Helper
 *
 * @since 1.5.1
 */
class Test_Boldgrid_Backup_Admin_Restore_Helper extends WP_UnitTestCase {
	/**
	 * Helper under test.
	 *
	 * @var Boldgrid_Backup_Admin_Restore_Helper|null
	 */
	private $helper;

	/**
	 * Absolute paths created for monitored files.
	 *
	 * @var array
	 */
	private $monitor_paths = array();

	/**
	 * Action hooks registered by a test.
	 *
	 * @var array
	 */
	private $restore_hooks = array();

	/**
	 * Teardown.
	 */
	public function tear_down() {
		if ( $this->helper ) {
			remove_action( 'shutdown', array( $this->helper, 'shutdown' ) );
		}

		foreach ( $this->restore_hooks as $hook ) {
			remove_all_actions( $hook );
		}

		foreach ( $this->monitor_paths as $path ) {
			if ( is_string( $path ) && file_exists( $path ) ) {
				unlink( $path );
			}

			$copy = $path . '.bgb';
			if ( is_string( $path ) && file_exists( $copy ) ) {
				unlink( $copy );
			}
		}

		$this->helper        = null;
		$this->monitor_paths = array();
		$this->restore_hooks = array();

		parent::tear_down();
	}

	/**
	 * A changed monitored file is detected from the hash recorded before restore.
	 */
	public function test_changed_file_is_detected_from_recorded_hash() {
		$path   = $this->create_monitor_file( 'changed', 'before-restore' );
		$helper = $this->helper_for(
			array(
				'changed' => 'changed',
			)
		);
		$detected = false;
		$this->listen( 'changed', $detected );

		$helper->pre_restore( array( 'dryrun' => false ) );

		$this->assertSame( sha1( 'before-restore' ), $helper->monitor_files['changed']['pre_sha1'] );

		file_put_contents( $path, 'after-restore' );
		$helper->post_restore( array( 'dryrun' => false ) );

		$this->assertTrue( $detected );
	}

	/**
	 * An unchanged monitored file does not count as restored.
	 */
	public function test_unchanged_file_is_not_reported_changed() {
		$this->create_monitor_file( 'same', 'unchanged' );
		$helper   = $this->helper_for(
			array(
				'same' => 'same',
			)
		);
		$detected = false;
		$this->listen( 'same', $detected );

		$helper->pre_restore( array( 'dryrun' => false ) );
		$this->assertSame( sha1( 'unchanged' ), $helper->monitor_files['same']['pre_sha1'] );

		$helper->post_restore( array( 'dryrun' => false ) );

		$this->assertFalse( $detected );
	}

	/**
	 * A dry run returns before hashing or comparing monitored files.
	 */
	public function test_dry_run_skips_hash_comparison() {
		$path   = $this->create_monitor_file( 'dry', 'original' );
		$helper = $this->helper_for(
			array(
				'dry' => 'dry',
			)
		);
		$detected = false;
		$this->listen( 'dry', $detected );

		$helper->pre_restore( array( 'dryrun' => true ) );
		$this->assertArrayNotHasKey( 'pre_sha1', $helper->monitor_files['dry'] );

		file_put_contents( $path, 'edited-during-dry-run' );
		$helper->post_restore( array( 'dryrun' => true ) );

		$this->assertFalse( $detected );
		$this->assertArrayNotHasKey( 'pre_sha1', $helper->monitor_files['dry'] );
	}

	/**
	 * Create a monitored file under ABSPATH and return its path.
	 *
	 * @param string $name    Monitor key and filename stem.
	 * @param string $content File contents.
	 * @return string
	 */
	private function create_monitor_file( $name, $content ) {
		$path = ABSPATH . 'bgbkup-monitor-' . $name . '.txt';
		file_put_contents( $path, $content );
		$this->monitor_paths[] = $path;

		return $path;
	}

	/**
	 * Build a helper that watches the given monitor keys.
	 *
	 * @param array $keys Map of monitor key => filename stem.
	 * @return Boldgrid_Backup_Admin_Restore_Helper
	 */
	private function helper_for( array $keys ) {
		$helper = new Boldgrid_Backup_Admin_Restore_Helper();
		$files  = array();

		foreach ( $keys as $key => $name ) {
			$files[ $key ] = array(
				'filename'  => 'bgbkup-monitor-' . $name . '.txt',
				'copy'      => false,
				'keep_copy' => false,
				'copied'    => false,
			);
		}

		$helper->monitor_files = $files;
		$this->helper          = $helper;

		return $helper;
	}

	/**
	 * Record whether the post-restore action for one monitored file runs.
	 *
	 * @param string $key      Monitor key.
	 * @param bool   $detected Set true when the action runs.
	 */
	private function listen( $key, &$detected ) {
		$hook                  = 'boldgrid_backup_post_restore_' . $key;
		$this->restore_hooks[] = $hook;

		add_action(
			$hook,
			function () use ( &$detected ) {
				$detected = true;
			}
		);
	}
}
