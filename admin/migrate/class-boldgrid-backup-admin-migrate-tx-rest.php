<?php
/**
 * File: class-boldgrid-backup-admin-migrate-tx-rest.php
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
 * Class: Boldgrid_Backup_Admin_Migrate_Tx_Rest
 * 
 * The transmitting rest api class for the Transfer process.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate_Tx_Rest {
	/**
	 * Boldgrid_Transfer Core
	 *
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 1.17.0
	 */
	public $migrate_core;

	/**
	 * Transfers Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $transfers_option_name;

	/**
	 * DB Dump Status Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $db_dump_status_option_name;

	/**
	 * Rest API Namespace
	 * 
	 * @var string
	 * 
	 * @since 1.17.00
	 */
	public $namespace;

	/**
	 * Rest API Prefix
	 * 
	 * @var string
	 * 
	 * @since 1.17.00
	 */
	public $prefix;

	/**
	 * Boldgrid_Transfer_Rx_Rest constructor.
	 * 
	 * @param Boldgrid_Transfer $migrate_core
	 * 
	 * @since 1.17.0
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;

		$this->transfers_option_name      = $this->migrate_core->configs['option_names']['transfers'];
		$this->db_dump_status_option_name = $this->migrate_core->configs['option_names']['db_dump_status'];

		$this->namespace = $this->migrate_core->configs['rest_api_namespace'];
		$this->prefix    = $this->migrate_core->configs['rest_api_prefix'];
	}

	/**
	 * Authenticate the request
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return bool True if the request is authenticated, false otherwise
	 * 
	 * @since 1.17.0
	 */
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

		wp_set_current_user( $user->ID );

		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the rest api routes
	 * 
	 * @since 1.17.00
	 */
	public function register_routes() {
		$rest_routes = array(
			'start-db-dump' => array(
				'methods'             => 'POST',
				'endpoint'            => 'start-db-dump',
			),
			'check-dump-status' => array(
				'methods'             => 'POST',
				'endpoint'            => 'check-dump-status',
			),
			'split-db-file' => array(
				'methods'             => 'POST',
				'endpoint'            => 'split-db-file',
			),
			'generate-file-list' => array(
				'methods'             => 'GET',
				'endpoint'            => 'generate-file-list',
			),
			'get-wp-version' => array(
				'methods'             => 'GET',
				'endpoint'            => 'get-wp-version',
			),
			'get-db-prefix' => array(
				'methods'             => 'GET',
				'endpoint'            => 'get-db-prefix',
			),
			'retrieve-files' => array(
				'methods'             => 'POST',
				'endpoint'            => 'retrieve-files',
			),
			'retrieve-large-file-part' => array(
				'methods'             => 'POST',
				'endpoint'            => 'retrieve-large-file-part',
			),
			'delete-large-file-parts' => array(
				'methods'             => 'POST',
				'endpoint'            => 'delete-large-file-parts',
			),
			'get-db-dump' => array(
				'methods'             => 'POST',
				'endpoint'            => 'get-db-dump',
			),
			'split-large-files' => array(
				'methods'             => 'POST',
				'endpoint'            => 'split-large-files',
			),
		);

		foreach( $rest_routes as $route => $args ) {
			$req_method  = $args['methods'];
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
	 * Start db dump
	 *  
	 * @param $request WP_REST_Request
	 *
	 * @since 1.17.0
	 * 
	 * @return WP_Rest_Response
	 */
	public function start_db_dump( $request ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
		$request_params = $request->get_params();
		$transfer_id    = $request_params['transfer_id'];
		$dest_url       = $request_params['dest_url'];
		$dest_dir       = $this->migrate_core->util->url_to_safe_directory_name( $dest_url );
		$dump_dir       = $this->migrate_core->util->get_transfer_dir() . '/' . $dest_dir . '/' . $transfer_id;
		$db_size        = WP_Debug_Data::get_database_size();
		$db_dump_file   = $dump_dir . '/db-' . DB_NAME . '-export-' . gmdate('Y-m-d-H-i-s');
		update_option( $this->db_dump_status_option_name, $db_dump_file );
		$response       = json_encode( array(
			'status'  => 'pending',
			'file'    => $db_dump_file,
			'db_size' => $db_size,
		) );
		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		$this->migrate_core->util->create_dirpath( $dump_dir . '/db-dump-status.json' );
		file_put_contents( $dump_dir . '/db-dump-status.json', $response );

        $cron_disabled = defined( 'DISABLE_WP_CRON' ) ? DISABLE_WP_CRON : false;
        $this->migrate_core->log->add( 'DISABLE_WP_CRON: ' . json_encode( $cron_disabled ) );
		$this->migrate_core->log->add( 'Scheduling db dump cron ' );
		$scheduled = wp_schedule_single_event( time() + 10, 'boldgrid_transfer_db_dump_cron' );
		$this->migrate_core->log->add( 'Scheduled: ' . json_encode( $scheduled, JSON_PRETTY_PRINT ) );

		return new WP_REST_Response( array(
			'success' => true,
			'db_dump_info' => $response,
		) );
	}

	/**
	 * Generate a database dump
	 * 
	 * @since 1.17.0
	 */
	public function generate_db_dump() {
		$db_dump_file = $this->migrate_core->util->get_option( $this->db_dump_status_option_name, '' );

		$this->migrate_core->log->add( 'Generating DB Dump: ' . $db_dump_file );

		$dump_dir = dirname( $db_dump_file );
		$progress = json_decode( file_get_contents( $dump_dir . '/db-dump-status.json' ), true );

		$progress['status'] = 'dumping';

		file_put_contents( $dump_dir . '/db-dump-status.json', json_encode( $progress ) );

		$db_dump = new Boldgrid_Backup_Admin_Db_Dump( $this->migrate_core->backup_core );

		$db_dump->dump( $db_dump_file );

		$progress['status'] = 'complete';

		file_put_contents( $dump_dir . '/db-dump-status.json', json_encode( $progress ) );
	}

	/** 
	 * Check Status of DB Dump
	 * 
	 * @since 1.17.00
	 * 
	 * @param $request WP_REST_Request
	 *
	 * @return WP_Rest_Response
	 */
	public function check_dump_status( $request ) {
		$params      = $request->get_params();
		$transfer_id = $params['transfer_id'];
		$dest_url    = $params['dest_url'];
		$dest_dir	 = $this->migrate_core->util->url_to_safe_directory_name( $dest_url );
		$dump_dir    = $this->migrate_core->util->get_transfer_dir() . '/' . $dest_dir . '/' . $transfer_id;
		$status_file = $dump_dir . '/db-dump-status.json';

		if ( ! file_exists( $status_file ) ) {
			$this->migrate_core->log->add( 'Status file not found: ' . $status_file );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Status file not found',
			) );
		}

		$response  = json_decode( file_get_contents( $status_file ), true );
		$restarted = false;

		if ( file_exists( $response['file'] ) ) {
			clearstatcache();
			$file_size = filesize( $response['file'] );
			/*
			 * If the file is not complete and it has been more
			 * than 15 seconds since the last modification, then
			 * set the status to pending and reschedule the cron
			 * to start the dump again.
			 */
			$restarted = $this->maybe_restart_dump( $response, $status_file );
		} else {
			$file_size = 0;
		}

		if ( $restarted ) {
			$response['restarted'] = true;
		}

		$response['file_size'] = $file_size;

		if ( 'complete' === $response['status'] ) {
			$response['db_size'] = $file_size;
			$response['db_hash'] = md5_file( $response['file'] );
		}

		$this->migrate_core->log->add( 'DB Dump Status: ' . json_encode( $response, JSON_PRETTY_PRINT ) );

		return new WP_REST_Response( array(
			'success'      => true,
			'db_dump_info' => $response,
		) );
	}

	public function maybe_restart_dump( $status, $status_file ) {
		if ( 'complete' === $status['status'] || 'pending' === $status['status'] ) {
			return false;
		}

		$time_since_modified = time() - filemtime( $status['file'] );

		if ( 15 > $time_since_modified ) {
			return false;
		}

		$this->migrate_core->log->add( 'Restarting DB Dump. Time since modified: ' . $time_since_modified );

		// Update Status File
		file_put_contents( $status_file, json_encode( array(
			'status'  => 'pending',
			'file'    => $status['file'],
			'db_size' => $status['db_size'],
		) ) );
	
		// Delete the failed file if it exists
		if ( file_exists( $status['file'] ) ) {
			wp_delete_file( $status['file'] );
		}
		
		// Reschedule the cron
		if ( ! wp_next_scheduled( 'boldgrid_transfer_db_dump_cron' ) ) {
			wp_schedule_single_event( time() + 10, 'boldgrid_transfer_db_dump_cron' );
		}

		return true;
	}

	/**
	 * Delete large file parts
	 * 
	 * @param $request WP_REST_Request
	 * @since 1.17.00
	 * 
	 * @return WP_Rest_Response
	 */
	public function delete_large_file_parts( $request ) {
		$params = $request->get_params();

		$transfer_id = $params['transfer_id'];

		foreach( $params['file_parts'] as $file_part ) {
			if ( file_exists( $file_part ) ) {
				wp_delete_file( $file_part );
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
	 * @since 1.17.00
	 * 
	 * @return WP_Rest_Response
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

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( '1G' );

		$file_contents = base64_encode( file_get_contents( $part_path ) );

		return new WP_REST_Response( array(
			'success'   => true,
			'file_part' => $file_contents,
		) );
	}

	/**
	 * Split db file
	 * 
	 * Rest API request to split the database dump file into smaller parts
	 * The request should contain the following parameters:
	 * - transfer_id: The transfer id
	 * - db_file: The path to the database dump file
	 * - max_upload_size: The maximum upload size for the parts
	 * 
	 * Compare the remote max_upload_size with the local max_upload_size
	 * Use the smaller of the two values to split the file.
	 * 
	 * @param $request WP_REST_Request
	 * 
	 * @since 1.17.00
	 * 
	 * @return WP_Rest_Response
	 */
	public function split_db_file( $request ) {
		$params = $request->get_params();

		$db_file         = $params['db_file'];
		$transfer_id     = $params['transfer_id'];
		$max_upload_size = $params['max_upload_size'];
		$memory_limit    = $this->migrate_core->util->convert_to_bytes( ini_get('memory_limit') );
		
		$max_upload_size = min( $max_upload_size, $memory_limit );

		// extract the file name, from the absolute path in $db_file
		$relative_path = basename( $db_file );

		$split_files = $this->migrate_core->util->split_large_file( $transfer_id, $db_file, $relative_path, $max_upload_size * 2 );

		return new WP_REST_Response( array(
			'success'     => true,
			'split_files' => json_encode( $split_files ),
		) );
	}

	/**
	 * Split large files
	 * 
	 * @since 1.17.0
	 * 
	 * @param $request WP_REST_Request
	 * 
	 * @return WP_Rest_Response
	 */
	public function split_large_files( $request ) {
		$params = $request->get_params();

		$files           = json_decode( $params['files'], true );
		$transfer_id     = $params['transfer_id'];
		$max_upload_size = $params['max_upload_size'];

		$split_files = array();

		foreach( $files as $file ) {
			$relative_path = $file['path'];
			$file_path     = ABSPATH . $relative_path;

			$file['md5'] = md5_file( $file_path );

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
	 * @since 1.17.0
	 * 
	 * @param $request WP_REST_Request
	 * 
	 * @return WP_Rest_Response
	 */
	public function retrieve_files( $request ) {
		$params = $request->get_params();

		$files    = json_decode( $params['files'], true );
		$batch_id = $params['batch_id'];

		$files_data = array();

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
			$files_data[$file] = array(
				'md5'  => md5_file( $file_path ),
				'file' => base64_encode( file_get_contents( $file_path ) ),
			);
		}

		return new WP_REST_Response( array(
			'success'  => true,
			'batch_id' => $batch_id,
			'files'    => json_encode( $files_data ),
		) );
	}

	/**
	 * Get the db dump file
	 * 
	 * @since 1.17.0
	 * 
	 * @param $request WP_REST_Request
	 * 
	 * @return WP_Rest_Response
	 */
	public function get_db_dump( $request ) {
		$request_params = $request->get_params();
		$file_name      = $request_params['file_path'];
		$db_path        = urldecode( $file_name );
		if ( ! file_exists( $db_path ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'File not found',
			) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'file'    => file_get_contents( $db_path ),
			'file_hash' => md5_file( $db_path ),
		) );
	}

	/**
	 * Get the wp version info
	 * 
	 * @since 1.17.0
	 * 
	 * @return WP_Rest_Response
	 */
	public function get_wp_version() {
		return new WP_REST_Response( array(
			'success'    => true,
			'wp_version' => get_bloginfo( 'version' ),
		) );
	}

	/**
	 * Get the wp version info
	 * 
	 * @since 1.17.0
	 * 
	 * @return WP_Rest_Response
	 */
	public function get_db_prefix() {
		global $wpdb;
		return new WP_REST_Response( array(
			'success'   => true,
			'db_prefix' => $wpdb->get_blog_prefix(),
		) );
	}

	/**
	 * Generate a file list
	 * 
	 * @since 1.17.0
	 * 
	 * @param $request WP_REST_Request
	 * 
	 * @return WP_Rest_Response
	 */
	public function generate_file_list( $request ) {
		$file_list = $this->migrate_core->util->generate_file_list();

		// Sort filelist ascending by size
		usort( $file_list, function( $a, $b ) {
			return $a['size'] - $b['size'];
		} );

		$largest_file = $this->migrate_core->util->get_largest_file_size( $file_list, 0 );

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $largest_file ) * 2 );

		return $response = new WP_REST_Response( array(
			'success'   => true,
			'file_list' => json_encode( $file_list )
		) );
	}
}