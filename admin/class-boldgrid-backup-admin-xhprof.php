<?php
/**
 * The admin-specific PHP profiling methods for the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.2
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin xhprof class.
 *
 * @since 1.2
 */
class Boldgrid_Backup_Admin_Xhprof {
	/**
	 * Is XHProf active?
	 *
	 * @since 1.2
	 * @access private
	 * @var bool
	 */
	private $xhprof_active = false;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 */
	public function __construct() {
		// Set the configuration array.
		Boldgrid_Backup_Admin::get_configs();

		// Try to enable XHProf.
		$this->xhprof_active = $this->xhprof_enable();

		// If XHprof was enabled, then register a shutdown action to disable XHProf and
		// save the run report data to file.
		if ( $this->xhprof_active ) {
			add_action( 'shutdown',
				array(
					$this,
					'xhprof_disable',
				), 10, 0
			);
		}
	}

	/**
	 * Enable XHProf.
	 *
	 * @since 1.2
	 *
	 * @return bool Success; whether or not XHProf was enabled.
	 */
	private function xhprof_enable() {
		// If the action is "heartbeat", then abort.
		if ( ! empty( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) {
			return false;
		}

		// Get configs.
		$configs = Boldgrid_Backup_Admin::get_configs();

		// If available and enabled, then start XHProf.
		if ( ! empty( $configs['xhprof'] ) && extension_loaded( 'xhprof' ) ) {
			xhprof_enable( XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY );

			return true;
		}

		// Unsuccessful.
		return false;
	}

	/**
	 * Disable XHProf, saving report and error logging the report URL.
	 *
	 * @since 1.2
	 *
	 * @return null
	 */
	public function xhprof_disable() {
		// If XHProf is not active, then abort.
		if ( ! $this->xhprof_active ) {
			return;
		}

		// Get configs.
		$configs = Boldgrid_Backup_Admin::get_configs();

		// Save report to the log.
		if ( ! empty( $configs['xhprof'] ) && extension_loaded( 'xhprof' ) ) {
			// Disable XHProf and collect the data return array.
			$xhprof_data = xhprof_disable();

			// If there is no data, then abort.
			if ( empty( $xhprof_data ) ) {
				return;
			}

			// Configure the utils path.
			$xhprof_utils_path = '/usr/share/pear/xhprof_lib/utils';

			// If the utility libraries exists, then load them.
			if ( file_exists( $xhprof_utils_path . '/xhprof_lib.php' ) &&
			file_exists( $xhprof_utils_path . '/xhprof_runs.php' ) ) {
				require_once $xhprof_utils_path . '/xhprof_lib.php';
				require_once $xhprof_utils_path . '/xhprof_runs.php';

				// Save the run data to file.
				$xhprof_runs = new XHProfRuns_Default();
				$run_id = $xhprof_runs->save_run( $xhprof_data, 'xhprof_boldgrid_backup' );

				// Write the report URL to the error log.
				error_log(
					__METHOD__ . ': https://' . $_SERVER['HTTP_HOST'] .
					'/xhprof/index.php?run=' . $run_id . '&source=xhprof_boldgrid_backup'
				);
			}
		}

		return;
	}
}
