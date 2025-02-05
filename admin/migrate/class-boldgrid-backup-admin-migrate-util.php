<?php
/**
 * File: class-boldgrid-backup-migrate-util.php
 * 
 * The main class for the BoldGrid Transfer utility functions.
 * 
 * @link https://www.boldgrid.com
 * @since 1.17.0
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Util
 * 
 * The main class for the BoldGrid Transfer utility functions.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate_Util {
	/**
	 * Boldgrid_Transfer Core
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 1.17.0
	 */
	public $migrate_core;

	/**
	 * Excluded Paths
	 *
	 * @var array
	 *
	 * @since 1.17.0
	 */
	public $excluded_paths = array(
		'.git',
		'node_modules',
		'boldgrid-backup'
	);

	/**
	 * Minimum TU Version
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $min_tu_version = '1.17.0';

	/**
	 * Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $transfers_option_name;

		/**
	 * File List Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $lists_option_name;

	/**
	 * Authenticated Sites Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $authd_sites_option_name;

	/**
	 * Cancelled Transfers Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $cancelled_transfers_option_name;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $core
	 * 
	 * @since 1.17.0
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;

		$this->transfers_option_name           = $this->migrate_core->configs['option_names']['transfers'];
		$this->lists_option_name               = $this->migrate_core->configs['option_names']['file_lists'];
		$this->authd_sites_option_name         = $this->migrate_core->configs['option_names']['authd_sites'];
		$this->heartbeat_option_name           = $this->migrate_core->configs['option_names']['heartbeat'];
		$this->cancelled_transfers_option_name = $this->migrate_core->configs['option_names']['cancelled_transfers'];
	}

	/**
	 * Get Transfer Dir
	 * 
	 * Get Backup Dir from boldgrid-backup settings
	 * in `boldgrid_backup_settings` option.
	 * 
	 * @since 1.17.0
	 * 
	 * @return string
	 */
	public function get_transfer_dir() {
		$settings = $this->get_option( 'boldgrid_backup_settings', array() );

		// TODO: Use the Boldgrid_Backup_Admin_Backup_Dir::guess_and_set() method once this is
		// 	 integrated into the Boldgrid Backup plugin.
		return isset( $settings['backup_directory'] ) ? $settings['backup_directory'] : '/var/www/boldgrid_backup';
	}

	/**
	 * URL to Safe Directory Name
	 * 
	 * Given a URL, this function will return a safe directory name
	 * by replacing dots with dashes.
	 * 
	 * @param string $url
	 * 
	 * @return string
	 */
	function url_to_safe_directory_name( $url ) {
		// Parse the URL to extract the host (domain)
		$parsed_url = wp_parse_url( $url );
		$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
	
		// Replace dots with dashes
		$safe_directory_name = str_replace('.', '-', $host);
	
		return $safe_directory_name;
	}

	/**
	 * Create Dirpath
	 * 
	 * Given a path to a file, this function will create the directory path
	 * if it does not exist.
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $path File path to create the directory for
	 * 
	 * @return bool True if the directory was created, false otherwise
	 */
	public function create_dirpath( $path ) {
		$dirpath = dirname( $path );

		if ( ! file_exists( $dirpath ) ) {
			try {
				wp_mkdir_p( $dirpath );
				return true;
			} catch ( Exception $e ) {
				error_log( 'Caught exception: ' . $e->getMessage() . "\n" );
				return false;
			}
		}

		error_log( 'dirpath: ' . $dirpath );

		return true;
	}

	/**
	 * Split Large File
	 * 
	 * Given a file path, this function will split the large file
	 * into max_upload_size / 10 sized chunks.
	 * They will be placed in the transfer_dir / transfer id
	 * directory.
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $transfer_id     The transfer id
	 * @param string $file_path       The path to the file to split
	 * @param string $relative_path   The path relative to the doc root
	 * @param int    $max_upload_size The max upload size
	 * 
	 * @return array of file paths to the chunks
	 */
	public function split_large_file( $transfer_id, $file_path, $relative_path, $max_upload_size ) {
		$transfer_dir = $this->get_transfer_dir();
		$chunk_size   = $max_upload_size / 10;

		$chunk_dir = $transfer_dir . '/temp-file-chunks/' . $transfer_id;
		$this->create_dirpath( $chunk_dir . '/' . $relative_path );

		$chunk_paths = array();

		/**
		 * Note: We are using direct PHP file operations here instead of WP_Filesystem
		 * because we need to be able to only read certain byte chunks of the file.
		 * Using WP_Filesystem would require reading the entire file into memory
		 * which could cause memory issues with large files.
		 */
		$handle       = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$chunk_number = 0;

		while ( ! feof( $handle ) ) {
			$chunk = fread( $handle, $chunk_size ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$chunk_file = $chunk_dir . '/' . $relative_path . '.part-' . $chunk_number;
			file_put_contents( $chunk_file, $chunk );
			$chunk_paths[] = $chunk_file;
			$chunk_number++;
		}

		return $chunk_paths;
	}

	/**
	 * Update Transfer Heartbeat
	 *
	 * @since 1.17.0
	 */
	public function update_transfer_heartbeat() {
		wp_cache_delete( $this->heartbeat_option_name, 'options' );
		update_option( $this->heartbeat_option_name, time(), false );
	}

	/**
	 * Get Transfer Heartbeat
	 *
	 * @since 1.17.0
	 * 
	 * @return int
	 */
	public function get_transfer_heartbeat() {
		wp_cache_delete( $this->heartbeat_option_name, 'options' );
		return $this->get_option( $this->heartbeat_option_name, 0 );
	}

	/**
	 * Generate DB Dump
	 * 
	 * Generate a database dump file.
	 * 
	 * @since 1.17.0
	 * 
	 * @return string The path to the database dump file
	 */
	public function generate_db_dump() {
		$backup_file = $this->get_transfer_dir() . '/db-' . DB_NAME . '-export-' . gmdate('Y-m-d-H-i-s') . '.sql';

		$db_dump = new Boldgrid_Backup_Admin_Db_Dump( $this->transfer_core->backup_core );

		$db_dump->dump( $backup_file );

		return $backup_file;
	}

	/**
	 * Get all files in a directory
	 * 
	 * Note: This file excludes anything in the
	 * self::excluded_paths property.
	 * 
	 * @param string $dir The directory to scan
	 * 
	 * @return array An array of files in the directory
	 * 
	 * @since 1.17.0
	 */
	public function get_files_in_dir( $dir ) {
		$wp_root = ABSPATH;
		$files   = array();

		// Recursive function to scan directories
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator( $dir ) );
		foreach ( $iterator as $fileInfo ) {
			if ( $fileInfo->isFile() ) {
				$relativePath = str_replace( $wp_root, '', $fileInfo->getPathname() );

				// Check if the relative path contains any of the exclusion strings
				$exclude = false;
				foreach ($this->excluded_paths as $exclusion) {
					if ( strpos( $relativePath, $exclusion ) !== false ) {
						$exclude = true;
						break;
					}
				}

				if ( ! $exclude ) {
					$files[] = [
						'path' => $relativePath,
						'size' => $fileInfo->getSize(),
					];
				}
			}
		}
	
		return $files;
	}

	/**
	 * Convert to Bytes
	 * 
	 * Convert a string representation of a file size
	 * to bytes.
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $size
	 * 
	 * @return int The size in bytes
	 */
	public function convert_to_bytes( $size ) {
		$val  = trim( $size );
		$last = strtolower( $val[ strlen($val) - 1 ] );
		$val  = (int) $val;

		switch( $last ) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/**
	 * Get Max Upload Size
	 * 
	 * Get the max upload size based on max sizes
	 * and memory limits.
	 * 
	 * @since 1.17.0
	 * 
	 * @return int The max upload size in bytes
	 */
	public function get_max_upload_size() {
		// Get values from PHP configuration
		$upload_max_filesize = $this->convert_to_bytes(ini_get('upload_max_filesize'));
		$post_max_size       = $this->convert_to_bytes(ini_get('post_max_size'));
		$memory_limit        = $this->convert_to_bytes(ini_get('memory_limit'));

		$this->migrate_core->log->add( json_encode( array(
			'upload_max_filesize' => $upload_max_filesize,
			'post_max_size'       => $post_max_size,
			'memory_limit'        => $memory_limit,
			'sapi_name'		      => php_sapi_name(),
		) ) );
	
		// Return the smallest value
		return min( $upload_max_filesize, $post_max_size, $memory_limit );
	}

	/**
	 * Get the largest file size from a list of files
	 * 
	 * @param array $files
	 * 
	 * @return int
	 * 
	 * @since 1.17.0
	 */
	public function get_largest_file_size( $files ) {
		$largest = 0;
		foreach ( $files as $file ) {
			if ( $file['size'] > $largest ) {
				$largest = $file['size'];
			}
		}

		return $largest;
	}

	/**
	 * Get Transfer From ID
	 * 
	 * Get the transfer data from the transfer id.
	 * 
	 * @param string $transfer_id
	 * 
	 * @return array|bool The transfer data or false if not found
	 */
	public function get_transfer_from_id( $transfer_id ) {
		$transfers = $this->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add( "Transfer $transfer_id not found" );
			return false;
		}

		return $transfers[ $transfer_id ];
	}

	/**
	 * Get Site Rest URL
	 * 
	 * Get the REST URL for a site using the link
	 * header provided by wordpress.
	 * 
	 * @param string $site_url
	 * 
	 * @return string The REST URL
	 */
	public function get_site_rest_url( $site_url ) {
		$response     = wp_remote_get( $site_url );
		$headers      = $response['headers']->getAll();
		$links        = explode( ',', $headers['link'] );
		$wp_json_link = array_filter( $links, function( $link ) {
			return false !== strpos( $link, 'rel="https://api.w.org/"' );
		} );

		preg_match('/<([^>]+)>/', $wp_json_link[0], $matches );

		return $matches[1];
	}

	/**
	 * Get Transfer Prop
	 * 
	 * Get a property from a transfer.
	 * 
	 * @param string $transfer_id
	 * @param string $property
	 * @param mixed  $fallback
	 * 
	 * @return mixed The property value or the fallback value
	 */
	public function get_transfer_prop( $transfer_id, $property, $fallback ) {
		wp_cache_delete( $this->transfers_option_name, 'options' );

		$transfer  = $this->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			return $fallback;
		}

		if ( ! isset( $transfer[ $property ] ) ) {
			return $fallback;
		}

		return $transfer[ $property ];
	}

	/**
	 * Update Transfer Prop
	 * 
	 * Update the property of a transfer
	 * and log the change if it is a status
	 * change.
	 * 
	 * @param string $transfer_id
	 * @param string $key
	 * @param mixed  $value
	 * 
	 * @return bool True if the update was successful, false otherwise
	 */
	public function update_transfer_prop( $transfer_id, $key, $value ) {
		wp_cache_delete( $this->transfers_option_name, 'options' );
		$transfers = $this->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return false;
		}

		if ( 'status' === $key && $transfers[ $transfer_id ][ $key ] !== $value ) {
			$this->migrate_core->log->add(
				"Transfer $transfer_id status updated from {$transfers[ $transfer_id ][ $key ]} to $value"
			);
		}

		$transfers[ $transfer_id ][ $key ] = $value;

		$this->update_transfer_heartbeat();

		wp_cache_delete( $this->transfers_option_name, 'options' );
		return update_option( $this->transfers_option_name, $transfers, false );
	}

	/**
	 * Cleanup Filelists
	 *
	 * @since 1.17.0
	 */
	public function cleanup_filelists() {
		$transfers    = $this->get_option( $this->transfers_option_name, array() );
		$transfer_ids = array_keys( $transfers );

		$file_lists = $this->get_option( $this->lists_option_name, array() );

		foreach( $file_lists as $transfer_id => $file_list ) {
			if ( ! in_array( $transfer_id, $transfer_ids ) ) {
				unset( $file_lists[ $transfer_id ] );
			}
		}

		update_option( $this->lists_option_name, $file_lists, false );
	}

	/**
	 * Generate File List
	 * 
	 * Generate a list of files in the WP_CONTENT_DIR
	 * 
	 * @since 1.17.0
	 * 
	 * @return array
	 */
	public function generate_file_list() {
		$file_list = $this->get_files_in_dir( WP_CONTENT_DIR );

		// Add a status => pending to each file in the list.
		foreach ( $file_list as $key => $file ) {
			$file_list[ $key ]['status'] = 'pending';
		}

		// Sort filelist ascending by size
		usort( $file_list, function( $a, $b ) {
			return $a['size'] - $b['size'];
		} );

		return $file_list;
	}

	/**
	 * Split file list
	 * 
	 * Splits the file list into small files,
	 * and large files. The small files are
	 * any files smaller than half of the
	 * max_upload_size
	 * 
	 * @param string $file_list       json encoded file list
	 * @param int    $max_upload_size max upload size in bytes
	 * 
	 * @return array
	 */
	public function split_file_list( $file_list, $max_upload_size ) {
		$files = json_decode( $file_list, true );

		$small_files = array();
		$large_files = array();

		foreach ( $files as $file ) {
			if ( $file['size'] < $max_upload_size / 2 ) {
				$small_files[] = $file;
			} else {
				$large_files[] = $file;
			}
		}

		return array( json_encode( $small_files ), json_encode( $large_files ),
		);
	}

	/**
	 * Get Rest
	 * 
	 * Get data from a REST API endpoint
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $site_url The site URL
	 * @param string $route    The route to the REST endpoint
	 * @param string $key      The key to get from the response
	 * 
	 * @return mixed The data from the REST endpoint
	 */
	public function rest_get( $transfer, $route, $key ) {
		$namespace   = $this->migrate_core->configs['rest_api_namespace'] . '/';
		$prefix      = $this->migrate_core->configs['rest_api_prefix'] . '/';
		$site_url    = $transfer['source_site_url'];
		$request_url = $rest_url . $namespace . $prefix . $route;

		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
		$response = wp_remote_get(
			$request_url . '?user=' . $user . '&pass=' . base64_encode( $pass ),
			array(
				'timeout' => 600,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error getting REST data: ' . $response->get_error_message() );
			return new WP_Error( 'rest_error', $response->get_error_message() );
		}

		$body    = wp_remote_retrieve_body( $response );
		$headers = wp_remote_retrieve_headers( $response );

		$data = json_decode( $body, true );

		if ( isset ( $data[ $key ] ) ) {
			return $data[ $key ];
		} else {
			$this->migrate_core->log->add( 'Get Rest Error: ' . $request_url );
			$this->migrate_core->log->add( 'Get Rest Error Headers: ' . json_encode( $body, JSON_PRETTY_PRINT ) );
			$this->migrate_core->log->add( 'Get Rest Error Body: ' . $body );
			
			return new WP_Error( 'rest_error', 'Requested Key: ' . $key . ' not found in response' );
		}
	}

	/**
	 * Generate a random backup id.
	 * 
	 * @since 1.17.0
	 *
	 * @return string
	 */
	public function gen_transfer_id() {
		$transfers = $this->get_option( $this->transfers_option_name, array() );

		$transfer_id = wp_generate_password( 8, false );

		while ( isset( $transfers[ $transfer_id ] ) ) {
			$transfer_id = wp_generate_password( 8, false );
		}

		return $transfer_id;
	}

	/**
	 * Get Option
	 * 
	 * Get an option from the php table using
	 * wpdb to avoid caching issues.
	 * 
	 * NOTE: Plugin checks don't like that we're using
	 * this method to get options, rather than using get_option
	 * along with cache clearing, however these options are updated
	 * asynchronously and cache clearing wasn't working efficiently
	 * enough for our needs.
	 * 
	 * @param string $option_name The name of the option
	 * @param mixed  $fallback    The fallback value if the option is not found
	 * 
	 * @return mixed The option value or the fallback value
	 */
	public function get_option( $option_name, $fallback = null ) {
		$wpdb   = $GLOBALS['wpdb'];
		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->options} WHERE option_name = %s", $option_name )
		);

		// If the option is not found, return the fallback value
		if ( ! $result ) {
			return $fallback;
		}

		// Unserialize the option value and return it
		return maybe_unserialize( $result->option_value );
	}

	/**
	 * Cancel Transfer
	 *
	 * @param string $transfer_id The transfer id
	 */
	public function cancel_transfer( $transfer_id ) {
		$cancelled_transfers = $this->get_option( $this->cancelled_transfers_option_name, array() );

		if ( ! in_array( $transfer_id, $cancelled_transfers ) ) {
			$cancelled_transfers[] = $transfer_id;
		}

		wp_cache_delete( $this->cancelled_transfers_option_name, 'options' );
		update_option( $this->cancelled_transfers_option_name, $cancelled_transfers, false );

		wp_cache_delete( $this->transfers_option_name, 'options' );
		$transfer = $this->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			return;
		}

		$status_updated = $this->update_transfer_prop( $transfer_id, 'status', 'canceled' );
	
		return $status_updated;
	}

	/**
	 * Update Bulk File Status
	 * 
	 * @since 1.17.0
	 *
	 * @param string $transfer_id The transfer id
	 * @param array  $batch       The batch of files to update
	 * @param string $status      The status to update the files to
	 */
	public function update_bulk_file_status( $transfer_id, $batch, $status ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->transfers_option_name, 'options' );
		wp_cache_delete( 'boldgrid_transfer_cancelled_transfers', 'options' );
		$file_lists = $this->get_option( $this->lists_option_name, array() );
		$transfers  = $this->get_option( $this->transfers_option_name, array () );

		set_time_limit( ini_get( 'max_execution_time' ) );

		if ( 'canceled' === $transfers[ $transfer_id ]['status'] ) {
			wp_die();
		}

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			wp_die();
			return;
		}

		if ( ! isset( $file_lists[ $transfer_id ] ) ) {
			wp_die();
			return;
		}

		$file_list = json_decode( $file_lists[ $transfer_id ]['small'], true );

		foreach ( $batch as $file_path ) {
			foreach ( $file_list as $key => $file_item ) {
				if ( $file_item['path'] === $file_path ) {
					$file_list[ $key ]['status'] = $status;
				}
			}
		}

		// Sort filelist ascending by size
		usort( $file_list, function( $a, $b ) {
			return $a['size'] - $b['size'];
		} );

		$file_lists[ $transfer_id ]['small'] = json_encode( $file_list );

		$this->update_transfer_heartbeat();
		update_option( $this->lists_option_name, $file_lists, false );
	}

	/**
	 * Update File MD5
	 * 
	 * @since 1.17.0
	 *
	 * @param string $transfer_id   The transfer id
	 * @param string $small_or_large The file size
	 * @param string $file_path      The file path
	 * @param string $new_md5_hash   The new md5 hash
	 */
	public function update_file_md5( $transfer_id, $small_or_large, $file_path, $new_md5_hash ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->transfers_option_name, 'options' );
		wp_cache_delete( 'boldgrid_transfer_cancelled_transfers', 'options' );
		$file_lists = $this->get_option( $this->lists_option_name, array() );
		$transfer = $this->get_transfer_from_id( $transfer_id );

		if ( 'canceled' === $transfer['status'] ) {
			wp_die();
		}

		if ( ! isset( $transfer ) ) {
			wp_die();
			return;
		}

		if ( ! isset( $file_lists[ $transfer_id ] ) ) {
			wp_die();
			return;
		}

		$file_list = json_decode( $file_lists[ $transfer_id ][ $small_or_large ], true );

		foreach ( $file_list as $key => $file_item ) {
			if ( $file_item['path'] === $file_path ) {
				$file_list[ $key ]['md5'] = $new_md5_hash;
			}
		}

		// Sort filelist ascending by size
		usort( $file_list, function( $a, $b ) {
			return $a['size'] - $b['size'];
		} );

		$file_lists[ $transfer_id ][ $small_or_large ] = json_encode( $file_list );

		update_option( $this->lists_option_name, $file_lists, false );
		$this->update_transfer_heartbeat();
	}

	/**
	 * Update File Status
	 * 
	 * @since 1.17.0
	 *
	 * @param string $transfer_id   The transfer id
	 * @param string $small_or_large The file size
	 * @param string $file_path      The file path
	 * @param string $status         The new status
	 */
	public function update_file_status( $transfer_id, $small_or_large, $file_path, $status ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->transfers_option_name, 'options' );
		wp_cache_delete( $this->cancelled_transfers_option_name, 'options' );
		$file_lists          = $this->get_option( $this->lists_option_name, array() );
		$transfer            = $this->get_transfer_from_id( $transfer_id );
		$cancelled_transfers = $this->get_option( $this->cancelled_transfers_option_name, array() );

		if ( 'canceled' === $transfer['status'] ) {
			wp_die();
		}

		if ( in_array( $transfer_id, $cancelled_transfers ) ) {
			$this->cancel_transfer( $this->transfers_option_name, $transfer_id );
			wp_die();
		}

		if ( ! isset( $transfer ) ) {
			wp_die();
			return;
		}

		if ( ! isset( $file_lists[ $transfer_id ] ) ) {
			wp_die();
			return;
		}

		$file_list = json_decode( $file_lists[ $transfer_id ][ $small_or_large ], true );

		foreach ( $file_list as $key => $file_item ) {
			if ( $file_item['path'] === $file_path ) {
				$file_list[ $key ]['status'] = $status;
			}
		}

		// Sort filelist ascending by size
		usort( $file_list, function( $a, $b ) {
			return $a['size'] - $b['size'];
		} );

		$file_lists[ $transfer_id ][ $small_or_large ] = json_encode( $file_list );

		update_option( $this->lists_option_name, $file_lists, false );
		$this->update_transfer_heartbeat();
	}

	/**
	 * Rest Post
	 * 
	 * POST data to a REST API endpoint
	 * 
	 * @since 1.17.0
	 * 
	 * @param array  $transfer Transfer Data
	 * @param string $route    The route to the REST endpoint
	 * @param array  $data     The data to post
	 * @param bool   $return   Whether to expect a return value
	 * 
	 * @return mixed The response data from the REST endpoint
	 */
	public function rest_post( $transfer, $route, $data, $return = false ) {
		$namespace = $this->migrate_core->configs['rest_api_namespace'] . '/';
		$prefix    = $this->migrate_core->configs['rest_api_prefix'] . '/';
		$site_url  = $transfer['source_site_url'];
		$rest_url  = $transfer['source_rest_url'];
		$request_url = $rest_url . $namespace . $prefix . $route;

		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$data['user'] = $auth['user'];
		$data['pass'] = base64_encode( Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' ) );
		
		$response = wp_remote_post(
			$request_url,
			array(
				'body' => $data,
				'timeout' => $this->migrate_core->configs['conn_timeout'],
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error posting REST data: ' . $response->get_error_message() );
			return new WP_Error( 'rest_error', $response->get_error_message() );
		}

		$body      = wp_remote_retrieve_body( $response );
		$body_data = json_decode( $body, true );

		if ( isset ( $body_data[ 'success' ] ) && ! $return ) {
			return $body_data[ 'success' ];
		} else if ( isset( $body_data[ 'success' ] ) && $return ) {
			return $body_data;
		} else {
			$this->migrate_core->log->add( 'Post Rest Error: ' . $request_url );
			$this->migrate_core->log->add( 'Post Rest Error: ' . $body );
			return new WP_Error( 'rest_error', 'No Seccess Response' );
		}
	}

	public function install_total_upkeep( $url ) {
		$site_url    = $url;
		$rest_url    = $this->get_site_rest_url( $site_url );
		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$request_url = $rest_url . 'wp/v2/plugins';

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
		$response = wp_remote_post(
			$request_url,
			array(
				'body'    => array(
					'slug'   => 'boldgrid-backup',
					'status' => 'active',
				),
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $pass ),
				),
				'timeout' => 600,
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 201 !== $response_code ) {
			return new WP_Error( 'install_error', 'Error installing Total Upkeep' );
		}

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'install_error', $response->get_error_message() );
		}

		return true;
	}

	public function validate_total_upkeep_status( $site_rest_url, $site_url ) {
		$total_upkeep_status = $this->get_total_upkeep_status(
			array(
				'source_rest_url' => $site_rest_url,
				'source_site_url' => $site_url,
			)
		);

		error_log( 'total_upkeep_status: ' . json_encode( $total_upkeep_status ) );

		if ( is_wp_error( $total_upkeep_status ) ) {
			$this->migrate_core->log->add( 'Error getting Total Upkeep Status: ' . $total_upkeep_status->get_error_message() );
			wp_send_json_error( array( 'success' => false, 'error' => 'Error getting Total Upkeep Status' ) );
		}

		if ( empty( $total_upkeep_status ) ) {
			$this->migrate_core->log->add( 'Total Upkeep is not installed on the source site.' );
			wp_send_json_error(
				array(
					'success' => false,
					'error'   => sprintf(
						'<p class="notice notice-error">%1$s %2$s %3$s</p>
						<button class="install-total-upkeep button-primary" data-url="%4$s">%5$s</button>',
						'Total Upkeep is not installed on the source site.',
						'Total Upkeep must be installed on the source site in order to transfer the site.',
						'You may click the button below to install Total Upkeep on the source site.',
						esc_url( $site_url ),
						'Install Total Upkeep'
						)
					)
				);
		}

		$version_compare = version_compare(
			$total_upkeep_status['version'],
			$this->min_tu_version,
			'ge'
		);

		if ( ! $version_compare ) {
			wp_send_json_error(
				array(
					'success' => false,
					'error'   => sprintf(
						'<p class="notice notice-error">%1$s %2$s %3$s</p>
						<button class="update-total-upkeep button-primary" data-url="%4$s">%5$s</button>',
						'Total Upkeep Version is not compatible with this site.',
						'Total Upkeep must be version ' . $this->min_tu_version . ' or higher in order to transfer the site.',
						'You may click the button below to update Total Upkeep to othe newest version on the source site.',
						esc_url( $site_url ),
						'Update Total Upkeep'
						)
					)
				);
		}

		if ( 'active' !== $total_upkeep_status['active'] ) {
			wp_send_json_error(
				array(
					'success' => false,
					'error'   => sprintf(
						'<p class="notice notice-error">%1$s %2$s %3$s</p>
						<button class="activate-total-upkeep button-primary" data-url="%4$s">%5$s</button>',
						'Total Upkeep is installed but is not active on the source site.',
						'Total Upkeep must be active on the source site in order to transfer the site.',
						'You may click the button below to activate Total Upkeep on the source site.',
						esc_url( $site_url ),
						'Activate Total Upkeep'
						)
					)
				);
		}

		return true;
	}

	/**
	 * Edit Total Upkeep Status
	 * 
	 * @param string $site_url The site URL
	 */
	public function edit_total_upkeep_status( $site_url, $status ) {
		$rest_url    = $this->get_site_rest_url( $site_url );
		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$request_url = $rest_url . 'wp/v2/plugins/boldgrid-backup/boldgrid-backup';

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
		$response = wp_remote_post(
			$rest_url . 'wp/v2/plugins/boldgrid-backup/boldgrid-backup',
			array(
				'body' => array(
					'context' => 'edit',
					'status'  => $status,
					'plugin'  => 'boldgrid-backup/boldgrid-backup',
				),
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $pass ),
				),
				'timeout' => 600,
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		error_log( 'edit_response: ' . json_encode( $response_code ) );

		if ( 200 !== $response_code ) {
			return new WP_Error( 'install_error', 'Error installing Total Upkeep' );
		}

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'install_error', $response->get_error_message() );
		}

		return true;
	}

	/**
	 * Update the Total Upkeep Plugin
	 * 
	 * @param string $site_url The site URL
	 */
	public function update_total_upkeep( $site_url ) {
		$rest_url    = $this->get_site_rest_url( $site_url );
		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$request_url = $rest_url . 'wp/v2/plugins/boldgrid-backup/boldgrid-backup';

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
		$this->edit_total_upkeep_status( $site_url, 'inactive' );
		$response = wp_remote_get(
			$request_url,
			array(
				'method' => 'DELETE',
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $pass ),
				),
				'timeout' => 600,
			)
		); 

		return $this->install_total_upkeep( $site_url );
	}

	public function get_total_upkeep_status( $transfer ) {
		$site_url    = $transfer['source_site_url'];
		$rest_url    = $transfer['source_rest_url'];
		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$request_url = $rest_url . 'wp/v2/plugins';

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
		$response = wp_remote_get(
			$request_url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $pass ),
				),
				'timeout' => 600,
			)
		);

		$body    = wp_remote_retrieve_body( $response );
		$plugins = json_decode( $body, true );
		$total_upkeep_data = array();

		foreach( $plugins as $plugin ) {
			if ( 'Total Upkeep' === $plugin['name'] ) {
				$total_upkeep_data = array(
					'version' => $plugin['version'],
					'active'  => $plugin['status'],
				);
				break;
			}
		}

		return $total_upkeep_data;
	}

	/**
	 * Convert to MM:SS
	 * 
	 * @param int $seconds
	 * 
	 * @return string
	 * 
	 * @since 1.17.0
	 */
	public function convert_to_mmss( $seconds ) {
		if ( $seconds >= 3600 ) { // Check if time is 1 hour or more
			return date_i18n( 'H:i:s', mktime( 0, 0, $seconds ) );
		}
		return date_i18n( 'i:s', mktime( 0, 0, $seconds ) );
	}
}