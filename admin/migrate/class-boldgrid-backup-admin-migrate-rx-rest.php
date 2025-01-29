<?php
/**
 * File: class-boldgrid-transfer-rx-rest.php
 * 
 * The trasnmitting rest api class for the Transfer process.
 * 
 * @link https://www.boldgrid.com
 * @since 0.0.1
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Rest
 * 
 * The receiving rest api class for the Transfer process.
 *
 * @since 0.0.1
 */
class Boldgrid_Backup_Admin_Migrate_Rx_Rest {
	/**
	 * Boldgrid_Transfer Core
	 *
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 0.0.1
	 */
	public $migrate_core;

	/**
	 * Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.1
	 */
	public $transfers_option_name;

	/**
	 * Rest API Namespace
	 * 
	 * @var string
	 * 
	 * @since 0.0.1
	 */
	public $namespace;

	/**
	 * Rest API Prefix
	 * 
	 * @var string
	 * 
	 * @since 0.0.1
	 */
	public $prefix;

	/**
	 * Boldgrid_Transfer_Rx_Rest constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;
		$this->util         = $this->migrate_core->util;

		$this->transfers_option_name  = $this->migrate_core->configs['option_names']['transfers'];

		$this->namespace = $this->migrate_core->configs['rest_api_namespace'];
		$this->prefix    = $this->migrate_core->configs['rest_api_prefix'];
	}

	public function authenticate_local_request( $request ) {
		$params = $request->get_params();

		$host = $request->get_header( 'host' );
		
		if ( ! isset( $params['nonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $params['nonce'], 'boldgrid_transfer_cron_resume_transfer' ) ) {
			return false;
		}

		if ( false === strpos( admin_url(), $host ) ) {
			return false;
		}

		return true;
	}

	public function authenticate_request( $request ) {
		$params = $request->get_params();

		// First, check to see if the user has already been authenticated by the Rest API.
		if ( is_user_logged_in() && current_user_can( 'administrator' ) ) {
			return true;
		}

		// If the user is not logged in, check for the user and pass in the request.
		if ( ! isset( $params['user'] ) && ! isset( $params['pass'] ) ) {
			return false;
		}

		$creds = array(
			'user_login'    => $params['user'],
			'user_password' => base64_decode( $params['pass'] ),
			'remember'      => false,
		);

		$user = wp_signon( $creds );

		if ( is_wp_error( $user ) ) {
			return false;
		}

		set_current_user( $user->ID );

		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the rest api routes
	 * 
	 * @since 0.0.1
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, $this->prefix . '/cron-resume-transfer', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'cron_resume_transfer' ),
			'permission_callback' => array( $this, 'authenticate_local_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/transfer-status/(?P<transfer_id>[A-Za-z0-9]+)/(?P<status>[A-Za-z0-9\-]+)', array(
			'methods'             => 'PUT',
			'callback'            => array( $this, 'update_transfer_status' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/check-status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'check_status' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/start-migration', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'start_migration' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/cancel-transfer', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'cancel_transfer' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/delete-transfer', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'delete_transfer' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/resync-database', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'resync_database' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( $this->namespace, $this->prefix . '/start-restore', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'start_restore' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );
	}

	/**
	 * Migrate the site
	 * 
	 * @since 0.0.1
	 */
	public function start_restore( $request ) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';

		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		$transfers = $this->util->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to restore invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$result = $this->migrate_core->rx->update_transfer_prop( $transfer_id, 'status', 'pending-restore' );
		
