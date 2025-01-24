<?php
/**
 * File: class-boldgrid-backup-migrate-util.php
 * 
 * The main class for the BoldGrid Transfer utility functions.
 * 
 * @link https://www.boldgrid.com
 * @since 0.0.1
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Util
 * 
 * The main class for the BoldGrid Transfer utility functions.
 *
 * @since 0.0.1
 */
class Boldgrid_Backup_Admin_Migrate_Util {
	/**
	 * Boldgrid_Transfer Core
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 0.0.1
	 */
	public $migrate_core;

	/**
	 * Excluded Paths
	 */
	public $excluded_paths = array(
		'.git',
		'node_modules',
		'boldgrid-transfer'
	);

	/**
	 * Option Name
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
	 * Authenticated Sites Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $authd_sites_option_name;

	/**
	 * Cancelled Transfers Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $cancelled_transfers_option_name;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $core
	 * 
	 * @since 0.0.1
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
	 * Get Backup Dir from boldgrid-backup settings
	 * in `boldgrid_backup_settings` option.
	 */
	public function get_transfer_dir() {
		$settings = $this->get_option( 'boldgrid_backup_settings', array() );

		// TODO: Use the Boldgrid_Backup_Admin_Backup_Dir::guess_and_set() method once this is
		// 	 integrated into the Boldgrid Backup plugin.
		return isset( $settings['backup_directory'] ) ? $settings['backup_directory'] : '/var/www/boldgrid_backup';
	}

	function url_to_safe_directory_name( $url ) {
		// Parse the URL to extract the host (domain)
		$parsed_url = parse_url( $url );
		$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
	
		// Replace dots with dashes
		$safe_directory_name = str_replace('.', '-', $host);
	
		return $safe_directory_name;
	}

