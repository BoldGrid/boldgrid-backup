<?php
/**
 * File: class-boldgrid-backup-admin-migrate-tx.php
 * 
 * The main class for the transmitting ( tx ) of the Transfer process.
 * 
 * @link https://www.boldgrid.com
 * @since 1.17.0
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Transfer_Tx
 * 
 * The main class for the transmitting ( tx ) of the Transfer process.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate_Tx {
	/**
	 * Boldgrid_Transfer Core
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 1.17.0
	 */
	public $migrate_core;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 1.17.0
	 */
	public $util;

	/**
	 * Rest
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Tx_Rest
	 * 
	 * @since 1.17.0
	 */
	public $rest;

	/**
	 * Dump Status Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $db_dump_status_option_name;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 1.17.0
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core  = $migrate_core;
		$this->util          = $this->migrate_core->util;
		$this->rest          = new Boldgrid_Backup_Admin_Migrate_Tx_Rest( $migrate_core );

		$this->db_dump_status_option_name = $this->migrate_core->configs['option_names']['db_dump_status'];

		$this->add_hooks();
	}

	/**
	 * Add hooks
	 * 
	 * @since 1.17.0
	 */
	public function add_hooks() {
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
	}

	public function db_dump_is_pending() {
		$dump_status_option = $this->migrate_core->util->get_option( $this->db_dump_status_option_name, '' );
		if ( ! $dump_status_option ) {
			return false;
		}

		$status_file = $dump_status_option['db_status_file'];

		if ( ! file_exists( $status_file ) ) {
			error_log( 'File Does not exist: ' . $status_file );
			return false;
		}

		$status = json_decode( file_get_contents( $status_file ), true );
		$this->migrate_core->log->add( 'DB Dump Status: ' . json_encode( $status, JSON_PRETTY_PRINT ) );

		if ( 'pending' === $status['status'] ) {
			return true;
		}

		if ( 'dumping' === $status['status'] ) {
			return $this->maybe_restart_dump( $status, $status_file );
		}
	}

	public function maybe_restart_dump( $status, $status_file ) {
		if ( 'complete' === $status['status'] || 'pending' === $status['status'] ) {
			return false;
		}

		$time_since_modified = time() - filemtime( $status['file'] );

		if ( $this->migrate_core->configs['stalled_timeout'] > $time_since_modified ) {
			return false;
		}

		$times_restarted = isset( $status['restarted'] ) ? $status['restarted'] : 0;

		// IF we've restarted 5 times already, then fail
		if ( 5 <= $times_restarted ) {
			$this->migrate_core->log->add( 'Failed to restart DB Dump. Time since modified: ' . $time_since_modified );
			// Update Status File
			file_put_contents( $status_file, json_encode( array(
				'status'    => 'failed',
				'file'      => $status['file'],
				'db_size'   => $status['db_size'],
				'restarted' => $times_restarted,
			) ) );
			return false;
		}

		$times_restarted = $times_restarted + 1;

		$this->migrate_core->log->add( 'Restarting DB Dump. Time since modified: ' . $time_since_modified );

		// Update Status File
		file_put_contents( $status_file, json_encode( array(
			'status'    => 'pending',
			'file'      => $status['file'],
			'db_size'   => $status['db_size'],
			'restarted' => $times_restarted,
		) ) );
	
		// Delete the failed file if it exists
		if ( file_exists( $status['file'] ) ) {
			wp_delete_file( $status['file'] );
		}

		return true;
	}

	public function create_dump_status_file( $transfer_id, $dest_url ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
		$dest_dir       = $this->migrate_core->util->url_to_safe_directory_name( $dest_url );
		$dump_dir       = $this->migrate_core->util->get_transfer_dir() . '/' . $dest_dir . '/' . $transfer_id;
		$db_size        = WP_Debug_Data::get_database_size();
		$db_dump_file   = $dump_dir . '/db-' . DB_NAME . '-export-' . gmdate('Y-m-d-H-i-s');
		$response       = json_encode( array(
			'status'  => 'pending',
			'file'    => $db_dump_file,
			'db_size' => $db_size,
		) );
		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );
		$this->migrate_core->log->add( 'Creating DB Dump Status File: ' . $dump_dir . '/db-dump-status.json' );

		$this->migrate_core->util->create_dirpath( $dump_dir . '/db-dump-status.json' );
		file_put_contents( $dump_dir . '/db-dump-status.json', $response );
		update_option( $this->db_dump_status_option_name, array(
			'db_dump_file'   => $db_dump_file,
			'db_status_file' => $dump_dir . '/db-dump-status.json',
		) );
	}

	/**
	 * Generate a database dump
	 * 
	 * @since 1.17.0
	 */
	public function generate_db_dump() {
		$db_status_option = $this->migrate_core->util->get_option( $this->db_dump_status_option_name, array() );

		$db_dump_file = $db_status_option['db_dump_file'];

		$this->migrate_core->log->add( 'Generating DB Dump: ' . $db_dump_file );

		$dump_dir = dirname( $db_dump_file );
		$progress = json_decode( file_get_contents( $dump_dir . '/db-dump-status.json' ), true );

		$progress['status'] = 'dumping';

		file_put_contents( $dump_dir . '/db-dump-status.json', json_encode( $progress ) );

		$db_dump = new Boldgrid_Backup_Admin_Db_Dump( $this->migrate_core->backup_core );

		$increased_max_execution = set_time_limit( 0);

		if ( $increased_max_execution ) {
			$this->migrate_core->log->add( 'Increased Max Execution Time for db dumping process' );
		} else {
			$this->migrate_core->log->add(
				'Failed to Increase Max Execution Time for db dumping process. ' .
				'Database dumping may fail if the database is too large. ' .
				'Consider increasing the max_execution_time in your php configuration'
			);
		}

		$db_dump->dump( $db_dump_file );

		$progress['status'] = 'complete';

		$this->migrate_core->log->add( 'DB Dump Complete: ' . $db_dump_file );

		$this->migrate_core->backup_core->cron->entry_delete_contains( 'direct-transfer.php' );

		file_put_contents( $dump_dir . '/db-dump-status.json', json_encode( $progress ) );
	}
}
