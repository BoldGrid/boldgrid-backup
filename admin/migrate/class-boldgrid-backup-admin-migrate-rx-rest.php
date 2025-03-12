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

		wp_set_current_user( $user->ID );

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
			'transfer-status'       => array(
				'method'   => 'PUT',
				'endpoint' => 'transfer-status/(?P<transfer_id>[A-Za-z0-9]+)/(?P<status>[A-Za-z0-9\-]+)',
			),
			'check-status'          => array(
				'method'   => 'GET',
				'endpoint' => 'check-status',
			),
			'start-migration'       => array(
				'method'   => 'POST',
				'endpoint' => 'start-migration',
			),
			'cancel-transfer'       => array(
				'method'   => 'POST',
				'endpoint' => 'cancel-transfer',
			),
			'delete-transfer'       => array(
				'method'   => 'POST',
				'endpoint' => 'delete-transfer',
			),
			'resync-database'       => array(
				'method'   => 'POST',
				'endpoint' => 'resync-database',
			),
			'start-restore'         => array(
				'method'   => 'POST',
				'endpoint' => 'start-restore',
			),
			'validate-url' => array(
				'method'   => 'GET',
				'endpoint' => 'validate-url',
			),
			'install-total-upkeep'  => array(
				'method'   => 'POST',
				'endpoint' => 'install-total-upkeep',
			),
			'update-total-upkeep'   => array(
				'method'   => 'POST',
				'endpoint' => 'update-total-upkeep',
			),
			'activate-total-upkeep' => array(
				'method'   => 'POST',
				'endpoint' => 'activate-total-upkeep',
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
	 * Update Total Upkeep
	 * 
	 * Callback for endpoint: install-total-upkeep
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function install_total_upkeep( $request ) {
		$url = $request->get_param( 'url' );

		$installed = $this->util->install_total_upkeep( $url );

		if ( is_wp_error( $installed ) ) {
			wp_send_json_error( array( 'message' => $installed->get_error_message() ) );
		} else {
			wp_send_json_success(
				array(
					'message' => sprintf(
						'<p class="notice notice-success">%s</p>',
						esc_html__( 'Total Upkeep has been successfully installed on the source site.', 'boldgrid-backup' )
					)
				)
			);
		}
	}

	/**
	 * Activate Total Upkeep
	 * 
	 * Callback for endpoint: activate-total-upkeep
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function activate_total_upkeep( $request ) {
		$url = $request->get_param( 'url' );

		$activated = $this->util->edit_total_upkeep_status( $url, 'active' );

		if ( is_wp_error( $activated ) ) {
			wp_send_json_error( array( 'message' => $activated->get_error_message() ) );
		} else {
			wp_send_json_success(
				array(
					'message' => sprintf(
						'<p class="notice notice-success">%s</p>',
						esc_html__( 'Total Upkeep has been successfully activated on the source site.', 'boldgrid-backup' )
					)
				)
			);
		}
	}

	/**
	 * Update Total Upkeep
	 * 
	 * Callback for endpoint: update-total-upkeep
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 */
	public function update_total_upkeep( $request ) {
		$url = $request->get_param( 'url' );

		$installed = $this->util->update_total_upkeep( $url );

		if ( is_wp_error( $installed ) ) {
			wp_send_json_error( array( 'message' => $installed->get_error_message() ) );
		} else {
			wp_send_json_success(
				array(
					'message' => sprintf(
						'<p class="notice notice-success">%s</p>',
						esc_html__( 'Total Upkeep has been successfully updated on the source site.', 'boldgrid-backup' )
					)
				)
			);
		}
	}

	/**
	 * Validate URL
	 * 
	 * Validate the url by making the following checks:
	 * 1. Check if the URL is a valid WordPress site.
	 * 2. Check if the REST API is enabled on the source site.
	 * 3. Check for a valid authentication endpoint.
	 * 
	 * Callback for endpoint: validate-url
	 * 
	 * @param WP_REST_Request $request The request object.
	 * 
	 * @since 1.17.0
	 * 
	 * @return WP_REST_Response
	 */
	public function validate_url( $request ) {
		$url = $request->get_param( 'url' );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => $this->migrate_core->configs['conn_timeout'],
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array(
				'error'   => true,
				'message' => 'Error validating URL: ' . $response->get_error_message(),
			), 200 );
		}

		$headers = $response['headers']->getAll();

		/*
		 * All public facing wp sites have the link header added
		 * in order to allow for rest discovery. If this header is not
		 * present, then it's either not a wp site, or the rest api is not enabled.
		 */
		if ( ! isset( $headers['link'] ) ) {
			error_log( 'headers: ' . json_encode( $headers ) );
			return new WP_REST_Response( array(
				'error'   => true,
				'message' => __(
					'Either this is not a WordPress site, or the WordPress REST Api is not enabled. Please enable the REST API on the source site.',
					'boldgrid-backup'
				),
			), 200 );
		}

		$links = explode( ',', $headers['link'] );
		$wp_json_link = array_filter( $links, function( $link ) {
			return false !== strpos( $link, 'rel="https://api.w.org/"' );
		} );

		$rest_api_error_response = new WP_REST_Response( array(
			'error'   => true,
			'message' => __(
				'The REST API is not properly configured on the source site. Please ensure that the REST API is properly configured.',
				'boldgrid-backup'
			),
		), 200 );

		if ( empty( $wp_json_link ) ) {
			return $rest_api_error_response;
		}

		preg_match('/<([^>]+)>/', $wp_json_link[0], $matches );

		$wp_json_url = $matches[1];

		$wp_json_response = wp_remote_get(
			$wp_json_url,
			array(
				'headers' => array(
					'Accept' => 'application/json',
				),
				'timeout' => $this->migrate_core->configs['conn_timeout'],
			)
		);
		

		if ( is_wp_error( $wp_json_response ) ) {
			return $rest_api_error_response;
		}

		$body = wp_remote_retrieve_body( $wp_json_response );
		$body = json_decode( $body, true );
		
		/**
		 * Application passwords are enabled by default, but some security plugins
		 * may disable them. This is determined by the presence of the 'authentication'
		 * and 'application-passwords' keys in the response body.
		 */
		if ( isset( $body['authentication'] ) && ! isset( $body['authentication']['application-passwords']['endpoints']['authorization'] ) ) {
			return new WP_REST_Response( array(
				'error'   => true,
				'message' => __(
					'Application passwords are not enabled on the source site. This may be due to a security plugin such as WordFence. Please enable Application Passwords to continue.',
					'boldgrid-backup'
				),
			), 200 );;
		}

		return new WP_REST_Response( array(
			'success'       => true,
			'auth_endpoint' => $body['authentication']['application-passwords']['endpoints']['authorization'],
		), 200 );

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
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid transfer ID.', 'boldgrid-backup' ) ) );
		}

		// Verify that the database file is valid.
		if ( ! isset( $transfer['db_dump_info']['status'] ) || 'complete' !== $transfer['db_dump_info']['status'] ) {
			$this->migrate_core->log->add( 'Database resync was not completed.' );
			wp_send_json_error( array(
				'success' => false,
				'error'   => esc_html__(
					'Database resync had started but was not completed. Please Resync database.',
					'boldgrid-backup'
				)
			) );
		}

		$result = $this->util->update_transfer_prop( $transfer_id, 'status', 'pending-restore' );

		$this->migrate_core->backup_core->cron->schedule_direct_transfer();
		
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
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid transfer ID.', 'boldgrid-backup' ) ) );
		}

		$transfer_dir = $this->util->get_transfer_dir();

		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );

		$db_dump_info = $transfer['db_dump_info'];

		$db_file_name = basename( $db_dump_info['file'] );

		$db_dump_path = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/' . $db_file_name;

		$deleted = wp_delete_file( $db_dump_path );

		if ( file_exists( $db_dump_path ) ) {
			$deleted = wp_delete_file( $db_dump_path );
		}

		$db_dump_info['status'] = 'pending';

		$this->util->update_transfer_prop( $transfer_id, 'db_dump_info', $db_dump_info );

		update_option( $this->migrate_core->configs['option_names']['active_transfer'], $transfer_id );

		$this->util->update_transfer_prop( $transfer_id, 'resyncing_db', true );
		$this->util->reset_status_time( $transfer_id, 'resyncing-db' );
		$this->util->update_transfer_prop( $transfer_id, 'status', 'pending-db-dump' );
		$this->migrate_core->backup_core->cron->schedule_direct_transfer();
		$this->migrate_core->log->add( 'Database dump file deleted and pending re-sync: ' . $transfer_id );

		wp_send_json_success( array( 'message' => esc_html__( 'Database dump file deleted and pending re-sync', 'boldgrid-backup' ) ) );
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
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid transfer ID.', 'boldgrid-backup' ) ) );
		}

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$deleted = $wp_filesystem->delete( $transfer_dir, true );

		$this->util->cleanup_filelists();

		$files_deleted_from_source = $this->util->rest_post(
			$transfer, 'delete-transfer-files',
			array(
				'transfer_id' => $transfer_id,
				'dest_url'    => $transfer['dest_site_url'],
			)
		);

		$this->migrate_core->log->add( 'Files deleted from source: ' . json_encode( $files_deleted_from_source ) );

		if ( $deleted ) {
			unset( $transfers[ $transfer_id ] );
			update_option( $this->transfers_option_name, $transfers, false );

			$cancelled_transfers = $this->util->get_option( $this->cancelled_transfers_option_name, array() );
			$cancelled_transfers = array_filter( $cancelled_transfers, function( $id ) use ( $transfer_id ) {
				return $id !== $transfer_id;
			} );

			update_option( $this->cancelled_transfers_option_name, array_values( $cancelled_transfers ), false );
			$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' deleted.' );
			wp_send_json_success( array( 'message' => esc_html__( 'Transfer Deleted', 'boldgrid-backup' ) ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => esc_html__( 'Error Deleting Transfer', 'boldgrid-backup' ) ) );
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
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid transfer ID.', 'boldgrid-backup' ) ) );
		}

		$this->util->cancel_transfer( $transfer_id );

		wp_send_json_success( array( 'message' => esc_html__( 'Transfer Cancelled', 'boldgrid-backup' ) ) );
	}

	/**
	 * Check Status
	 * 
	 * Callback for endpoint: check-status
	 * 
	 * TODO: This method has gotten quite a bit long.
	 *       This should eventually be refactored, or
	 *       even moved to it's own class.
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

		$progress_data['status']       = $transfer['status'];
		$progress_data['elapsed_time'] = $this->util->get_elapsed_time( $transfer_id, true );
		switch( $status ) {
			case 'failed':
				$progress_text = $this->util->get_transfer_prop(
					$transfer_id,
					'failed_message',
					__( 'Transfer Failed', 'boldgrid-backup' )
				);
				$progress_data['status']               = 'failed';
				$progress_data['progress']             = 0;
				$progress_data['progress_text']        = esc_html( $progress_text );
				$progress_data['progress_status_text'] = esc_html__(
					'Failed',
					'boldgrid-backup'
				);
				break;
			case 'completed':
				$progress_data['status']               = 'completed';
				$progress_data['progress']             = 100;
				$progress_data['progress_text']        = esc_html__(
					'Transfer Complete',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Completed',
					'boldgrid-backup'
				);
				break;
			case 'restore-completed':
				$progress_data['status']               = 'completed';
				$progress_data['progress']             = 100;
				$progress_data['progress_text']        = esc_html__(
					'Restoration Complete',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Restoration Complete',
					'boldgrid-backup'
				);
				break;
			case 'pending':
				$progress_data['status']               = 'pending';
				$progress_data['progress']             = 0;
				$progress_data['progress_text']        = esc_html__(
					'Transfer Still Pending',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Pending',
					'boldgrid-backup'
				);
				$this->migrate_core->rx->process_transfers();
				break;
			case 'dumping-db-tables':
				$this->migrate_core->rx->check_dump_status( $transfer );
				/*
				 * While the transfer status may technically be
				 * 'dumping-db-tables', until the source site actually
				 * starts the dumping process, the status should be displayed
				 * to the user as pending to avoid confusion.
				*/
				if ( ! is_array( $transfer['db_dump_info'] ) ||
				( isset( $transfer['db_dump_info']['status'] ) && 'pending' === $transfer['db_dump_info']['status'] )
				) {
					$progress_data['status']               = 'pending-db-dump';
					$progress_data['progress']             = 0;
					$progress_data['progress_text']        = esc_html__(
						'Waiting for Source site to dump database tables',
						'boldgrid-backup'
					);
					$progress_data['progress_status_text'] = esc_html__(
						'Pending DB Dump',
						'boldgrid-backup'
					);
					break;
				}

				$db_size   = $transfer['db_dump_info']['db_size'];
				$dump_size = isset( $transfer['db_dump_info']['file_size'] ) ? $transfer['db_dump_info']['file_size'] : 0;
				if ( $dump_size > $db_size ) {
					$db_size = $dump_size;
				}
				
				$progress  = $db_size > 0 ? ( $dump_size / $db_size ) * 100 : 0;

				$progress_text = sprintf(
					'%1$s / %2$s (%3$s%%)',
					size_format( $dump_size, 2 ),
					size_format( $db_size, 2 ),
					number_format( $progress, 2 )
				);
				$progress_data['status']               = 'dumping-db-tables';
				$progress_data['progress']             = esc_html( $progress );
				$progress_data['progress_text']        = esc_html( $progress_text );
				$progress_data['progress_status_text'] = esc_html__(
					'Dumping Database Tables',
					'boldgrid-backup'
				);
				break;
			case 'db-dump-complete':
				$db_size                               = $transfer['db_dump_info']['db_size'];
				$progress_data['status']               = 'db-dump-complete';
				$progress_data['progress']             = 100;
				$progress_data['progress_text']        = esc_html(
					sprintf(
						'%1$s / %2$s (%3$s%%)',
						size_format( $db_size, 2 ),
						size_format( $db_size, 2 ),
						number_format( 100, 2 )
					)
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Database Dump Complete. Pending Transfer',
					'boldgrid-backup'
				);
				$this->migrate_core->backup_core->cron->schedule_direct_transfer();
				break;
			case 'splitting-db-file':
				$progress     = $this->migrate_core->rx->check_split_status( $transfer_id );
				$chunk_count  = $progress['chunk_count'];
				$chunk_number = $progress['chunk_number'];
				$progress     = 0 !== $chunk_count ? $chunk_number / $chunk_count * 100 : 0;	
				$progress_data['status']               = 'splitting-db-dump';
				$progress_data['progress']             = $progress;
				$progress_data['progress_text']        = esc_html( sprintf(
					'%1$s / %2$s Files (%3$s%%)',
					$chunk_number,
					$chunk_count,
					number_format( $progress, 2 )
				) );
				$progress_data['progress_status_text'] = esc_html__(
					'Splitting Database Dump',
					'boldgrid-backup'
				);
				break;
			case 'db-ready-for-transfer':
				$progress_data['status']               = 'db-ready-for-transfer';
				$progress_data['progress']             = 0;
				$progress_data['progress_text']        = esc_html__(
					'Database Pending Transfer',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Database Pending Transfer',
					'boldgrid-backup'
				);
				break;
			case 'db-transferring':
				$db_part_count = count( $transfer['db_dump_info']['split_files'] );
				$completed_count = count(
					array_filter( $transfer['db_dump_info']['split_files'], function( $file ) {
						return 'transferred' === $file['status'];
					} )
				);
				$progress                              = $db_part_count > 0 ? ( $completed_count / $db_part_count ) * 100 : 0;
				$progress_data['status']               = 'db-transferring';
				$progress_data['progress']             = $db_part_count > 0 ? ( $completed_count / $db_part_count ) * 100 : 0;
				$progress_data['progress_text']        = esc_html( sprintf(
					'%1$s / %2$s Files (%3$s%%)',
					$completed_count,
					$db_part_count,
					number_format( $progress, 2 )
				) );
				$progress_data['progress_status_text'] = esc_html__(
					'Transferring Database',
					'boldgrid-backup'
				);
				$this->migrate_core->log->add( 'Transferring DB Files: ' . $progress_data['progress_text'] );
				$this->migrate_core->rx->process_transfers();
				break;
			case 'transferring-small-files':
			case 'transferring-large-files':
				$progress_data = $this->migrate_core->rx->verify_files( $transfer_id );
				if ( isset ( $verification_data['error'] ) && $verification_data['error'] ) {
					wp_send_json_error( array( 'message' => esc_html( $verification_data['message'] ) ) );
				}
				break;
			case 'pending-restore':
				$progress_data['status']               = 'pending-restore';
				$progress_data['progress']             = 0;
				$progress_data['progress_text']        = esc_html__(
					'Pending Restore',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Pending Restore',
					'boldgrid-backup'
				);
				break;
			case 'restoring-files':
				$progress_data['status']               = 'restoring-files';
				$progress_data['progress']             = 0;
				$progress_data['progress_text']        = esc_html__(
					'Restoring Files',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Restoring Files',
					'boldgrid-backup'
				);

				$copy_files_stats = $this->util->get_transfer_prop( $transfer_id, 'copy_files_stats', array() );
				if ( ! empty( $copy_files_stats ) ) {
					$progress_data['progress']      = $copy_files_stats['files_copied'] / $copy_files_stats['total_files'] * 100;
					$progress_data['progress_text'] = esc_html(
						sprintf(
							'%1$s / %2$s files (%3$s%%)',
							$copy_files_stats['files_copied'],
							$copy_files_stats['total_files'],
							number_format( $progress_data['progress'], 2 )
						)
					);
				}
				break;
			case 'restoring-db':
				$settings     = $this->util->get_option( 'boldgrid_backup_settings', array() );
				$backup_dir   = isset( $settings['backup_directory'] ) ? $settings['backup_directory'] : '/var/www/boldgrid_backup';
				$log_file     = $backup_dir . '/active-import.log';
				if ( file_exists( $log_file ) ) {
					$log_data                       = file_get_contents( $log_file );
					$import_stats                   = json_decode( $log_data, true );
					$progress_data['progress']      = intval( $import_stats['completed_lines'] ) / intval( $import_stats['number_of_lines'] ) * 100;
					$progress_data['progress_text'] = esc_html(
						sprintf(
							'%1$s / %2$s lines (%3$s%%)',
							$import_stats['completed_lines'],
							$import_stats['number_of_lines'],
							number_format( $progress_data['progress'], 2 )
						)
					);
				} else {
					$progress_data['progress']      = 0;
					$progress_data['progress_text'] = esc_html__(
						'Restoring Database',
						'boldgrid-backup'
					);
				}
				$progress_data['status']               = 'restoring-db';
				$progress_data['progress_status_text'] = esc_html__(
					'Restoring Database',
					'boldgrid-backup'
				);
				break;
			case 'pending-db-dump':
				$progress_data['status']               = 'pending-db-dump';
				$progress_data['progress']             = 0;
				$progress_data['progress_text']        = esc_html__(
					'Waiting for Source site to dump database tables',
					'boldgrid-backup'
				);
				$progress_data['progress_status_text'] = esc_html__(
					'Pending DB Dump',
					'boldgrid-backup'
				);
				break;
			default:
				$progress_data['progress_text']        = ucwords(
					str_replace( '-', ' ', esc_html( $transfer['status'] ) )
				);
				$progress_data['progress_status_text'] = ucwords(
					str_replace( '-', ' ', esc_html( $transfer['status'] ) )
				);
				$progress_data['progress']             = 0;
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
			wp_send_json_error( array( 'message' => esc_html__( 'Site not authenticated', 'boldgrid-backup' ) ) );
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

		if ( ! $transfer ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid transfer ID.', 'boldgrid-backup' ) ) );
		}

		$this->util->update_transfer_prop( $transfer_id, 'status', $status );

		return new WP_REST_Response( array(
			'success' => true,
			'transfer_id' => $transfer
		), 200 );
	}
}