	public function create_dirpath( $path ) {
		$dirpath = dirname( $path );

		if ( ! file_exists( $dirpath ) ) {
			try {
				wp_mkdir_p( $dirpath );
				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

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
	 * @param $transfer_id
	 * @param $file_path
	 * 
	 * @return array of file paths to the chunks
	 */
	public function split_large_file( $transfer_id, $file_path, $relative_path, $max_upload_size ) {
		$transfer_dir = $this->get_transfer_dir();
		$chunk_size   = $max_upload_size / 10;

		$chunk_dir = $transfer_dir . '/temp-file-chunks/' . $transfer_id;
		$this->create_dirpath( $chunk_dir . '/' . $relative_path );

		$chunk_paths = array();

		$handle       = fopen( $file_path, 'rb' );
		$chunk_number = 0;

		while ( ! feof( $handle ) ) {
			$chunk = fread( $handle, $chunk_size );
			$chunk_file = $chunk_dir . '/' . $relative_path . '.part-' . $chunk_number;
			file_put_contents( $chunk_file, $chunk );
			$chunk_paths[] = $chunk_file;
			$chunk_number++;
		}

		return $chunk_paths;
	}

	public function update_transfer_heartbeat() {
		wp_cache_delete( $this->heartbeat_option_name, 'options' );
		update_option( $this->heartbeat_option_name, time(), false );
	}

	public function get_transfer_heartbeat() {
		wp_cache_delete( $this->heartbeat_option_name, 'options' );
		return $this->get_option( $this->heartbeat_option_name, 0 );
	}

	public function generate_db_dump() {
		$backup_file = $this->get_transfer_dir() . '/db-' . DB_NAME . '-export-' . date('Y-m-d-H-i-s') . '.sql';

		$db_dump = new Boldgrid_Backup_Admin_Db_Dump( $this->transfer_core->backup_core );

		$db_dump->dump( $backup_file );

		return $backup_file;
	}

	/**
	 * Get all files in a directory
	 * 
	 * @param string $dir
	 * 
	 * @return array
	 * 
	 * @since 0.0.1
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
	 * @param string $size
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
	 * @return int
	 * 
	 * @since 0.0.1
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
	 * @since 0.0.1
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
	 * Get Estimated Batch Size
	 * 
	 * Determine what the size in bytes will be of
	 * the average batch of files based on the 
	 * configs['batch_chunks'] and 
	 * configs['chunk_size'] configs,
	 * as well as the largest file size and the total transfer
	 * size.
	 * 
	 * @param array $file_list list of files
	 * 
	 * @return int
	 * 
	 * @since 0.0.3
	 */
	public function get_estimated_batch_size( $file_list ) {
		$transfer_size = array_sum( array_column( $file_list, 'size' ) );

		$average_file_size = $transfer_size / count( $file_list );

		$average_batch_size = $average_file_size * $this->migrate_core->configs['batch_chunks'];

		return $average_batch_size;
	}

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

	public function rest_get( $site_url, $route, $key ) {
		$namespace = $this->migrate_core->configs['REST']['namespace'];
		$request_url = $site_url . '/wp-json/' . $namespace . $route;

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
			return new WP_Error( 'rest_error', 'Requested Key: ' . $key . ' not found in response' );
		}
	}

	/**
	 * Generate a random backup id.
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
	 * @param string $option_name The name of the option
	 * @param mixed  $fallback    The fallback value if the option is not found
	 * 
	 * @return mixed
	 */
	public function get_option( $option_name, $fallback = null ) {
		$wpdb   = $GLOBALS['wpdb'];
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->options} WHERE option_name = %s", $option_name );
		$result = $wpdb->get_row( $query );

		// If the option is not found, return the fallback value
		if ( ! $result ) {
			return $fallback;
		}

		// Unserialize the option value and return it
		return maybe_unserialize( $result->option_value );
	}

	public function cancel_transfer( $transfer_id ) {
		$cancelled_transfers = $this->get_option( $this->cancelled_transfers_option_name, array() );

		if ( ! in_array( $transfer_id, $cancelled_transfers ) ) {
			$cancelled_transfers[] = $transfer_id;
		}

		wp_cache_delete( $this->cancelled_transfers_option_name, 'options' );
		update_option( $this->cancelled_transfers_option_name, $cancelled_transfers, false );

		wp_cache_delete( $this->transfers_option_name, 'options' );
		$transfers = $this->get_option( $this->transfers_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return;
		}

		$transfers[ $transfer_id ]['status'] = 'canceled';
		$status_updated = update_option( $this->transfers_option_name, $transfers );
		wp_cache_delete( $this->transfers_option_name, 'options' );
	
		return $status_updated;
	}

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

	public function update_file_md5( $transfer_id, $small_or_large, $file_path, $new_md5_hash ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->transfers_option_name, 'options' );
		wp_cache_delete( 'boldgrid_transfer_cancelled_transfers', 'options' );
		$file_lists = $this->get_option( $this->lists_option_name, array() );
		$transfers  = $this->get_option( $this->transfers_option_name, array() );

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
	
	public function update_file_status( $transfer_id, $small_or_large, $file_path, $status ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->transfers_option_name, 'options' );
		wp_cache_delete( $this->cancelled_transfers_option_name, 'options' );
		$file_lists          = $this->get_option( $this->lists_option_name, array() );
		$transfers           = $this->get_option( $this->transfers_option_name, array() );
		$cancelled_transfers = $this->get_option( $this->cancelled_transfers_option_name, array() );

		if ( 'canceled' === $transfers[ $transfer_id ]['status'] ) {
			wp_die();
		}

		if ( in_array( $transfer_id, $cancelled_transfers ) ) {
			$this->cancel_transfer( $this->transfers_option_name, $transfer_id );
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

	public function rest_post( $site_url, $route, $data, $return = false ) {
		$namespace = $this->migrate_core->configs['REST']['namespace'];
		$request_url = $site_url . '/wp-json/' . $namespace . $route;

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
			$this->migrate_core->log->add( 'Post Rest Error: /wp-json/' . $namespace . $route );
			$this->migrate_core->log->add( 'Post Rest Error: ' . $body );
			return new WP_Error( 'rest_error', 'No Seccess Response' );
		}
	}

	/**
	 * Convert to MM:SS
	 * 
	 * @param int $seconds
	 * 
	 * @return string
	 * 
	 * @since 0.0.5
	 */
	public function convert_to_mmss( $seconds ) {
		if ( $seconds >= 3600 ) { // Check if time is 1 hour or more
			return date_i18n( 'H:i:s', mktime( 0, 0, $seconds ) );
		}
		return date_i18n( 'i:s', mktime( 0, 0, $seconds ) );
	}
}