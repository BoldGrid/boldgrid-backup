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
	public $option_name = 'boldgrid_backup_xfers';

	/**
	 * File List Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $lists_option_name = 'boldgrid_backup_xfer_file_lists';

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $core
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core = $migrate_core;
	}

	/**
	 * Get Backup Dir from boldgrid-backup settings
	 * in `boldgrid_backup_settings` option.
	 */
	public function get_transfer_dir() {
		$settings = get_option( 'boldgrid_backup_settings', array() );

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
	 * Given a file path, this function will split the file
	 * into BOLDGRID_TRANSFER_FILE_CHUNK_SIZE sized chunks.
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
		wp_cache_delete( 'boldgrid_transfer_heartbeat', 'options' );
		update_option( 'boldgrid_transfer_heartbeat', time() );
	}

	public function get_transfer_heartbeat() {
		wp_cache_delete( 'boldgrid_transfer_heartbeat', 'options' );
		return get_option( 'boldgrid_transfer_heartbeat', 0 );
	}

	public function generate_db_dump() {
		global $wpdb;

		$backupFile = $this->get_transfer_dir() . '/db-' . DB_NAME . '-export-' . date('Y-m-d-H-i-s') . '.sql';

		// Get all table names from the database
		$tables = $wpdb->get_results( "SHOW TABLES", ARRAY_N );
		$tables = array_map( function( $table ) {
			return $table[0];
		}, $tables );

		// Initialize the backup content
		$backupContent = '';

		foreach ( $tables as $table ) {
			// Add DROP TABLE IF EXISTS statement
			$backupContent .= "DROP TABLE IF EXISTS `$table`;\n";

			// Get the create table statement
			$createTable    = $wpdb->get_row( "SHOW CREATE TABLE `$table`", ARRAY_N );
			$backupContent .= "\n" . $createTable[1] . ";\n\n";
	
			// Get the table data
			$tableData = $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_N );
			foreach ( $tableData as $row ) {
				$values = array_map( function( $value ) use ( $wpdb ) {
					if ( $value === null ) {
						return 'NULL';
					}
					return "'" . $wpdb->_real_escape($value) . "'";
				}, $row );
				$backupContent .= "INSERT INTO `$table` VALUES(" . implode(", ", $values) . ");\n";
			}
			$backupContent .= "\n";
		}

		// Save the backup content to a file
		if ( ! file_put_contents( $backupFile, $backupContent ) ) {
			wp_die( "Error writing to file" );
		}

		return $backupFile;
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
						'md5'  => md5_file( $fileInfo->getPathname() ),
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
	public function get_largest_file_size( $files, $db_file_size ) {
		$largest = 0;
		foreach ( $files as $file ) {
			if ( $file['size'] > $largest ) {
				$largest = $file['size'];
			}
		}

		return max( $largest, $db_file_size );
	}

	/**
	 * Get Estimated Batch Size
	 * 
	 * Determine what the size in bytes will be of
	 * the average batch of files based on the 
	 * BOLDGRID_TRANSFER_BATCH_CHUNKS and 
	 * BOLDGRID_TRANSFER_CHUNK_SIZE constants,
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

		$average_batch_size = $average_file_size * BOLDGRID_TRANSFER_CHUNK_SIZE;

		error_log( json_encode( array(
			'transfer_size' => $transfer_size,
			'average_file_size' => $average_file_size,
			'file count' => count( $file_list ),
			'files per batch' => BOLDGRID_TRANSFER_CHUNK_SIZE,
			'average_batch_size' => $average_batch_size,
		) ) );

		return $average_batch_size;
	}

	/**
	 * Get Input Vars Limit
	 * 
	 * @return int
	 * 
	 * @since 0.0.1
	 */
	public function get_input_vars_limit() {
		return ini_get('max_input_vars');
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

		$authd_sites = get_option( 'boldgrid_transfer_authd_sites', array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
	
		$response = wp_remote_get(
			$request_url . '?user=' . $user . '&pass=' . base64_encode( $pass ),
			array(
				'timeout' => BOLDGRID_TRANSFER_CONN_TIMEOUT,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error getting REST data: ' . $response->get_error_message() );
			return new WP_Error( 'rest_error', $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		$data = json_decode( $body, true );

		if ( isset ( $data[ $key ] ) ) {
			return $data[ $key ];
		} else {
			return new WP_Error( 'rest_error', 'Requested Key: ' . $key . ' not found in response', $body );
		}
	}

	/**
	 * Generate a random backup id.
	 *
	 * @return string
	 */
	public function gen_transfer_id() {
		$transfers = get_option( 'boldgrid_transfer_rx_list', array() );

		$transfer_id = wp_generate_password( 8, false );

		while ( isset( $transfers[ $transfer_id ] ) ) {
			$transfer_id = wp_generate_password( 8, false );
		}

		return $transfer_id;
	}

	public function cancel_transfer( $transfer_id ) {
		$cancelled_transfers = get_option( 'boldgrid_transfer_cancelled_transfers', array() );

		if ( ! in_array( $transfer_id, $cancelled_transfers ) ) {
			$cancelled_transfers[] = $transfer_id;
		}

		update_option( 'boldgrid_transfer_cancelled_transfers', $cancelled_transfers );

		$transfers = get_option( $this->option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return;
		}

		$transfers[ $transfer_id ]['status'] = 'canceled';
		wp_cache_delete( $this->option_name, 'options' );

		$status_updated = update_option( $this->option_name, $transfers );
	
		return $status_updated;
	}

	public function update_bulk_file_status( $transfer_id, $batch, $status ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->option_name, 'options' );
		wp_cache_delete( 'boldgrid_transfer_cancelled_transfers', 'options' );
		$file_lists = get_option( $this->lists_option_name, array() );
		$transfers  = get_option( $this->option_name, array () );

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
		update_option( $this->lists_option_name, $file_lists );
	}
	
	public function update_file_status( $transfer_id, $small_or_large, $file_path, $status ) {
		wp_cache_delete( $this->lists_option_name, 'options' );
		wp_cache_delete( $this->option_name, 'options' );
		wp_cache_delete( 'boldgrid_transfer_cancelled_transfers', 'options' );
		$file_lists          = get_option( $this->lists_option_name, array() );
		$transfers           = get_option( $this->option_name, array() );
		$cancelled_transfers = get_option( 'boldgrid_transfer_cancelled_transfers', array() );

		if ( 'canceled' === $transfers[ $transfer_id ]['status'] ) {
			wp_die();
		}

		if ( in_array( $transfer_id, $cancelled_transfers ) ) {
			$this->cancel_transfer( $this->option_name, $transfer_id );
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

		update_option( $this->lists_option_name, $file_lists );
		$this->update_transfer_heartbeat();
	}

	public function rest_post( $site_url, $route, $data, $return = false ) {
		$namespace = $this->migrate_core->configs['REST']['namespace'];
		$request_url = $site_url . '/wp-json/' . $namespace . $route;

		$authd_sites = get_option( 'boldgrid_transfer_authd_sites', array() );
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
				'timeout' => BOLDGRID_TRANSFER_CONN_TIMEOUT,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Error getting REST data: ' . $response->get_error_message() );
			return new WP_Error( 'rest_error', $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		$body_data = json_decode( $body, true );

		if ( isset ( $body_data[ 'success' ] ) && ! $return ) {
			return $body_data[ 'success' ];
		} else if ( isset( $body_data[ 'success' ] ) && $return ) {
			return $body_data;
		} else {
			$this->migrate_core->log->add('Rest Post Error: ' . json_encode( $response ) );
			return new WP_Error( 'rest_error', 'No Seccess Response' );
		}
	}
}