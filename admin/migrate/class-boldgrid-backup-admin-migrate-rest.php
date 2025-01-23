<?php
/**
 * File: class-boldgrid-admin-migrate-rest.php
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
 * Class: Boldgrid_Transfer_Tx_Rest
 * 
 * The transmitting rest api class for the Transfer process.
 *
 * @since 0.0.1
 */
class Boldgrid_Backup_Admin_Migrate_Rest {
	/**
	 * Boldgrid_Transfer Core
	 *
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 0.0.1
	 */
	public $migrate_core;

	/**
	 * Boldgrid_Transfer_Rx_Rest constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;
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
		register_rest_route( 'boldgrid-backup/v1', '/generate-db-dump', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'generate_db_dump' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( 'boldgrid-backup/v1', '/generate-file-list', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'generate_file_list' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( 'boldgrid-backup/v1', '/get-wp-version', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_wp_version' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( 'boldgrid-backup/v1', '/retrieve-files', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'retrieve_files' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( 'boldgrid-backup/v1', '/retrieve-large-file-part', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'retrieve_large_file_part' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( 'boldgrid-backup/v1', '/delete-large-file-parts', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'delete_large_file_parts' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		// Register a rest route to retrieve a DB dump file, where the file name is an
		// argument in the rest route: /boldgrid-transfer/v1/get-db-dump/<file_name>
		register_rest_route( 'boldgrid-backup/v1', '/get-db-dump/(?P<file_name>.+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_db_dump' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );

		register_rest_route( 'boldgrid-backup/v1', '/split-large-files', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'split_large_files' ),
			'permission_callback' => array( $this, 'authenticate_request' ),
		) );
	}

	/**
	 * Delete large file parts
	 * 
	 * @param $request WP_REST_Request
	 * @since 0.0.3
	 */
	public function delete_large_file_parts( $request ) {
		$params = $request->get_params();

		$transfer_id = $params['transfer_id'];

		foreach( $params['file_parts'] as $file_part ) {
			if ( file_exists( $file_part ) ) {
				unlink( $file_part );
			}
		}

		return new WP_REST_Response( array(
			'success' => true,
		) );
	}

	/**
	 * Retrieve large file part
	 * 
	 * @param $request WP_REST_Request
	 * @since 0.0.1
	 */
	public function retrieve_large_file_part( $request ) {
		$params = $request->get_params();

		$transfer_id = $params['transfer_id'];
		$part_path   = $params['file_part'];

		if ( ! file_exists( $part_path ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'File part not found',
			) );
		}

		error_log( 'Part Path: ' . $part_path );
		error_log( 'File Size: ' . filesize( $part_path ) );

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( '1G' );

		$file_contents = base64_encode( file_get_contents( $part_path ) );

		return new WP_REST_Response( array(
			'success'   => true,
			'file_part' => $file_contents,
		) );
	}

	/**
	 * Split large files
	 * 
	 * @since 0.0.1
	 */
	public function split_large_files( $request ) {
		$params = $request->get_params();

		error_log( 'Params: ' . json_encode( $params ) );

		$files           = json_decode( $params['files'], true );
		$transfer_id     = $params['transfer_id'];
		$max_upload_size = $params['max_upload_size'];

		$split_files = array();

		foreach( $files as $file ) {
			$relative_path = $file['path'];
			$file_path     = ABSPATH . $relative_path;

			$file[ 'parts' ]  = $this->migrate_core->util->split_large_file( $transfer_id, $file_path, $relative_path, $max_upload_size );

			$file[ 'status' ] = 'ready-to-transfer';

			$split_files[] = $file;
		}

		return new WP_REST_Response( array(
			'success'      => true,
			'split_files'  => json_encode( $split_files ),
		) );
	}

	/**
	 * Retrieve files
	 * 
	 * @since 0.0.1
	 */
	public function retrieve_files( $request ) {
		$params = $request->get_params();

		$files    = json_decode( $params['files'], true );
		$batch_id = $params['batch_id'];

		$file_contents = array();

		$memory_requirement = 0;

		foreach ( $files as $file ) {
			$memory_requirement += filesize( ABSPATH . $file ) * 2;
		}

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $memory_requirement ) * 3 );

		foreach ( $files as $file ) {
			$file_path = ABSPATH . $file;
			if ( ! file_exists( $file_path ) ) {
				continue;
			}

			$file_contents[ $file ] = base64_encode( file_get_contents( $file_path ),
			);
		}

		return new WP_REST_Response( array(
			'success'  => true,
			'batch_id' => $batch_id,
			'files'    => json_encode( $file_contents ),
		) );
	}

	/**
	 * Get the db dump file
	 * 
	 * @since 0.0.1
	 */
	public function get_db_dump( $request ) {
		$request_params = $request->get_params();
		$file_name = $request_params['file_name'] . '.sql';
		$db_path = $this->migrate_core->util->get_transfer_dir() . '/' . $file_name;
		if ( ! file_exists( $db_path ) ) {
			update_option( 'boldgrid_transfer_last_error', 'File Path not found: ' . $db_path );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'File not found',
			) );
		}

		$file = file_get_contents( $db_path );

		return new WP_REST_Response( array(
			'success' => true,
			'db_dump' => base64_encode( $file ),
		) );
	}

	/**
	 * Get the wp version info
	 * 
	 * @since 0.0.1
	 */
	public function get_wp_version() {
		return new WP_REST_Response( array(
			'success'    => true,
			'wp_version' => get_bloginfo( 'version' ),
		) );
	}
	/**
	 * Generate a database dump
	 * 
	 * @since 0.0.1
	 */
	public function generate_db_dump( $request ) {
		$db_dump_file     = $this->migrate_core->util->generate_db_dump();
		$db_dump_md5_hash = md5_file( $db_dump_file );

		return $response = new WP_REST_Response( array(
			'success'          => true,
			'generate_db_dump' => array(
				'path' => str_replace( $this->migrate_core->util->get_transfer_dir() . '/', '', $db_dump_file ),
				'hash' => $db_dump_md5_hash,
				'size' => filesize( $db_dump_file ),
			)
		) );
	}

	/**
	 * Generate a file list
	 * 
	 * @since 0.0.1
	 */
	public function generate_file_list( $request ) {
		$file_list = $this->migrate_core->util->generate_file_list();

		// Sort filelist ascending by size
		usort( $file_list, function( $a, $b ) {
			return $a['size'] - $b['size'];
		} );

		$largest_file = $this->migrate_core->util->get_largest_file_size( $file_list, 0 );

		$memory_limit_adjustable = Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $largest_file ) * 2 );

		return $response = new WP_REST_Response( array(
			'success'   => $memory_limit_adjustable,
			'file_list' => json_encode( $file_list )
		) );
	}
}