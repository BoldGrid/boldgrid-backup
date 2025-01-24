<?php
/**
 * File: class-boldgrid-backup-admin-migrate-rx.php
 * 
 * The main class for the receiving ( rx ) of the Transfer process.
 * 
 * @link https://www.boldgrid.com
 * @since 0.0.1
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Rx
 * 
 * The main class for the receiving ( rx ) of the Transfer process.
 *
 * @since 0.0.1
 */
class Boldgrid_Backup_Admin_Migrate_Rx {
	/**
	 * Boldgrid_Backup_Admin_Migrate
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 0.0.1
	 */
	public $migrate_core;

	/**
	 * Rest API instance
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Rx_Rest
	 * 
	 * @since 0.0.1
	 */
	public $rest;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 0.0.1
	 */
	public $util;

	/**
	 * Transfers Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.1
	 */
	public $transfers_option_name;

	/**
	 * File List Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $lists_option_name;

	/**
	 * Open Batches Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $open_batches_option_name;

	/**
	 * Authenticated Sites Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $authd_sites_option_name;

	/**
	 * Bytes Received Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $bytes_received_option_name;

	/**
	 * Active Transfer Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $active_transfer_option_name;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core   = $migrate_core;

		$this->transfers_option_name           = $this->migrate_core->configs['option_names']['transfers'];
		$this->lists_option_name               = $this->migrate_core->configs['option_names']['file_lists'];
		$this->open_batches_option_name        = $this->migrate_core->configs['option_names']['open_batches'];
		$this->authd_sites_option_name         = $this->migrate_core->configs['option_names']['authd_sites'];
		$this->bytes_received_option_name      = $this->migrate_core->configs['option_names']['bytes_received'];
		$this->active_transfer_option_name     = $this->migrate_core->configs['option_names']['active_transfer'];
		$this->cancelled_transfers_option_name = $this->migrate_core->configs['option_names']['cancelled_transfers'];
		
		$this->rest = new Boldgrid_Backup_Admin_Migrate_Rx_Rest( $migrate_core );
		$this->util = $this->migrate_core->util;
		$this->add_hooks();
	}

	/**
	 * Add hooks
	 * 
	 * @since 0.0.1
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_boldgrid_transfer_check_status', array( $this, 'ajax_check_status' ) );
		add_action( 'wp_ajax_boldgrid_transfer_start_rx', array( $this, 'ajax_start_handler' ) );
		add_action( 'wp_ajax_boldgrid_transfer_cancel_transfer', array( $this, 'ajax_cancel_transfer' ) );
		add_action( 'wp_ajax_boldgrid_transfer_delete_transfer', array( $this, 'ajax_delete_transfer' ) );
		add_action( 'wp_ajax_boldgrid_transfer_resync_database', array( $this, 'ajax_resync_database' ) );
		add_action( 'boldgrid_transfer_process_transfers', array( $this, 'wp_cron_process_transfers' ) );

		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );

		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( 'init', array( $this, 'cron_activation' ) );
		add_action( 'init', array( $this, 'handle_fatal_errors' ) );
		add_action( 'shutdown', array( $this, 'cron_deactivation' ) );
	}

	public function handle_fatal_errors() {
		register_shutdown_function( function () {
			$error = error_get_last();

			if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE ) ) ) {
				return;
			}

			$this->migrate_core->log->add( 'Fatal Error: ' . json_encode( $error, JSON_PRETTY_PRINT ) );
		} );
	}

	/**
	 * Ajax Resync Database
	 * 
	 * @since 0.0.9
	 * 
	 * @return void
	 */
	public function ajax_resync_database() {
		check_ajax_referer( 'boldgrid_transfer_resync_database', 'nonce' );

		// Delete the database file and reset the transfer status to 'pending-db-dump'.
		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );
		$transfers   = $this->util->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to resync an invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$transfer = $transfers[ $transfer_id ];

