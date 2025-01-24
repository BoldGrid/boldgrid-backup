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
	 * Boldgrid_Transfer_Rx_Rest constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;

		$this->transfers_option_name  = $this->migrate_core->configs['option_names']['transfers'];

		$this->namespace = $this->migrate_core->configs['rest_api_namespace']; 
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
		register_rest_route( $this->namespace, '/cron_resume_transfer', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'cron_resume_transfer' ),
			'permission_callback' => array( $this, 'authenticate_local_request' ),
		) );

		register_rest_route( $this->namespace, '/transfer_status/(?P<transfer_id>[A-Za-z0-9]+)/(?P<status>[A-Za-z0-9\-]+)', array(
			'methods'             => 'PUT',
			'callback'            => array( $this, 'update_transfer_status' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );
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