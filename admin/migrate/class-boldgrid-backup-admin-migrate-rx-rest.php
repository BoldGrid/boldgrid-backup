<?php
/**
 * File: class-boldgrid-transfer-rx-rest.php
 * 
 * The trasnmitting rest api class for the Transfer process.
 * 
 * @link https://www.boldgrid.com
 * @since 1.17.0
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Rest
 * 
 * The receiving rest api class for the Transfer process.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate_Rx_Rest {
	/**
	 * Boldgrid_Transfer Core
	 *
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 1.17.0
	 */
	public $migrate_core;

	/**
	 * Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $transfers_option_name;

	/**
	 * Cancelled Transfers Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $cancelled_transfers_option_name;

	/**
	 * Rest API Namespace
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $namespace;

	/**
	 * Rest API Prefix
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $prefix;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 1.17.0
	 */
	public $util;

	/**
	 * Boldgrid_Transfer_Rx_Rest constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 1.17.0
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;
		$this->util         = $this->migrate_core->util;

		$this->transfers_option_name           = $this->migrate_core->configs['option_names']['transfers'];
		$this->cancelled_transfers_option_name = $this->migrate_core->configs['option_names']['cancelled_transfers'];

		$this->namespace = $this->migrate_core->configs['rest_api_namespace'];
		$this->prefix    = $this->migrate_core->configs['rest_api_prefix'];
	}

	/**
	 * Authenticate a local request
	 * 
	 * This is used when making rest api requests
	 * WP Cron.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool True if the request is authenticated, false otherwise.
	 * 
	 * @since 1.17.0
	 */
	public function authenticate_local_request( $request ) {
		$params = $request->get_params();

		$host  = $request->get_header( 'host' );
		$nonce = isset( $params['nonce'] ) ? $params['nonce'] : '';
		
		if ( empty( $nonce ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $nonce, 'boldgrid_transfer_cron_resume_transfer' ) ) {
			return false;
		}

		if ( false === strpos( admin_url(), $host ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Authenticate a request
	 * 
	 * This is default method to authenticate all 
	 * rest api requests.
	 *
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @return bool True if the request is authenticated, false otherwise.
	 * 
	 * @since 1.17.0
	 */
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
	 * @since 1.17.0
	 */
	public function register_routes() {
		$rest_routes = array(
			'cron-resume-transfer' => array(
				'method'              => 'GET',
				'endpoint'            => 'cron-resume-transfer',
				'permission_callback' => 'authenticate_local_request',
			),
			'transfer-status'      => array(
				'method'   => 'PUT',
				'endpoint' => 'transfer-status/(?P<transfer_id>[A-Za-z0-9]+)/(?P<status>[A-Za-z0-9\-]+)',
			),
			'check-status'         => array(
				'method'   => 'GET',
				'endpoint' => 'check-status',
			),
			'start-migration'      => array(
				'method'   => 'POST',
				'endpoint' => 'start-migration',
			),
			'cancel-transfer'      => array(
				'method'   => 'POST',
				'endpoint' => 'cancel-transfer',
			),
			'delete-transfer'      => array(
				'method'   => 'POST',
				'endpoint' => 'delete-transfer',
			),
			'resync-database'      => array(
				'method'   => 'POST',
				'endpoint' => 'resync-database',
			),
			'start-restore'        => array(
				'method'   => 'POST',
				'endpoint' => 'start-restore',
			),
		);

		foreach ( $rest_routes as $route => $args ) {
			$req_method  = $args['method'];
			$endpoint    = $args['endpoint'];
			$method_name = str_replace( '-', '_', $route );

			$permission_cb_name = isset( $args['permission_callback'] ) ? $args['permission_callback'] : 'authenticate_request';

			register_rest_route( $this->namespace, $this->prefix . '/' . $endpoint, array(
				'methods'             => $req_method,
				'callback'            => array( $this, $method_name ),
				'permission_callback' => array( $this, $permission_cb_name ),
			) );
		}
	}

	/**
	 * Start Restore
	 * 
	 * Callback for endpoint: start-restore
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function start_restore( $request ) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';

		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		$transfer = $this->util->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$result = $this->util->update_transfer_prop( $transfer_id, 'status', 'pending-restore' );
		
		wp_send_json_success( $result );
	}

	/**
	 * Resync Database
	 * 
	 * Callback for endpoint: resync-database
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function resync_database( $request ) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';
		$transfer    = $this->util->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$transfer_dir = $this->util->get_transfer_dir();

		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );

		$db_file_name = basename( $transfer['db_dump_info']['file'] );

		$db_dump_path = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/' . $db_file_name;

		$deleted = unlink( $db_dump_path );

		if ( $deleted ) {
			$this->util->update_transfer_prop( $transfer_id, 'status', 'pending-db-dump' );
			$this->migrate_core->log->add( 'Database dump file deleted and pending re-sync: ' . $transfer_id );
			wp_send_json_success( array( 'message' => 'Database dump file deleted and pending re-sync' ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting database dump file for transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => 'Error deleting database dump file.' ) );
		}
	}

	/**
	 * Delete Transfer
	 * 
	 * Callback for endpoint: delete-transfer
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function delete_transfer( $request ) {
		global $wp_filesystem;

		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';
		$transfers   = $this->util->get_option( $this->transfers_option_name, array() );
		$transfer    = $this->util->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$deleted = $wp_filesystem->delete( $transfer_dir, true );

		$this->util->cleanup_filelists();

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

	/**
	 * Cancel Transfer
	 * 
	 * Callback for endpoint: cancel-transfer
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function cancel_transfer( $request) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';
		$transfer    = $this->util->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$this->util->cancel_transfer( $transfer_id );

		$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' cancelled by user.' );

		wp_send_json_success( array( 'message' => 'Transfer Cancelled' ) );
	}

	/**
	 * Check Status
	 * 
	 * Callback for endpoint: check-status
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function check_status( $request ) {
		$params      = $request->get_params();
		$transfer_id = isset( $params['transfer_id'] ) ? sanitize_text_field( $params['transfer_id'] ) : '';

		$transfer    = $this->util->get_transfer_from_id( $transfer_id );
		if ( ! $transfer ) {
			return array(
				'error' => true,
				'message' => 'Invalid transfer ID: ' . $transfer_id,
			);
		}

		$status    = $transfer['status'];

		$progress_data = array();

		$elapsed_time = microtime( true ) - intval( $this->util->get_transfer_prop( $transfer_id, 'start_time', 0 ) );
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
	 * Start Migration
	 * 
	 * Callback for endpoint: start-migration
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function start_migration( $request ) {
		$params   = $request->get_params();
		$site_url = isset( $params['url'] ) ? sanitize_text_field( $params['url'] ) : '';

		$authd_sites = $this->util->get_option( $this->migrate_core->rx->authd_sites_option_name, array() );
		
		if ( ! isset( $authd_sites[ $site_url ] ) ) {
			$this->migrate_core->log->add( 'Site ' . $site_url . ' not authenticated.' );
			wp_send_json_error( array( 'message' => 'Site not authenticated' ) );
		}

		$auth = $authd_sites[ $site_url ];

		$this->migrate_core->rx->create_new_transfer( $site_url, $auth['user'], $auth['pass'] );
	}

	/**
	 * Transfer Transfer
	 * 
	 * Callback for endpoint: transfer-status
	 * 
	 * Note: This is a PUT request, and
	 * is used to manually update the status of a transfer.
	 * This is mostly used during testing purposes.
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function transfer_status( $request ) {
		$params      = $request->get_params();
		$transfer_id = $params['transfer_id'];
		$status      = $params['status'];
		$transfer    = $this->util->get_transfer_from_id( $transfer_id );


		if ( $transfer ) {
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$this->util->update_transfer_prop( $transfer_id, 'status', $status );

		return new WP_REST_Response( array(
			'success' => true,
			'transfer_id' => $transfers[ $transfer_id ]
		), 200 );
	}

	/**
	 * Cron Resume Transfer
	 * 
	 * Callback for endpoint: cron-resume-transfer
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function cron_resume_transfer( $request ) {
		error_log( 'cron_resume_transfer' );
		$this->migrate_core->rx->process_transfers();
	}
}