		$transfer_dir = $this->util->get_transfer_dir();

		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );

		$db_file_name = basename( $transfer['db_dump_info']['file'] );

		$db_dump_path = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/' . $db_file_name;

		$deleted = unlink( $db_dump_path );

		if ( $deleted ) {
			$this->update_transfer_prop( $transfer_id, 'status', 'pending-db-dump' );
			$this->migrate_core->log->add( 'Database dump file deleted and pending re-sync: ' . $transfer_id );
			wp_send_json_success( array( 'message' => 'Database dump file deleted and pending re-sync' ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting database dump file for transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => 'Error deleting database dump file.' ) );
		}
	}

	/**
	 * WP Cron Process Transfers.
	 * 
	 * This is just a wrapper for the 'process_transfers' method
	 * but I was having trouble confirming that an action was being
	 * called in the cron job, so I added this method to confirm that
	 * in the backtrace.
	 *
	 * @return void
	 */
	public function wp_cron_process_transfers() {
		$this->migrate_core->log->add( 'Processing Transfers via WP Cron.' );

		$php_sapi_name = php_sapi_name();

		if ( 'cli' === $php_sapi_name ) {
			$this->migrate_core->log->add( 'Processing via CLI....' );
			$namespace   = $this->migrate_core->configs['rest_api_namespace'] . '/';
			$prefix      = $this->migrate_core->configs['rest_api_prefix'] . '/';
			$nonce       = wp_create_nonce( 'boldgrid_transfer_cron_resume_transfer' );
			wp_remote_get(
				home_url( '/wp-json/' . $namespace . $prefix . 'cron_resume_transfer' ) . '?nonce=' . $nonce,
				array(
					'timeout' => $this->migrate_core->configs['conn_timeout'],
				)
			);
		} else {
			$this->process_transfers();
		}
	}


	/**
	 * Add cron interval
	 * 
	 * @since 0.0.1
	 */
	public function add_cron_interval( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => $this->migrate_core->configs['cron_interval'],
			'display'  => esc_html__( 'Every Minute' ),
		);

		return $schedules;
	}

	/**
	 * Cron Deactivation
	 * 
	 * Check the transfers list for any pending or active
	 * transfers. If there are none, then deactivate the cron
	 * job.
	 * 
	 * @since 0.0.3
	 */
	public function cron_deactivation() {
		$incomplete_transfers = $this->get_incomplete_transfers();

		if ( wp_next_scheduled( 'boldgrid_transfer_process_transfers' ) && empty( $incomplete_transfers ) ) {
			wp_clear_scheduled_hook( 'boldgrid_transfer_process_transfers' );
			$this->migrate_core->log->add( 'Cron Deactivated: boldgrid_transfer_process_transfers' );
		}
	}

	/**
	 * cron_activation
	 * 
	 * @since 0.0.1
	 */
	public function cron_activation() {
		$incomplete_transfers = $this->get_incomplete_transfers();

		if ( empty( $incomplete_transfers ) ) {
			return;
		}

		$active_transfer = array_shift( $incomplete_transfers );

		$transfer_id = $active_transfer['transfer_id'];

		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		if ( ! wp_next_scheduled( 'boldgrid_transfer_process_transfers' ) ) {
			$scheduled = wp_schedule_event( time(), 'every_minute', 'boldgrid_transfer_process_transfers' );
			$this->migrate_core->log->add( 'Cron Activation: ' . json_encode( $scheduled ) );
		}
	}

	public function ajax_delete_transfer() {
		check_ajax_referer( 'boldgrid_transfer_delete_transfer', 'nonce' );

		global $wp_filesystem;

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );
		$transfers   = $this->util->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to delete invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$transfer = $transfers[ $transfer_id ];

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$deleted = $wp_filesystem->delete( $transfer_dir, true );

		$this->cleanup_filelists();

		if ( $deleted ) {
			unset( $transfers[ $transfer_id ] );
			update_option( $this->transfers_option_name, $transfers, false );

			$cancelled_transfers = $this->util->get_option( $this->cancelled_transfers_option_name, array() );
			$cancelled_transfers = array_filter( $cancelled_transfers, function( $id ) use ( $transfer_id ) {
				return $id !== $transfer_id;
			} );

			update_option( $this->cancelled_transfers_option_name, array_values( $cancelled_transfers ), false );
			$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' deleted.' );
			wp_send_json_success( array( 'message' => 'Transfer Deleted' ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => 'Error Deleting Transfer' ) );
		}

	}

	public function cleanup_filelists() {
		$transfers = $this->util->get_option( $this->transfers_option_name, array() );
		$transfer_ids = array_keys( $transfers );

		$file_lists = $this->util->get_option( $this->lists_option_name, array() );

		foreach( $file_lists as $transfer_id => $file_list ) {
			if ( ! in_array( $transfer_id, $transfer_ids ) ) {
				unset( $file_lists[ $transfer_id ] );
			}
		}

		update_option( $this->lists_option_name, $file_lists, false );
	}

	public function ajax_cancel_transfer() {
		check_ajax_referer( 'boldgrid_transfer_cancel_transfer', 'nonce' );

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );

		$transfers = $this->util->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to cancel invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$this->util->cancel_transfer( $transfer_id );

		$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' cancelled by user.' );

		wp_send_json_success( array( 'message' => 'Transfer Cancelled' ) );
	}

	/**
	 * Start the transfer process
	 * 
	 * @since 0.0.1
	 */
	public function ajax_start_handler() {
		check_ajax_referer( 'boldgrid_transfer_start_rx', 'nonce' );

		$site_url = sanitize_text_field( $_POST['url'] );

		$authd_sites = $this->util->get_option( $this->authd_sites_option_name, array() );
		
		if ( ! isset( $authd_sites[ $site_url ] ) ) {
			$this->migrate_core->log->add( 'Site ' . $site_url . ' not authenticated.' );
			wp_send_json_error( array( 'message' => 'Site not authenticated' ) );
		}

		$auth = $authd_sites[ $site_url ];

		$this->create_new_transfer( $site_url, $auth['user'], $auth['pass'] );
	}

	/**
	 * Create a new transfer
	 * 
	 * @since 0.0.1
	 */
	public function create_new_transfer( $site_url, $user, $pass ) {
		$transfer_status = 'pending';

		$this->util->update_transfer_heartbeat();

		$this->cleanup_filelists();

		$transfer_id = $this->util->gen_transfer_id();

		$max_upload_size = $this->util->get_max_upload_size();

		$source_wp_version = $this->util->rest_get(
			$site_url,
			'get-wp-version',
			'wp_version'
		);

		$transfer = array(
			'transfer_id'        => $transfer_id,
			'status'             => $transfer_status,
			'source_site_url'    => $site_url,
			'dest_site_url'      => get_site_url(),
			'source_wp_version'  => $source_wp_version,
			'rx_max_upload_size' => $max_upload_size,
			'transfer_rate'      => 0,
			'start_time'         => microtime( true ), 
			'end_time'           => 0,
			'time_elapsed'       => 0,
		);

		$transfer_list = $this->util->get_option( $this->transfers_option_name, array() );

		$transfer_list[ $transfer_id ] = $transfer;

		update_option( $this->transfers_option_name, $transfer_list, false );

		$this->update_open_batches( array() );
		update_option( $this->bytes_received_option_name, 0, false );

		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		$this->migrate_core->log->add( 'Created new transfer: ' . $transfer_id );
		$this->migrate_core->log->add( 'Transfer Info: ' . json_encode( $transfer ) );

		wp_send_json_success( array( 'success' => true, 'transfer_id' => $transfer_id ) );
	}

	/**
	 * Process the active transfer
	 * 
	 * @since 0.0.1
	 */
	public function process_active_transfer( $transfer ) {
		$transfer_id     = $transfer['transfer_id'];
		$transfer_status = $transfer['status'];

		update_option( $this->active_transfer_option_name, $transfer_id, false );

		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		$this->fix_stalled_transfer( $transfer );

		switch( $transfer_status ) {
			case 'pending':
				$this->generate_file_lists( $transfer );
				$this->update_transfer_prop( $transfer_id, 'status', 'transferring-large-files' );
				$this->process_large_files_rx( $transfer_id );
				break;
			case 'transferring-large-files':
				$this->verify_files( $transfer_id );
				$this->process_large_files_rx( $transfer_id );
				break;
			case 'transferring-small-files':
				$this->verify_files( $transfer_id );
				$this->process_small_files_rx( $transfer_id );
				break;
			case 'pending-db-dump':
				$this->start_db_dump( $transfer );
				break;
			case 'dumping-db-tables':
				$this->check_dump_status( $transfer );
				break;
			case 'db-dump-complete':
				$this->maybe_split_dump( $transfer_id );
				break;
			case 'db-ready-for-transfer':
			case 'db-transferring':
				$this->process_db_rx( $transfer_id );
				break;
		}
	}

	/**
	 * Maybe Split DB dump file
	 * 
	 * @since 0.0.8
	 * 
	 * @param array $transfer Transfer data
	 */
	public function maybe_split_dump( $transfer_id ) {
		$transfers    = $this->util->get_option( $this->transfers_option_name, array() );
		$transfer     = $transfers[ $transfer_id ];
		$db_dump_info = $transfer['db_dump_info'];
	
		// Get DB Info.
		$db_file_path = $db_dump_info['file'];
		$db_file_size = $db_dump_info['db_size'];
		$db_file_hash = $db_dump_info['db_hash'];
		
		$max_upload_size = $this->util->get_max_upload_size();
		if ( $db_file_size > $max_upload_size ) {
			$this->migrate_core->log->add( 'Database dump file size exceeds the maximum upload size and must be split' );
			$db_files = $this->split_db_file( $transfer );
		} else {
			$this->migrate_core->log->add( 'Databse dump file does not need to be split' );
			$db_files = array( 'part-0' => array( 'path' => $db_file_path, 'status' => 'pending' ) );
		}

		if ( empty( $db_files ) ) {
			$this->migrate_core->log->add( 'Error splitting database dump file' );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'db-dump-complete' );
			return new WP_Error( 'boldgrid_transfer_rx_db_split_error', __( 'There was an error splitting the database dump file.', 'boldgrid-transfer' ) );
		}

		$db_dump_info['split_files'] = $db_files;

		$this->update_transfer_prop( $transfer_id, 'db_dump_info', $db_dump_info );
		$this->update_transfer_prop( $transfer_id, 'status', 'db-ready-for-transfer' );

		$this->process_db_rx( $transfer_id );
	}

	public function check_dump_status( $transfer ) {

		$response = $this->util->rest_post(
			$transfer['source_site_url'],
			'check-dump-status',
			array(
				'transfer_id' => $transfer['transfer_id'],
				'dest_url'    => $transfer['dest_site_url'],
			),
			true
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error checking database dump status: ' . $response->get_error_message() );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'failed' );
			return $response;
		} else if ( ! isset( $response['db_dump_info'] ) ) {
			$this->migrate_core->log->add( 'No db_dump_info in response: ' . json_encode( $response ) );
			return;
		}

		$db_dump_info = $response['db_dump_info'];

		$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $db_dump_info );

		$this->migrate_core->log->add(
			sprintf(
				'Database Dump Status: %1$s / %2$s (%3$s%%)',
				size_format( $db_dump_info['file_size'], 2 ),
				size_format( $db_dump_info['db_size'], 2 ),
				number_format( ( $db_dump_info['file_size'] / $db_dump_info['db_size'] ) * 100, 2 )
			)
		);

		if ( 'complete' === $db_dump_info['status'] ) {
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'db-dump-complete' );
			$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', array(
				'file'      => $db_dump_info['file'],
				'status'    => 'complete',
				'db_size'   => $db_dump_info['db_size'],
				'file_size' => $db_dump_info['db_size'],
				'db_hash'   => $db_dump_info['db_hash'],
			) );
			$this->maybe_split_dump( $transfer['transfer_id'] );
		}
	}

	public function start_db_dump( $transfer ) {
		$site_url = $transfer['source_site_url'];

		$response = $this->util->rest_post(
			$site_url,
			'start-db-dump',
			array(
				'transfer_id' => $transfer['transfer_id'],
				'dest_url' => $transfer['dest_site_url'],
			),
			true
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error starting database dump: ' . $response->get_error_message() );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'failed' );
			return $response;
		} else if ( isset( $response['db_dump_info'] ) ) {
			$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $response['db_dump_info'] );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'dumping-db-tables' );
			$this->check_dump_status( $transfer );
		}
	}

	
	public function generate_db_dump( $transfer ) {
		if ( $transfer['status'] === 'pending' ) {
			
		} else if ( $transfer['status'] === 'dumping-db-tables' ) 
		// Generate Database Dump
		$generate_db_dump = $this->util->rest_get(
			$site_url,
			'generate-db-dump',
			'generate_db_dump'
		);

		if ( is_wp_error( $generate_db_dump ) ) {
			$this->migrate_core->log->add( 'Error generating database dump: ' . $generate_db_dump->get_error_message() );
			$this->update_transfer_prop( $transfer_id, 'status', 'failed' );
			return $generate_db_dump;
		}
	}

	/**
	 * Generate File Lists
	 * 
	 * @since 0.0.8
	 */
	public function generate_file_lists( $transfer ) {
			$site_url = $transfer['source_site_url'];
			// Generate File List & hashes;
			$file_list = $this->util->rest_get(
				$site_url,
				'generate-file-list',
				'file_list'
			);
	
			if ( is_wp_error( $file_list ) ) {
				$this->migrate_core->log->add( 'Error generating file list: ' . $file_list->get_error_message() );
				return $file_list;
			}

			//Determine Largest File.
			$largest_file_size = $this->util->get_largest_file_size( json_decode( $file_list, true ) );
			$max_upload_size   = $this->util->get_max_upload_size();

			$this->update_transfer_prop( $transfer['transfer_id'], 'largest_file_size', $largest_file_size );

			$file_lists = $this->util->get_option( $this->lists_option_name, array() );

			[ $small_file_list, $large_file_list ] = $this->util->split_file_list( $file_list, $max_upload_size );

			$file_lists[ $transfer['transfer_id'] ] = array(
				'small' => $small_file_list,
				'large' => $large_file_list
			);

			$file_lists = update_option( $this->lists_option_name, $file_lists, false );
	}

	/**
	 * Get Incomplete Transfers
	 * 
	 * Retrieve list of incomplete transfers
	 * 
	 * @since 0.0.3
	 * 
	 * @return array List of incomplete transfers
	 */
	public function get_incomplete_transfers() {
		$transfers = $this->util->get_option( $this->transfers_option_name, array() );

		$incomplete_transfers = array();

		$non_pending_statuses = array(
			'completed',
			'failed',
			'canceled',
		);

		foreach( $transfers as $transfer_id => $transfer ) {
			if ( ! in_array( $transfer['status'], $non_pending_statuses ) ) {
				$incomplete_transfers[] = $transfer;
			}
		}

		return $incomplete_transfers;
	}

	/**
	 * Process the transfers
	 * 
	 * @since 0.0.1
	 */
	public function process_transfers() {
		$incomplete_transfers = $this->get_incomplete_transfers();

		if ( empty( $incomplete_transfers ) ) {
			$this->migrate_core->log->add( 'No Incomplete Transfers to process' );
			return;
		}

		$this->process_active_transfer( array_shift( $incomplete_transfers ) );
	}

	public function fix_stalled_transfer( $transfer ) {
		$time_since_last_heartbeat = time() - $this->util->get_transfer_heartbeat();

		// If the last heartbeat was more than 120 seconds ago, then find the stalled file / files and retry them.
		if ( $this->migrate_core->configs['stalled_timeout'] > $time_since_last_heartbeat ) {
			return;
		}

		$time_since_last_heartbeat = $this->util->convert_to_mmss( $time_since_last_heartbeat );
		$this->migrate_core->log->add( 'Transfer has likely stalled: Time Since Last Heartbeat: ' . $time_since_last_heartbeat );

		switch( $transfer['status'] ) {
			case 'transferring-large-files':
				$this->retry_stalled_large_files( $transfer );
				$this->util->update_transfer_heartbeat();
				$this->process_transfers();
				break;
			case 'transferring-small-files':
				$this->retry_stalled_small_files( $transfer );
				$this->util->update_transfer_heartbeat();
				$this->process_transfers();
				break;
			case 'db-transferring':
				$this->retry_stalled_db_file( $transfer );
				$this->util->update_transfer_heartbeat();
				$this->process_transfers();
				break;
		}
	}

	/** Retry Stalled DB File
	 * 
	 * @since 0.0.8
	 */
	public function retry_stalled_db_file( $transfer ) {
		$transfer_id = $transfer['transfer_id'];
		$db_dump_info = $transfer['db_dump_info'];
		$split_files = $db_dump_info['split_files'];

		foreach( $split_files as $part_number => $file ) {
			if ( 'transferring' === $file['status'] ) {
				$this->migrate_core->log->add( 'Retrying stalled db file: ' . $file['path'] );
				$db_dump_info['split_files'][ $part_number ]['status'] = 'pending';
			}
		}

		$this->update_transfer_prop( $transfer_id, 'db_dump_info', $db_dump_info );
	}

	/**
	 * Retry stalled large files
	 * 
	 * @since 0.0.1
	 */
	public function retry_stalled_large_files( $transfer ) {
		// Stalled files will be marked as 'transferring' in the file list.
		// We need to find the stalled files, and retry them.
		$transfer_id = $transfer['transfer_id'];
		$file_lists  = $this->util->get_option( $this->lists_option_name, array() );
		$file_list   = json_decode( $file_lists[ $transfer_id ]['large'], true );

		foreach( $file_list as $file ) {
			if ( 'transferring' === $file['status'] || 'splitting' === $file['status'] ) {
				$this->migrate_core->log->add( 'Retrying stalled file: ' . $file['path'] );
				$this->util->update_file_status( $transfer_id, 'large', $file['path'], 'pending' );
			}
		}
	}

	/**
	 * Handle AJAX request to verify files.
	 *
	 * @since 0.0.1
	 */
	public function ajax_check_status() {
		check_ajax_referer( 'boldgrid_transfer_check_status', 'nonce' );

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );
		$transfers   = $this->util->get_option( $this->transfers_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to check the status of an invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			return array(
				'error' => true,
				'message' => 'Invalid transfer ID: ' . $transfer_id,
			);
		}
	
		$transfer  = $transfers[ $transfer_id ];
		$status    = $transfer['status'];

		$progress_data = array();

		$elapsed_time = microtime( true ) - intval( $this->get_transfer_prop( $transfer_id, 'start_time', 0 ) );
		$progress_data['elapsed_time'] = $this->migrate_core->util->convert_to_mmss( $elapsed_time );
		switch( $status ) {
			case 'failed':
				$progress_data['status'] = 'failed';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Transfer Failed';
				$progress_data['progress_status_text'] = 'Failed';
				break;
			case 'completed':
				$progress_data['status'] = 'completed';
				$progress_data['progress'] = 100;
				$progress_data['progress_text'] = 'Transfer Complete';
				$progress_data['progress_status_text'] = 'Completed';
				break;
			case 'pending':
				$progress_data['status'] = 'pending';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Transfer Still Pending';
				$progress_data['progress_status_text'] = 'Pending';
				$this->process_transfers();
				break;
			case 'dumping-db-tables':
				$this->check_dump_status( $transfer );
				$db_size   = $transfer['db_dump_info']['db_size'];
				$dump_size = isset( $transfer['db_dump_info']['file_size'] ) ? $transfer['db_dump_info']['file_size'] : 0;
				$progress  = $db_size > 0 ? ( $dump_size / $db_size ) * 100 : 0;

				$progress_text = sprintf(
					'%1$s / %2$s (%3$s%%)',
					size_format( $dump_size, 2 ),
					size_format( $db_size, 2 ),
					number_format( $progress, 2 )
				);
				$progress_data['status'] = 'dumping-db-tables';
				$progress_data['progress']             = $progress;
				$progress_data['progress_text']        = $progress_text;
				$progress_data['progress_status_text'] = 'Dumping Database Tables';
				break;
			case 'db-dump-complete':
				$db_size   = $transfer['db_dump_info']['db_size'];
				$progress_data['status'] = 'db-dump-complete';
				$progress_data['progress'] = 100;
				$progress_data['progress_text'] = $progress_text = sprintf(
					'%1$s / %2$s (%3$s%%)',
					size_format( $db_size, 2 ),
					size_format( $db_size, 2 ),
					number_format( 100, 2 )
				);
				$progress_data['progress_status_text'] = 'Database Dump Complete. Pending Transfer';
				break;
			case 'db-ready-for-transfer':
				$progress_data['status'] = 'db-ready-for-transfer';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Database Pending Transfer';
				$progress_data['progress_status_text'] = 'Database Pending Transfer';
				break;
			case 'db-transferring':
				$db_part_count = count( $transfer['db_dump_info']['split_files'] );
				$completed_count = count(
					array_filter( $transfer['db_dump_info']['split_files'], function( $file ) {
						return 'transferred' === $file['status'];
					} )
				);
				$progress = $db_part_count > 0 ? ( $completed_count / $db_part_count ) * 100 : 0;
				$progress_data['status'] = 'db-transferring';
				$progress_data['progress'] = $db_part_count > 0 ? ( $completed_count / $db_part_count ) * 100 : 0;
				$progress_data['progress_text'] = $progress_text = sprintf(
					'%1$s / %2$s Files (%3$s%%)',
					$completed_count,
					$db_part_count,
					number_format( $progress, 2 )
				);
				$progress_data['progress_status_text'] = 'Transferring Database';
				$this->process_transfers();
				break;
			case 'transferring-small-files':
			case 'transferring-large-files':
				$progress_data = $this->verify_files( $transfer_id );
				if ( isset ( $verification_data['error'] ) && $verification_data['error'] ) {
					wp_send_json_error( array( 'message' => $verification_data['message'] ) );
				}
				break;
		}

		wp_send_json_success( $progress_data );
	}

	/**
	 * Verify the files for a transfer.
	 *
	 * @since 0.0.1
	 *
	 * @param string $transfer_id The ID of the transfer to verify.
	 * @return array Verification data.
	 */
	public function verify_files( $transfer_id, $include_missing_files = false ) {
		// Reset execution time limit
		set_time_limit( max( ini_get( 'max_execution_time' ), $this->migrate_core->configs['cron_interval'] ) );

		$transfers   = $this->util->get_option( $this->transfers_option_name, array() );
		$file_lists  = $this->util->get_option( $this->lists_option_name, array() );

		$response = array();

		if ( $include_missing_files ) {
			$response['missing_files'] = array();
		}

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to verify files for an invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			return array(
				'error' => true,
				'message' => 'Invalid transfer ID: ' . $transfer_id,
			);
		}

		$transfer  = $transfers[ $transfer_id ];

		if ( 'db-transferred' === $transfer['status'] ) {
			$response['status'] = 'db-transferred';
			return $response;
		}

		$file_list = $file_lists[ $transfer_id ];

		$files = ( 'transferring-small-files' === $transfer['status'] )
			? ( json_decode( $file_list['small'], true ) ?: array() )
			: ( json_decode( $file_list['large'], true ) ?: array() );

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$total_file_count      = count( $files );
		$completed_count       = 0;
		$md5_failed_files      = array();
		$files_not_received    = array();
		$files_marked_complete = 0;

		$file_status_counts = array();

		foreach ( $files as $file ) {
			if ( 'transferred' === $file['status'] ) {
				$completed_count++;
			}
			// Get the status of the file, and increase the count of the
			// associated status in the $file_status_counts array.
			// If the status doesn't exist in the array yet, create the key,
			// and set the value to 1.
			if ( isset( $file['status'] ) ) {
				if ( isset( $file_status_counts[ $file['status'] ] ) ) {
					$file_status_counts[ $file['status'] ]++;
				} else {
					$file_status_counts[ $file['status'] ] = 1;
				}
			}
		}

		foreach ( $files as $file ) {
			$rcvd_file = $transfer_dir . $file['path'];
			if ( ! file_exists( $rcvd_file ) ) {
				$files_not_received[] = $file;
			}
		}

		$progress = $total_file_count > 0 ? ( $completed_count / $total_file_count ) * 100 : 0;

		$progress_text = sprintf(
			'%1$s / %2$s Files (%3$s%%)',
			$completed_count,
			$total_file_count,
			number_format( $progress, 2 )
		);

		$status = $transfers[ $transfer_id ]['status'];

		if ( $completed_count === $total_file_count && 'transferring-small-files' === $status ) {
			$this->update_transfer_prop( $transfer_id, 'status', 'pending-db-dump' );
			$status       = 'pending-db-dump';
		} else if ( $completed_count === $total_file_count && 'transferring-large-files' === $status ) {
			$this->update_transfer_prop( $transfer_id, 'status', 'transferring-small-files' );
			$status = 'transferring-small-files';
			$elapsed_time = $this->update_elapsed_time( $transfer_id );
		}

		$elapsed_time         = $this->update_elapsed_time( $transfer_id, false );
		$progress_status_text = $this->get_progress_status_text( $status );
		$bytes_received       = $this->util->get_option( $this->bytes_received_option_name, 0 );
		$transfer_rate        = $bytes_received / $elapsed_time;

		$this->migrate_core->log->add(
			sprintf(
				'Transfer %1$s progress: %2$s%5$c%6$c' .
				'Elapsed Time: %3$s%5$c%6$c' .
				'Avg Transfer Rate: %4$s',
				$transfer_id,
				$progress_text,
				$this->util->convert_to_mmss( $elapsed_time ),
				size_format( $transfer_rate, 2 ) . '/s',
				10,
				9
			)
		);

		$verification_data = array(
			'transfer_id'   => $transfer_id,
			'status'        => $transfer['status'],
			'progress'      => $progress,
			'progress_text' => $progress_text,
			'progress_status_text' => $progress_status_text,
			'elapsed_time'  => $this->migrate_core->util->convert_to_mmss( $elapsed_time ),
			'status_counts' => $file_status_counts,
		);

		if ( $include_missing_files ) {
			$verification_data['missing_files'] = $files_not_received;
		}

		return $verification_data;
	}

	/**
	 * Complete Transfer
	 * 
	 * Once a transfer is confirmed as complete,
	 * this method runs to update the transfer status,
	 * log the completion, and do any necessary cleanup.
	 * 
	 * @param string $transfer_id Transfer ID
	 * 
	 * @since 0.0.5
	 */
	public function complete_transfer( $transfer_id ) {
		$this->update_transfer_prop( $transfer_id, 'status', 'completed' );
		$elapsed_time = microtime( true ) - intval( $this->get_transfer_prop( $transfer_id, 'start_time', 0 ) );
		$this->update_transfer_prop( $transfer_id, 'end_time', microtime( true ) );
		$this->update_transfer_prop( $transfer_id, 'time_elapsed', $elapsed_time );
		$bytes_rcvd   = $this->util->get_option( $this->bytes_received_option_name, 0 );
		
		// Count the number of files transferred.
		$transfers     = $this->util->get_option( $this->transfers_option_name, array() );
		$transfer      = $transfers[ $transfer_id ];
		$file_lists    = $this->util->get_option( $this->lists_option_name, array() );
		$file_list     = $file_lists[ $transfer_id ];
		$file_count    = count( json_decode( $file_list['small'], true ) ) + count( json_decode( $file_list['large'], true ) );
		$bytes_per_sec = $bytes_rcvd / $elapsed_time;
		
		$this->migrate_core->log->add(
			sprintf( 
				'Transfer %1$s completed.%6$c%7$c' .
				'Total Size Transferred: %2$s%6$c%7$c' .
				'Total Files Transferred: %3$s%6$c%7$c' .
				'Time Elapsed: %4$s%6$c%7$c'.
				'Average Transfer Rate: %5$s',
				$transfer_id,
				size_format( $bytes_rcvd, 2 ),
				$file_count,
				$this->util->convert_to_mmss( $elapsed_time ),
				size_format( $bytes_per_sec ) . '/s',
				10,
				9
			)
		);
		update_option( $this->active_transfer_option_name, false, false );
	}

	/**
	 * Get progress status text based on transfer status.
	 *
	 * @param string $status Transfer status.
	 * @return string Progress status text.
	 */
	private function get_progress_status_text( $status ) {
		switch( $status ) {
			case 'pending-db-tx':
			case 'transferring-db':
				return 'Transferring Database';
			case 'db-transferred':
				return 'Pending File Transfer';
			case 'transferring-small-files':
				return 'Transferring Small Files';
			case 'transferring-large-files':
				return 'Transferring Large Files';
			case 'completed':
				return 'Transfer Complete';
			default:
				return '';
		}
	}

	public function retry_stalled_small_files( $transfer ) {
		$transfer_id = $transfer['transfer_id'];
		$file_list   = $this->util->get_option( $this->lists_option_name, array() );
		
		$verification_data = $this->verify_files( $transfer_id, true );

		// Remove any files that are not marked as 'transferred'.
		$missing_files = array();
		$pending_files = array();

		foreach( $verification_data['missing_files'] as $file ) {
			if ( 'pending' === $file['status'] ) {
				$pending_files[] = $file['path'];
			} else {
				$missing_files[] = $file['path'];
			}
		}

		if ( empty( $missing_files ) ) {
			return;
		}

		$num_missing_files = count( $missing_files );
		$num_pending_files = count( $pending_files );

		$this->migrate_core->log->add( "There are $num_pending_files that are still pending" );
		$this->migrate_core->log->add( "There are $num_missing_files that stalled or failed initial transfer attempts and will be retried" );
		$this->util->update_bulk_file_status( $transfer_id, $missing_files, 'pending' );
	}

	public function remove_stale_batches() {
		$open_batches = $this->util->get_option( $this->open_batches_option_name, array() );

		// Select all open batches that are older than 2 minutes.
		$stale_batches = array_filter( $open_batches, function( $batch ) {
			return time() - $batch > $this->migrate_core->configs['stalled_timeout'];
		} );

		if ( empty( $stale_batches ) ) {
			return;
		}

		$this->migrate_core->log->add( 'Removing stale batches: ' . json_encode( $stale_batches ) );

		$open_batches = array_diff_key( $open_batches, $stale_batches );

		update_option( $this->open_batches_option_name, $open_batches, false );
	}

	public function determine_open_batches() {
		wp_cache_delete( 'boldgrid_transfer_open_batches', 'options' );

		$this->remove_stale_batches();
		
		return count( $this->util->get_option( $this->open_batches_option_name, array() ) );
	}

	public function update_open_batches( $open_batches = array() ) {
		wp_cache_delete( $this->open_batches_option_name, 'options' );
			
		update_option( $this->open_batches_option_name, $open_batches, false );
	}

	public function update_transfer_prop( $transfer_id, $key, $value ) {
		wp_cache_delete( $this->transfers_option_name, 'options' );
		$transfers = $this->util->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return;
		}

		if ( 'status' === $key && $transfers[ $transfer_id ][ $key ] !== $value ) {
			$this->migrate_core->log->add(
				"Transfer $transfer_id status updated from {$transfers[ $transfer_id ][ $key ]} to $value"
			);
		}

		$transfers[ $transfer_id ][ $key ] = $value;

		$this->util->update_transfer_heartbeat();

		wp_cache_delete( $this->transfers_option_name, 'options' );
		return update_option( $this->transfers_option_name, $transfers, false );
	}

	public function process_small_files_rx( $transfer_id ) {
		// Reset execution time limit
		set_time_limit( max( ini_get( 'max_execution_time' ), $this->migrate_core->configs['cron_interval'] ) );
		$transfers  = $this->util->get_option( $this->transfers_option_name, array() );
		$file_lists = $this->util->get_option( $this->lists_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			return;
		}

		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ]['small'];

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 2 );
		$file_batch = $this->create_file_batch( $transfer, json_decode( $file_list, true ) );
		if ( empty( $file_batch ) ) {
			return;
		}
		$this->process_batch( $transfer, $file_batch, 'retrieve-files' );

		$depth_of_stack = count( debug_backtrace() );

		if ( 0 >= intval( $this->determine_open_batches() ) && 100 > $depth_of_stack ) {
			$this->process_transfers();
		}
	}

	public function split_large_files( $transfer_id ) {
		$transfers  = $this->util->get_option( $this->transfers_option_name, array() );
		$file_lists = $this->util->get_option( $this->lists_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			return;
		}

		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ]['large'];


		// Remove any files that are not either pending or failed.
		$files = json_decode( $file_list, true );
	
		$pending_files = array_filter( $files, function( $file ) {
			return 'pending' === $file['status'] || 'failed' === $file['status'];
		} );

		if ( empty( $pending_files ) ) {
			return;
		}

		foreach( $pending_files as $file ) {
			$this->util->update_file_status( $transfer_id, 'large', $file['path'], 'splitting' );
		}

		$pending_file_list = json_encode( $pending_files );

		$response = $this->util->rest_post(
			$transfer['source_site_url'],
			'split-large-files',
			array(
				'transfer_id' => $transfer_id,
				'max_upload_size' => $transfer['rx_max_upload_size'],
				'files' => $pending_file_list,
			),
			true
		);

		if ( ! isset( $response['split_files' ] ) ) {
			$this->migrate_core->log->add( 'Error splitting large files: ' . json_encode( $response ) );
			return;
		}

		$split_files = json_decode( $response['split_files'], true );

		// Update the file list with the split files.
		foreach( $split_files as $split_file ) {
			$path = $split_file['path'];
			// check the $files array for a file with the same path
			$index = array_search( $path, array_column( $files, 'path' ) );
			// if the file is found, update the status to 'ready-to-transfer'
			// and add the split files to the file list
			if ( false !== $index ) {
				$files[ $index ]['status'] = 'ready-to-transfer';
				$files[ $index ]['parts']  = $split_file['parts'];
				$files[ $index ]['md5']    = $split_file['md5'];
			}
		}

		$file_lists[ $transfer_id ]['large'] = json_encode( $files );

		update_option( $this->lists_option_name, $file_lists, false );
	}

	public function process_large_files_rx( $transfer_id ) {
		// Reset execution time limit
		set_time_limit( max( ini_get( 'max_execution_time' ), $this->migrate_core->configs['cron_interval'] ) );

		$this->split_large_files( $transfer_id );
		
		$transfers  = $this->util->get_option( $this->transfers_option_name, array() );
		$transfer   = $transfers[ $transfer_id ];

		$file_lists = $this->util->get_option( $this->lists_option_name, array() );
		$file_list  = $file_lists[ $transfer_id ]['large'];

		$file_list = json_decode( $file_list, true );

		// Only process files that are marked as 'ready-to-transfer'
		$split_files = array_filter( $file_list, function( $file ) {
			return 'ready-to-transfer' === $file['status'];
		} );

		foreach ( $split_files as $index => $split_file ) {
			$this->util->update_file_status( $transfer_id, 'large', $split_file['path'], 'transferring' );
			$this->process_large_file_rx( $transfer, $split_file );
		}
	}

	public function add_open_batch( $batch_id ) {
		wp_cache_delete( 'boldgrid_transfer_open_batches', 'options' );
		$open_batches = $this->util->get_option( $this->open_batches_option_name, array() );
		$open_batches[ $batch_id ] = time();

		update_option( $this->open_batches_option_name, $open_batches, false );
		$this->util->update_transfer_heartbeat();
	}

	public function remove_open_batch( $batch_id ) {
		wp_cache_delete( $this->open_batches_option_name, 'options' );
		$open_batches = $this->util->get_option( $this->open_batches_option_name, array() );
		unset( $open_batches[ $batch_id ] );

		update_option( $this->open_batches_option_name, $open_batches, false );
	}

	public function process_batch( $transfer, $file_batch, $route ) {
		$site_url    = $transfer['source_site_url'];
		$transfer_id = $transfer['transfer_id'];

		$ch_batch = array();

		$mh          = curl_multi_init();
		$namespace   = $this->migrate_core->configs['rest_api_namespace'] . '/';
		$prefix      = $this->migrate_core->configs['rest_api_prefix'] . '/';
		$request_url = $site_url . '/wp-json/' . $namespace . $prefix . $route;

		$authd_sites = $this->util->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			$this->migrate_core->log->add( 'Site ' . $site_url . ' not authenticated.' );
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$num_open_batches = $this->determine_open_batches();

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );

		$start_time = microtime( true );
		$total_bytes = 0;

		$total_bytes_received = 0;
		
		foreach( $file_batch as $batch_id => $batch ) {
			$files = array();

			$this->add_open_batch( $batch_id );

			foreach( $batch as $file ) {
				$files[] = $file['path'];
			}

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $request_url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, array(
				'user'     => $user,
				'pass'     => base64_encode( $pass ),
				'batch_id' => $batch_id,
				'files'    => json_encode( $files, true ),
			) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			$ch_batch[ $batch_id ] = $ch;

			curl_multi_add_handle( $mh, $ch_batch[ $batch_id ] );
		}

		do {
			Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 4 );
			$status = curl_multi_exec( $mh, $active );
			$transfer_is_canceled = $this->transfer_is_canceled( $transfer_id );
			if ( $active && ! $transfer_is_canceled ) {
				curl_multi_select( $mh );
			}
		} while ( $active && $status == CURLM_OK && ! $transfer_is_canceled );

		$end_time = microtime( true );
		$total_time = $end_time - $start_time;

		foreach ( $ch_batch as $batch_id => $ch ) {
			if ( $this->transfer_is_canceled( $transfer_id ) ) {
				break;
			}
			$response = curl_multi_getcontent( $ch );
			$body     = json_decode( $response, true );

			// Get transfer info
			$info                  = curl_getinfo( $ch );
			$total_bytes_received += intval( $info['size_upload'] );

			if ( isset( $body['success'] ) ) {
				if ( isset( $body['files'] ) ) {
					Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 10 );
					$files = json_decode( $body['files'], true );
					$processed_files = array();
					foreach( $files as $file_path => $file ) {
						$status = $this->process_small_file_rx( $transfer_id, $file_path, $file );
						if ( isset( $processed_files[ $status ] ) ) {
							$processed_files[ $status ][] = $file_path;
						} else {
							$processed_files[ $status ] = array( $file_path );
						}
					}
					foreach( $processed_files as $status => $file_paths ) {
						$this->util->update_bulk_file_status( $transfer_id, $file_paths, $status );
					}
				}
			} else {
				$this->migrate_core->log->add( 'Error Receiving Batch: ' . json_encode( $info, JSON_PRETTY_PRINT ) );
				if ( 403 === $info['http_code'] ) {
					$this->migrate_core->log->add( '403 error: ' . $response );
				}
				$this->util->update_bulk_file_status( $transfer_id, $file_batch[ $batch_id ], 'failed' );
			}

			if ( isset( $body['batch_id'] ) ) {
				$this->remove_open_batch( $body['batch_id'] );
			}

			curl_multi_remove_handle( $mh, $ch );

			// Reset execution time limit
			set_time_limit( max( ini_get( 'max_execution_time' ), $this->migrate_core->configs['cron_interval'] ) );

			wp_cache_delete( 'boldgrid_transfer_cancelled_transfers', 'options' );
		}

		$bytes_received_before_batch = $this->util->get_option( $this->bytes_received_option_name, 0 );

		update_option( $this->bytes_received_option_name, $bytes_received_before_batch + $total_bytes_received, false );

		curl_multi_close( $mh );
	}

	public function get_transfer_prop( $transfer_id, $property, $fallback ) {
		wp_cache_delete( $this->transfers_option_name, 'options' );

		$transfers = $this->util->get_option( $this->transfers_option_name, array() );
	
		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return $fallback;
		}

		if ( ! isset( $transfers[ $transfer_id ][ $property ] ) ) {
			return $fallback;
		}

		return $transfers[ $transfer_id ][ $property ];
	}

	public function create_file_batch( $transfer, $file_list ) {
		$transfer_id     = $transfer['transfer_id'];
		$open_batches    = $this->determine_open_batches();
		$num_of_chunks   = $this->migrate_core->configs['batch_chunks'] - $open_batches;
		$max_batch_size  = $num_of_chunks * $this->migrate_core->configs['chunk_size'];
		$max_upload_size = $this->util->get_max_upload_size();
		$max_upload_size = $max_upload_size * 0.1;

		$batch = array();
		$current_batch_size = 0;

		if ( 0 === $num_of_chunks ) {
			$this->migrate_core->log->add( 'All chunks are currently in use. Waiting for a chunk to become available.' );
			return $batch;
		}

		$pending_files = array_filter( $file_list, function( $file ) {
			return 'pending' === $file['status'] || 'failed' === $file['status'];
		} );

		if ( empty( $pending_files ) ) {
			$this->retry_stalled_small_files( $transfer );
			$file_lists = $this->util->get_option( $this->lists_option_name, array() );
			$file_list  = json_decode( $file_lists[ $transfer_id ]['small'], true );
		}

		// Check if 'transfer_rate is set and greater than zero
		if ( isset( $transfer['transfer_rate'] ) && 0 < $transfer['transfer_rate'] ) {
			// Calculate the maximum batch size that can be transferred in approximately 1 minute
			$transfer_rate = $transfer['transfer_rate'];
			$max_transfer_rate = $transfer_rate * 60;
		} else {
			$max_transfer_rate = $max_upload_size;
		}

		foreach ( $file_list as $file ) {
			$file_size = $file['size'];

			// Skip files that are not pending
			if ( 'pending' !== $file['status'] ) {
				continue;
			}

			// If a single file is larger than what can be transferred in 1 minute
			if ( $file_size > $max_transfer_rate ) {
				// If batch is empty, add the large file as the only file in the batch
				if ( empty( $batch ) ) {
					$batch[] = $file;
					$current_batch_size += $file_size;
					$open_batches++;
				}
				// Break after adding the large file.
				break;
			}

			// Check if adding the file exceeds the maximum transfer size for 1 minute.
			if ( $current_batch_size + $file_size > $max_transfer_rate ) {
				break;
			}

			if ( $current_batch_size + $file_size > $max_upload_size ) {
				break;
			}

			if ( count( $batch ) + 1 >= $max_batch_size ) {
				break;
			}

			$batch[] = $file;
			$current_batch_size += $file_size;
			$open_batches++;
		}

		if ( empty( $batch ) ) {
			return;
		}

		$this->migrate_core->log->add(
			'Number of Files in current batch: ' . count( $batch ) . ' - Current Batch Size: ' . size_format( $current_batch_size )
		);

		$this->util->update_bulk_file_status( $transfer_id, $batch, 'transferring' );

		$batches = array_chunk( $batch, $this->migrate_core->configs['chunk_size'] );

		$keyed_batches = array();

		foreach( $batches as $batch ) {
			$batch_id = wp_generate_password( 8, false );
			$keyed_batches[ $batch_id ] = $batch;
		}

		return $keyed_batches;
	}

	/**
	 * Process Large File RX
	 *
	 * Process a large file by using curl to retrieve each file part,
	 * and then reassemble the file. The file is then checked against
	 * the MD5 hash to ensure the file was properly received.
	 * 
	 * @param array $transfer
	 * @param array $split_file
	 * @return void
	 */
	public function process_large_file_rx( $transfer, $split_file ) {
		// Use php curl init to retrieve the file parts
		$output_file = $this->util->get_transfer_dir() .
			'/' . $this->util->url_to_safe_directory_name( $transfer['source_site_url'] ) .
			'/' . $transfer['transfer_id'] .
			'/' . $split_file['path'];

		$this->util->create_dirpath( $output_file );
		$handle = fopen( $output_file, 'wb' );

		foreach( $split_file['parts'] as $file_part ) {
			if ( $this->transfer_is_canceled( $transfer['transfer_id'] ) ) {
				break;
			}

			Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 2 );
			$response = $this->util->rest_post(
				$transfer['source_site_url'],
				'retrieve-large-file-part',
				array(
					'transfer_id' => $transfer['transfer_id'],
					'file_part'   => $file_part,
				),
				true
			);

			if ( is_wp_error( $response ) ) {
				$this->migrate_core->log->add( 'Error retrieving large file part: ' . $response->get_error_message() );
				$errors[] = 'Error retrieving large file part: ' . $response->get_error_message();
				break;
			}

			if ( ! isset( $response['file_part'] ) ) {
				$this->migrate_core->log->add( 'Error retrieving large file part: ' . json_encode( $response ) );
				$errors[] = 'Error retrieving large file part: ' . json_encode( $response );
				break;
			}

			$stat_before = fstat( $handle );
			$size_before = $stat_before['size'];
			fwrite( $handle, base64_decode( $response['file_part'] ) );
			$stat_after = fstat( $handle );
			$size_after = $stat_after['size'];

			$bytes_received = $this->util->get_option( $this->bytes_received_option_name, 0 );

			$bytes_received += intval( $size_after - $size_before );

			update_option( $this->bytes_received_option_name, $bytes_received, false );

			$this->util->update_transfer_heartbeat();
		}

		fclose( $handle );

		$md5_hash = md5_file( $output_file );
		
		if ( $md5_hash !== $split_file['md5'] ) {
			$this->migrate_core->log->add( 'MD5 hash mismatch for file: ' . $output_file );
			$errors[] = 'MD5 hash mismatch for file: ' . $output_file;
		}

		if ( ! empty( $errors ) ) {
			$this->util->update_file_status( $transfer['transfer_id'], 'large', $split_file['path'], 'failed' );
		} else {
			$time_before_write_option = microtime( true );
			$this->util->update_file_status( $transfer['transfer_id'], 'large', $split_file['path'], 'transferred' );
			$time_after_write_option = microtime( true );
			error_log( 'Time to write option: ' . ( $time_after_write_option - $time_before_write_option ) );
			$this->util->rest_post(
				$transfer['source_site_url'],
				'delete-large-file-parts',
				array(
					'transfer_id' => $transfer['transfer_id'],
					'file_parts'  => $split_file['parts']
				)
			);
		}
	}

	public function transfer_is_canceled( $transfer_id ) {
		// Check if the transfer still exists:
		$transfer_status = $this->get_transfer_prop( $transfer_id, 'status', 'no transfer found' );
		if ( 'canceled' === $transfer_status || 'no transfer found' === $transfer_status ) {
			return true;
		}

		$cancelled_transfers = $this->util->get_option( $this->cancelled_transfers_option_name, array() );
		if ( in_array( $transfer_id, $cancelled_transfers ) ) {
			return true;
		}

		return false;
	}

	public function process_small_file_rx( $transfer_id, $file_path, $file_data ) {
		global $wp_filesystem;
		$transfers  = $this->util->get_option( $this->transfers_option_name, array() );
		$file_lists = $this->util->get_option( $this->lists_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			return;
		}
		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ]['small'];

		$file_status = 'transferring';

		if ( 'canceled' === $transfer['status'] ) {
			return 'canceled';
		}

		$backup_dir  = $this->util->get_transfer_dir();
		$source_dir  = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$backup_dir  = trailingslashit( $backup_dir );
		$transfer_id = $transfer['transfer_id'];

		$rcvd_file = $backup_dir . $source_dir . '/' . $transfer_id . '/' . $file_path;

		if ( ! $this->util->create_dirpath( $rcvd_file ) ) {
			$status = 'pending';
			$this->migrate_core->log->add( 'There was an error creating the directory path for the file: ' . $rcvd_file );
			return $status;
		}

		$file_contents = base64_decode( $file_data['file'] );
		file_put_contents( $rcvd_file, $file_contents );
		unset( $file_contents );

		if ( ! file_exists( $rcvd_file ) ) {
			$status = 'pending';
			$this->migrate_core->log->add( 'The file was not properly not received: ' . $rcvd_file );
			return $status;
		}

		$new_file_hash = md5_file( $rcvd_file );
		if ( isset( $file_data['md5'] ) ) {
			$old_file_hash = $file_data['md5'];
		} else {
			$old_file_hash = '';
		}

		if ( ! file_exists( $rcvd_file ) ) {
			$status = 'pending';
			$this->migrate_core->log->add( 'The file was not properly not received: ' . $rcvd_file );
			return $status;
		} elseif ( $new_file_hash !== $old_file_hash ) {
			$status = 'pending';
			$filesize = size_format( filesize( $rcvd_file ) );
 			$this->migrate_core->log->add(
				"File hash mismatch for $rcvd_file with a size of $filesize ... Expected: $old_file_hash Got: $new_file_hash",
			);
			$wp_filesystem->delete( $rcvd_file );
			return $status;
		} else {
			$status = 'transferred';
			return $status;
		}
	}

	public function retrieve_db_files( $transfer, $pending_db_files, $db_file_dir ) {
		$part_number = array_key_first( $pending_db_files );
		$file_path   = $pending_db_files[ $part_number ]['path'];

		$db_dump_info = $transfer['db_dump_info'];

		$db_dump_info['split_files'][ $part_number ]['status'] = 'transferring';
		error_log( json_encode( array(
			'method' => __METHOD__,
			'part_number' => $part_number,
			'file_path' => $file_path,
			'db_dump_info' => $db_dump_info,
		) ) );
		$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $db_dump_info );
		
		$response = $this->util->rest_post(
			$transfer['source_site_url'],
			'get-db-dump',
			array(
				'file_path' => $file_path,
			),
			true
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error retrieving database dump file: ' . $response->get_error_message() );
			$db_dump_info['split_files'][ $part_number ]['status'] = 'pending';
			$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $db_dump_info );
			return $response;
		}

		$db_dump_file = $db_file_dir . basename( $file_path );

		if ( ! isset( $response['file'] ) ) {
			error_log( json_encode( array(
				'method' => __METHOD__,
				'response' => $response,
			) ) );
			$db_dump_info['split_files'][ $part_number ]['status'] = 'failed';
			$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $db_dump_info );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'db-dump-complete' );
			return;
		}

		file_put_contents( $db_dump_file, $response['file'] );

		$new_file_hash = md5_file( $db_dump_file );
		$old_file_hash = $response['file_hash'];

		if ( $new_file_hash !== $old_file_hash ) {
			$this->migrate_core->log->add( 'DB Dump file hash mismatch. Expected: ' . $old_file_hash . ' Got: ' . $new_file_hash );
			$db_dump_info['split_files'][ $part_number ]['status'] = 'pending';
			$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $db_dump_info );
			return new WP_Error( 'boldgrid_transfer_rx_db_file_hash_mismatch', __( 'Database dump file hash mismatch', 'boldgrid-transfer' ) );
		}

		$db_dump_info['split_files'][ $part_number ]['status'] = 'transferred';
		$db_dump_info['split_files'][ $part_number ]['path'] = $db_dump_file;
		$this->update_transfer_prop( $transfer['transfer_id'], 'db_dump_info', $db_dump_info );
	}

	public function process_db_rx( $transfer_id ) {
		global $wp_filesystem;
		// Refresh Transfer Info.
		$transfers = $this->migrate_core->util->get_option( $this->transfers_option_name, array() );
		$transfer  = $transfers[ $transfer_id ];

		$db_dump_info = $transfer['db_dump_info'];

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = trailingslashit( $transfer_dir );

		$db_file_dir = $transfer_dir . $source_dir . '/' . $transfer_id . '/';

		$path_created = $this->util->create_dirpath( $db_file_dir . 'db.sql', 0755 );

		if ( ! $path_created ) {
			$this->migrate_core->log->add( 'There was an error creating the directory path for the database dump' );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'pending-db-tx' );
			return new WP_Error( 'boldgrid_transfer_rx_db_dir_error', __( 'There was an error creating the directory path for the database dump.', 'boldgrid-transfer' ) );
		}

		$db_files = $db_dump_info['split_files'];

		error_log( json_encode( array(
			'method' => __METHOD__,
			'path crated' => $path_created,
			'db_files' => $db_files,
		) ) );

		// loop through the split files and return true if any of them are marked 'transferring'
		$transferring = array_filter( $db_files, function( $file, $key ) {
			return 'transferring' === $file['status'];
		}, ARRAY_FILTER_USE_BOTH );
		
		if ( $transferring ) {
			$this->migrate_core->log->add( 'There are still files that are transferring' );
			return;
		}

		// Filter array of db files to only include files that are marked 'pending'
		$pending_db_files = array_filter( $db_files, function( $file, $key ) {
			return 'pending' === $file['status'];
		}, ARRAY_FILTER_USE_BOTH );

		if ( ! empty( $pending_db_files ) ) {
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'db-transferring' );
			$this->retrieve_db_files( $transfer, $pending_db_files, $db_file_dir );
			return;
		}

		// If all files are marked as 'transferred', merge them into a single file
		// and check the md5 hash
		// IF there are more than one transfered files, merge them into a single file
		$completed_files = array_filter( $db_files, function( $file, $key ) {
			return 'transferred' === $file['status'];
		}, ARRAY_FILTER_USE_BOTH );

		if ( count( $completed_files ) !== count( $db_files ) ) {
			$this->migrate_core->log->add( 'Not all database dump files have been transferred' );
			return;
		}

		if ( count( $completed_files ) > 1 ) {
			$db_file_name   = $db_dump_info['file'];
			$merged_db_file = $transfer_dir . $source_dir . '/' . $transfer['transfer_id'] . '/' . basename( $db_file_name );
			$merged_db_file = $this->merge_db_files( $completed_files, $merged_db_file );

			if ( is_wp_error( $merged_db_file ) ) {
				$this->migrate_core->log->add( 'Error merging database dump files: ' . $merged_db_file->get_error_message() );
				$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'db-ready-for-transfer' );
				return $merged_db_file;
			}
			$db_dump_file = $merged_db_file;
		} else if ( count( $completed_files ) === count( $db_files ) ) {
			$db_dump_file = $completed_files['part-0']['path'];
		}

		if ( ! file_exists( $db_dump_file ) ) {
			$this->migrate_core->log->add( 'The database dump file was not properly received: ' . $db_dump_file );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'pending-db-dump' );
			return new WP_Error( 'boldgrid_transfer_rx_db_file_not_received', __( 'The database dump file was not properly received.', 'boldgrid-transfer' ) );
		}
		
		$new_file_hash = md5_file( $db_dump_file );

		if ( $new_file_hash !== $db_dump_info['db_hash'] ) {
			//$wp_filesystem->delete( $db_dump_file );
			$this->migrate_core->log->add( 'DB Dump file hash mismatch. Expected: ' . $db_dump_info['db_hash'] . ' Got: ' . $new_file_hash );
			$this->update_transfer_prop( $transfer['transfer_id'], 'status', 'db-dump-complete' );
			$this->reset_db_transfer( $transfer['transfer_id'] );
			return new WP_Error( 'boldgrid_transfer_rx_db_hash_mismatch', __( 'The database dump file hash does not match the expected hash.', 'boldgrid-transfer' ) );
		} else {
			$this->complete_transfer( $transfer['transfer_id'] );
			return true;
		}
	}

	public function reset_db_transfer( $transfer_id ) {
		$transfers = $this->migrate_core->util->get_option( $this->transfers_option_name, array() );
		$transfer  = $transfers[ $transfer_id ];

		$db_dump_info = $transfer['db_dump_info'];

		// Reset the status of all split files to 'pending'
		foreach( $db_dump_info['split_files'] as $index => $split_file ) {
			$db_dump_info['split_files'][ $index ]['status'] = 'pending';
		}

		$this->update_transfer_prop( $transfer_id, 'db_dump_info', $db_dump_info );

	}

	/**
	 * Send a request to the source site to split the db file into smaller parts
	 * Return with an array of the paths to the split files on the source site
	 */
	public function split_db_file( $transfer ) {
		$transfer_id = $transfer['transfer_id'];
		$db_file     = $transfer['db_dump_info']['file'];
		$source_site = $transfer['source_site_url'];

		$authd_sites = $this->util->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $source_site ] ) ? $authd_sites[ $source_site ] : false;
		$user        = $auth['user'];
		$pass        = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );

		$response = $this->util->rest_post(
			$source_site,
			'split-db-file',
			array(
				'transfer_id'     => $transfer_id,
				'max_upload_size' => $transfer['rx_max_upload_size'],
				'db_file'         => $db_file,
			),
			true
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error splitting database dump file: ' . $response->get_error_message() );
			return array();
		}

		if ( ! isset( $response['split_files'] ) ) {
			$this->migrate_core->log->add( 'Error splitting database dump file: ' . json_encode( $response ) );
			return array();
		}

		$split_files = array();

		foreach( json_decode( $response['split_files'], true ) as $index => $split_file ) {
			$split_files[ 'part-' . $index ] = array( 'path' => $split_file, 'status' => 'pending' );
		}

		return $split_files;
	}

	public function merge_db_files( $db_files, $output_file ) {
		$outHandle = fopen( $output_file, 'wb' );
		if ( ! $outHandle ) {
			// handle error opening output file
		}

		foreach ( $db_files as $db_file ) {
			$inHandle = fopen( $db_file['path'], 'rb' );
			if ( ! $inHandle ) {
				// handle error opening input file
			}

			while ( ! feof( $inHandle ) ) {
				$chunk = fread( $inHandle, 1024 * 1024 ); // read 1 MB at a time
				fwrite( $outHandle, $chunk );
			}
			fclose( $inHandle );
		}

		fclose( $outHandle );

		// Delete the individual db files
		foreach( $db_files as $db_file ) {
			unlink( $db_file['path'] );
		}

		return $output_file;
	}

	public function update_elapsed_time( $transfer_id, $save = true ) {
		$microtime    = microtime( true );
		$elapsed_time = $microtime - $this->get_transfer_prop( $transfer_id, 'start_time', 0 );

		if ( $save ) {
			$this->update_transfer_prop( $transfer_id, 'time_elapsed', $elapsed_time );
		}

		return $elapsed_time;
	}
}