		wp_send_json_success( $result );
	}

	/**
	 * Ajax Resync Database
	 * 
	 * @since 0.0.9
	 * 
	 * @return void
	 */
	public function resync_database( $request ) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';
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
			$this->migrate_core->rx->update_transfer_prop( $transfer_id, 'status', 'pending-db-dump' );
			$this->migrate_core->log->add( 'Database dump file deleted and pending re-sync: ' . $transfer_id );
			wp_send_json_success( array( 'message' => 'Database dump file deleted and pending re-sync' ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting database dump file for transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => 'Error deleting database dump file.' ) );
		}
	}

	public function delete_transfer( $request ) {
		global $wp_filesystem;

		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';

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

		$this->util->cleanup_filelists();

		if ( $deleted ) {
			unset( $transfers[ $transfer_id ] );
			update_option( $this->transfers_option_name, $transfers, false );

			$cancelled_transfers = $this->util->get_option( $this->migrate_core->rx->cancelled_transfers_option_name, array() );
			$cancelled_transfers = array_filter( $cancelled_transfers, function( $id ) use ( $transfer_id ) {
				return $id !== $transfer_id;
			} );

			update_option( $this->migrate_core->rx->cancelled_transfers_option_name, array_values( $cancelled_transfers ), false );
			$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' deleted.' );
			wp_send_json_success( array( 'message' => 'Transfer Deleted' ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => 'Error Deleting Transfer' ) );
		}

	}

	public function cancel_transfer( $request) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';

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
	 * Check Status of Transfer
	 *
	 * @since 0.0.1
	 */
	public function check_status( $request ) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';

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

		$elapsed_time = microtime( true ) - intval( $this->migrate_core->rx->get_transfer_prop( $transfer_id, 'start_time', 0 ) );
		$progress_data['elapsed_time'] = $this->util->convert_to_mmss( $elapsed_time );
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
			case 'restore-completed':
				$progress_data['status'] = 'completed';
				$progress_data['progress'] = 100;
				$progress_data['progress_text'] = 'Restoration Complete';
				$progress_data['progress_status_text'] = 'Restoration Complete';
				break;
			case 'pending':
				$progress_data['status'] = 'pending';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Transfer Still Pending';
				$progress_data['progress_status_text'] = 'Pending';
				$this->migrate_core->rx->process_transfers();
				break;
			case 'dumping-db-tables':
				$this->migrate_core->rx->check_dump_status( $transfer );
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
				$this->migrate_core->rx->process_transfers();
				break;
			case 'transferring-small-files':
			case 'transferring-large-files':
				$progress_data = $this->migrate_core->rx->verify_files( $transfer_id );
				if ( isset ( $verification_data['error'] ) && $verification_data['error'] ) {
					wp_send_json_error( array( 'message' => $verification_data['message'] ) );
				}
				break;
			case 'pending-restore':
				$progress_data['status'] = 'pending-restore';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Pending Restore';
				$progress_data['progress_status_text'] = 'Pending Restore';
				$this->rx->process_transfers();
				break;
			case 'restoring-files':
				$progress_data['status'] = 'restoring-files';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Restoring Files';
				$progress_data['progress_status_text'] = 'Restoring Files';
				break;
			case 'restoring-db':
				$progress_data['status'] = 'restoring-db';
				$progress_data['progress'] = 0;
				$progress_data['progress_text'] = 'Restoring Database';
				$progress_data['progress_status_text'] = 'Restoring Database';
				break;
		}

		wp_send_json_success( $progress_data );
	}

	/**
	 * Start the migration process
	 * 
	 * @since 0.0.1
	 */
	public function start_migration( $request ) {
		$params   = $request->get_params();
		error_log( json_encode(
			array(
				'method' => 'start_migration',
				'params' => $params
			)
		) );
		$site_url = isset( $params['url'] ) ? sanitize_text_field( $params['url'] ) : '';

		$authd_sites = $this->util->get_option( $this->migrate_core->rx->authd_sites_option_name, array() );
		
		if ( ! isset( $authd_sites[ $site_url ] ) ) {
			$this->migrate_core->log->add( 'Site ' . $site_url . ' not authenticated.' );
			wp_send_json_error( array( 'message' => 'Site not authenticated' ) );
		}

		$auth = $authd_sites[ $site_url ];

		$this->migrate_core->rx->create_new_transfer( $site_url, $auth['user'], $auth['pass'] );
	}

	public function update_transfer_status( $request ) {
		$params = $request->get_params();
		$transfer_id = $params['transfer_id'];
		$status = $params['status'];

		$transfers = $this->migrate_core->util->get_option( $this->transfers_option_name, array() );
		error_log( json_encode(
			array(
				'method' => 'update_transfer_status',
				'transfers' => $transfers
			)
		) );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return new WP_Error(
				'boldgrid_transfer_transfer_not_found',
				'Transfer with id '. $transfer_id . ' not found',
				array( 'status' => 404 )
			);
		}

		$transfers[ $transfer_id ]['status'] = $status;

		update_option( $this->transfers_option_name, $transfers );

		return new WP_REST_Response( array(
			'success' => true,
			'transfer_id' => $transfers[ $transfer_id ]
		), 200 );
	}

	public function cron_resume_transfer( $request ) {
		error_log( 'cron_resume_transfer' );
		$this->migrate_core->rx->process_transfers();
	}
}