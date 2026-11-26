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
	 * Hearbeat Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $heartbeat_option_name;

	/**
	 * Active Tx Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $active_tx_option_name;

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
		$this->active_tx_option_name           = $this->migrate_core->configs['option_names']['active_tx'];
	}

	/**
	 * Get Elapsed Time
	 * 
	 * Gets the elapsed time for a transfer, or
	 * for just the current status.
	 * 
	 * @since 1.17.0
	 *
	 * @param string  $transfer_id  The transfer id
	 * @param boolean $formatted    Whether to format the time
	 * @param boolean $return_total Whether to return the total time
	 *
	 * @return float|string The elapsed time as either a float or a formatted string
	 */
	public function get_elapsed_time( $transfer_id, $formatted = false, $return_total = true ) {
		$transfer = $this->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			return;
		}

		$time_tracking = $transfer['time_tracking'];
		$status        = $transfer['status'];

		$inactive_statusus = array( 'completed', 'restore-completed', 'failed', 'canceled' );

		if ( ! $return_total ) {
			$time         = isset( $time_tracking[ $status ] ) ? $time_tracking[ $status ] : array();
			$start_time   = isset( $time['start_time'] ) ? $time['start_time'] : microtime( true );
			$end_time     = isset( $time['end_time'] ) ? $time['end_time'] : microtime( true );
			$elapsed_time = $end_time - $start_time;
			return $formatted ? $this->format_time( $elapsed_time ) : $elapsed_time;
		}

		$elapsed_time = 0;

		foreach ( $time_tracking as $status => $time ) {
			if ( ! in_array( $status, $inactive_statusus ) ) {
				$end_time      = isset( $time['end_time'] ) ? $time['end_time'] : microtime( true );
				$elapsed_time += $end_time - $time['start_time'];
			}
		}

		return $formatted ? $this->format_time( $elapsed_time ) : $elapsed_time;
	}

	/**
	 * Update Elapsed Time
	 * 
	 * Updates the elapsed time for a transfer.
	 *
	 * @param string $transfer_id The transfer id
	 */
	public function update_elapsed_time( $transfer_id ) {
		$transfer      = $this->get_transfer_from_id( $transfer_id );
		$resyncing_db  = isset( $transfer['resyncing_db'] ) ? $transfer['resyncing_db'] : false;
		$status        = $resyncing_db ? 'resyncing-db' : $transfer['status'];
		$time_tracking = $transfer['time_tracking'];

		$inactive_statusus = array( 'completed', 'restore-completed', 'failed', 'canceled' );

		if ( in_array( $status, $inactive_statusus ) ) {
			return;
		}

		if ( ! isset( $time_tracking[ $status ] ) ) {
			$time_tracking[ $status ] = array(
				'start_time' => microtime( true ),
			);
		} else {
			$time_tracking[ $status ]['end_time'] = microtime( true );
		}

		$transfers = $this->get_option( $this->transfers_option_name, array() );
		$transfers[ $transfer_id ]['time_tracking'] = $time_tracking;

		wp_cache_delete( $this->transfers_option_name, 'options' );

		$option_updated = update_option( $this->transfers_option_name, $transfers, true );
	}

	/**
	 * Generate transfer action buttons
	 * 
	 * @since 1.17.0
	 *
	 * @param string $transfer_status The transfer status
	 * @param string $transfer_id     Transfer ID
	 *
	 * @return string HTML Markup for the action buttons
	 */
	public function transfer_action_buttons( $transfer_status, $transfer_id ) {
		$buttons = array(
			'restore' => array(
				'label' => __( 'Restore', 'boldgrid-backup' ),
				'classes' => 'restore-site button-primary',
			),
			'delete' => array(
				'label' => __( 'Delete', 'boldgrid-backup' ),
				'classes' => 'delete-transfer button-secondary',
			),
			'resync' => array(
				'label' => __( 'Resync Database', 'boldgrid-backup' ),
				'classes' => 'resync-database button-secondary',
			),
			'cancel' => array(
				'label' => __( 'Cancel', 'boldgrid-backup' ),
				'classes' => 'cancel-transfer button-secondary',
			),
		);

		$used_buttons = array();

		switch ( $transfer_status ) {
			case 'completed':
				$used_buttons = array(
					$buttons['restore'],
					$buttons['delete'],
					$buttons['resync'],
				);
				break;
			case 'canceled':
			case 'restore-completed':
			case 'restore-completed':
			case 'failed':
				$used_buttons = array(
					$buttons['delete'],
				);
				break;
			default:
				$used_buttons = array(
					$buttons['cancel'],
				);
				break;
		}

		$button_html = '';

		foreach ( $used_buttons as $button ) {
			$button_html .= sprintf(
				'<button class="%s" data-transfer-id="%s">%s</button>',
				esc_attr( $button['classes'] ),
				esc_attr( $transfer_id ),
				esc_html( $button['label'] )
			);
		}

		return $button_html;
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
	 * Whether the current user may run a direct transfer.
	 *
	 * On a network, only a super admin with manage_network may proceed. On a
	 * single site, the user must have manage_options. The administrator role
	 * name is not a capability and is not accepted.
	 *
	 * @since 1.17.5
	 *
	 * @return bool
	 */
	public function user_can_direct_transfer() {
		if ( is_multisite() ) {
			return is_super_admin() && current_user_can( 'manage_network' );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Reduce a transfer id to letters and digits.
	 *
	 * @since 1.17.5
	 *
	 * @param string $transfer_id Raw transfer id.
	 * @return string
	 */
	public function sanitize_transfer_id( $transfer_id ) {
		return preg_replace( '/[^A-Za-z0-9]/', '', (string) $transfer_id );
	}

	/**
	 * Whether a path is a real file inside this transfer's directory.
	 *
	 * The resolved path must stay under the backup directory. The transfer id
	 * must be the directory a transfer actually writes: {destination}/{id}/…
	 * or temp-file-chunks/{id}/…. An id at any other depth does not match.
	 *
	 * @since 1.17.5
	 *
	 * @param string $path        Candidate file path.
	 * @param string $transfer_id Transfer id that owns the file.
	 * @return bool
	 */
	public function path_belongs_to_transfer( $path, $transfer_id ) {
		$transfer_id = $this->sanitize_transfer_id( $transfer_id );
		if ( '' === $transfer_id || ! is_string( $path ) || '' === $path ) {
			return false;
		}

		$base = $this->get_transfer_base_dir();
		if ( false === $base ) {
			return false;
		}

		$real_base = realpath( $base );
		$real_path = realpath( $path );
		if ( false === $real_base || false === $real_path || ! is_file( $real_path ) ) {
			return false;
		}

		$real_base = wp_normalize_path( $real_base );
		$real_path = wp_normalize_path( $real_path );
		$prefix    = trailingslashit( $real_base );

		if ( 0 !== strpos( $real_path, $prefix ) ) {
			return false;
		}

		$relative = ltrim( substr( $real_path, strlen( $real_base ) ), '/' );
		$segments = explode( '/', $relative );

		return isset( $segments[1], $segments[2] ) && $transfer_id === $segments[1];
	}

	/**
	 * Whether a real file stays inside a directory.
	 *
	 * @since 1.17.5
	 *
	 * @param string $path      Candidate file path.
	 * @param string $directory Directory that must contain the file.
	 * @return bool
	 */
	public function path_is_inside_directory( $path, $directory ) {
		if ( ! is_string( $path ) || '' === $path || ! is_string( $directory ) || '' === $directory ) {
			return false;
		}

		$real_base = realpath( $directory );
		$real_path = realpath( $path );
		if ( false === $real_base || false === $real_path || ! is_file( $real_path ) ) {
			return false;
		}

		$real_base = wp_normalize_path( $real_base );
		$real_path = wp_normalize_path( $real_path );

		return 0 === strpos( $real_path, trailingslashit( $real_base ) );
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

		return true;
	}

	/**
	 * Get Transfer Report
	 * 
	 * Get the transfer report for a transfer id.
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $transfer_id The transfer id
	 * 
	 * @return string Json encoded transfer report
	 */
	public function get_transfer_report( $transfer_id ) {
		$bytes_rcvd = $this->get_transfer_prop( $transfer_id, 'bytes_received', 0 );
		
		// Count the number of files transferred.
		$transfer            = $this->get_transfer_from_id( $transfer_id );
		$file_lists          = $this->get_option( $this->lists_option_name, array() );
		$file_list           = $file_lists[ $transfer_id ];
		$file_count          = count( json_decode( $file_list['small'], true ) ) + count( json_decode( $file_list['large'], true ) );
		$total_elapsed_time  = $this->get_elapsed_time( $transfer_id );
		$bytes_per_sec       = $bytes_rcvd / $total_elapsed_time;

		$time_tracking_report = array();

		foreach ( $transfer['time_tracking'] as $status => $time ) {
			$time_tracking_report[ $status ] = $this->format_time( $time['end_time'] - $time['start_time'] );
		}

		$report = array(
			'Total Size Transferred'   => size_format( $bytes_rcvd, 2 ),
			'Total Files Transferred'  => $file_count,
			'Average Transfer Rate'    => size_format( $bytes_per_sec ) . '/s',
			'Total Time Elapsed'       => $this->get_elapsed_time( $transfer_id, true ),
			'Time Tracking'            => $time_tracking_report,
		);

		return json_encode( $report, JSON_PRETTY_PRINT );
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
	 * @param string $transfer_id          The transfer id
	 * @param string $file_path            The path to the file to split
	 * @param string $relative_path        The path relative to the doc root
	 * @param int    $max_upload_size      The max upload size
	 * @param string $status               Status to store in progress
	 * 
	 * @return array of file paths to the chunks
	 */
	public function split_large_file( $transfer_id, $file_path, $relative_path, $max_upload_size, $status ) {
		$transfer_id = $this->sanitize_transfer_id( $transfer_id );
		if ( '' === $transfer_id ) {
			return array();
		}

		$chunk_dir = $this->get_transfer_chunk_dir( $transfer_id );
		if ( false === $chunk_dir ) {
			return array();
		}

		$chunk_size = max( 1, (int) floor( $max_upload_size / 10 ) );

		// determine how many chunks based on chunk size and file size
		$file_size   = filesize( $file_path );
		$chunk_count = (int) ceil( $file_size / $chunk_size );

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

		set_time_limit( 0 );

		while ( ! feof( $handle ) ) {
			$chunk = fread( $handle, $chunk_size ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$chunk_file = $chunk_dir . '/' . $relative_path . '.part-' . $chunk_number;
			file_put_contents( $chunk_file, $chunk );
			$chunk_paths[] = $chunk_file;
			$chunk_number++;
			update_option( $this->active_tx_option_name, array(
				'transfer_id'  => $transfer_id,
				'status'	   => $status,
				'chunk_number' => $chunk_number,
				'chunk_count'  => $chunk_count,
			), false );
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
	 * @return string|WP_Error The REST URL or a WP_Error if unable to get the URL
	 */
	public function get_site_rest_url( $site_url ) {
		$response = wp_remote_get(
			$site_url,
			array(
				'timeout' => $this->migrate_core->configs['conn_timeout']
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$headers      = $response['headers']->getAll();
				// Make sure we have an array of link‐headers
		$raw_links = isset( $headers['link'] )
		    ? (array) $headers['link']
		    : [];
		
		// Flatten all comma-separated links into one array
		$all_links = [];
		foreach ( $raw_links as $header ) {
		    // explode may yield spaces around each link, so trim()
		    $parts = array_map( 'trim', explode( ',', $header ) );
		    $all_links = array_merge( $all_links, $parts );
		}
		
		// Find the first link with rel="https://api.w.org/"
		$wp_json_link = null;
		foreach ( $all_links as $link ) {
		    if ( false !== strpos( $link, 'rel="https://api.w.org/"' ) ) {
		        $wp_json_link = $link;
		        break;
		    }
		}
		
		$wp_json_url = '';
		if ( $wp_json_link ) {
			if ( preg_match( '/<([^>]+)>/', $wp_json_link, $m ) ) {
				$wp_json_url = $m[1];
			}
		}

		if ( empty( $wp_json_url ) ) {
			return new WP_Error(
				'rest_error',
				__(
					'The REST API is not properly configured on the source site. Please ensure that the REST API is properly configured.',
					'boldgrid-backup'
				)
			);
		}

		return $wp_json_url;
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

		$transfer = $this->get_transfer_from_id( $transfer_id );

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
			/*
			 * If we are changing the status, we update the elapsed time
			 * one last time to reflect the 'end_time' of the old status.
			 */
			$this->update_elapsed_time( $transfer_id );

			/*
			 * After the elapsed time is updated, we need to refresh the $transfers array
			 * to get the updated time_tracking data, or else we'll lose the last 
			 * 'end_time' for the old status.
			 */
			$transfers = $this->get_option( $this->transfers_option_name, array() );
		}

		$transfers[ $transfer_id ][ $key ] = $value;

		$this->update_transfer_heartbeat();

		wp_cache_delete( $this->transfers_option_name, 'options' );
		$option_updated = update_option( $this->transfers_option_name, $transfers, false );

		/*
		 * Anytime we update a transfer, we also want to update
		 * the elapsed time for the transfer. In the event that
		 * the status was changed, this will mark the 'start_time'
		 * of the new status
		 */
		if ( 'time_tracking' !== $key && 'resyncing_db' !== $key ) {
			$this->update_elapsed_time( $transfer_id );
		}
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
		$rest_url    = $transfer['source_rest_url'];
		$request_url = $rest_url . $namespace . $prefix . $route;
		$transfer_id = isset( $transfer['transfer_id'] ) ? $transfer['transfer_id'] : 'unset';

		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );
		$response = wp_remote_get(
			$request_url . '?user=' . $user . '&pass=' . base64_encode( $pass ) . '&transfer_id=' . $transfer_id,
			array(
				'timeout' => $this->migrate_core->configs['conn_timeout'],
				'headers' => array(
					'Accept' => 'application/json',
				),
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
			$this->migrate_core->log->add( 'Get Rest Error Headers: ' . json_encode( $headers, JSON_PRETTY_PRINT ) );
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
		$transfer = $this->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			return;
		}
	
		if ( ( isset( $transfer['resyncing_db'] ) && $transfer['resyncing_db'] ) ||
			 isset( $transfer['restore_start_time'] ) ) {
				$this->update_transfer_prop( $transfer_id, 'status', 'completed' );
				return $this->update_transfer_prop( $transfer_id, 'resyncing_db', false );
		}
	
		$cancelled_transfers = $this->get_option( $this->cancelled_transfers_option_name, array() );
		
		if ( ! in_array( $transfer_id, $cancelled_transfers ) ) {
			$cancelled_transfers[] = $transfer_id;
		}

		wp_cache_delete( $this->cancelled_transfers_option_name, 'options' );
		update_option( $this->cancelled_transfers_option_name, $cancelled_transfers, false );

		wp_cache_delete( $this->transfers_option_name, 'options' );

		$status_updated = $this->update_transfer_prop( $transfer_id, 'status', 'canceled' );

		$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' cancelled by user.' );
	
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

		$data['user']        = $auth['user'];
		$data['pass']        = base64_encode( Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' ) );
		$data['transfer_id'] = isset( $transfer['transfer_id'] ) ? $transfer['transfer_id'] : 'unset';
		
		$response = wp_remote_post(
			$request_url,
			array(
				'body' => $data,
				'timeout' => $this->migrate_core->configs['conn_timeout'],
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->migrate_core->log->add( 'Error posting REST data: ' . $response->get_error_message() );
			return $response;
		}

		$body      = wp_remote_retrieve_body( $response );
		$body_data = json_decode( $body, true );

		$bytes = (int) strlen( $body );
		$this->update_transfer_prop(
			$transfer['transfer_id'],
			'bytes_received',
			$this->get_transfer_prop( $transfer['transfer_id'], 'bytes_received', 0 ) + $bytes
		);

		if ( isset ( $body_data[ 'success' ] ) && ! $return ) {
			return $body_data[ 'success' ];
		} else if ( isset( $body_data[ 'success' ] ) && $return ) {
			return $body_data;
		} else {
			$this->migrate_core->log->add( 'Post Rest Error: ' . $request_url );
			$this->migrate_core->log->add( 'Post Rest Error: ' . $body );
			return new WP_Error( 'rest_error', 'No Success Response' );
		}
	}

	/**
	 * Delete a directory
	 * 
	 * Delete a directory and it's contents
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $dir The directory to delete
	 */
	public function delete_directory( $dir ) {
		global $wp_filesystem;
		
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem->rmdir( $dir, true );
	}

	/**
	 * Handle New Auth
	 * 
	 * Process the return from authorizing a new site.
	 * 
	 * @since 1.17.0
	 */
	public function handle_new_auth() {
		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash($_GET['_wpnonce'] ) ), 'boldgrid_backup_direct_transfer_auth' ) ) {
			return;
		}

		if ( ! isset( $_GET['site_url'], $_GET['user_login'], $_GET['password'] ) ) {
			return;
		}
		
		$site_url = sanitize_url( wp_unslash( $_GET['site_url'] ) );
		$user     = sanitize_text_field( wp_unslash( $_GET['user_login'] ) );
		$pass     = sanitize_text_field( wp_unslash( $_GET['password'] ) );

		$authd_sites              = get_option( $this->authd_sites_option_name, array() );
		$authd_sites[ $site_url ] = array(
			'user' => $user,
			'pass' => Boldgrid_Backup_Admin_Crypt::crypt( $pass, 'e' )
		);

		update_option( $this->authd_sites_option_name, $authd_sites, false );
	}

	/**
	 * Reset Status Time.
	 * 
	 * Reset the tracking time for a given status
	 * so the start time is equal to the current time,
	 * and remove the 'end_time' array key if it exists.
	 * 
	 * @since 1.17.0
	 *
	 * @param string $transfer_id The transfer id
	 * @param string $status      The status to reset
	 */
	public function reset_status_time( $transfer_id, $status ) {
		$transfer = $this->get_transfer_from_id( $transfer_id );

		if ( ! $transfer ) {
			return;
		}

		$time_tracking = $transfer['time_tracking'];

		if ( isset( $time_tracking[ $status ] ) ) {
			$time_tracking[ $status ]['start_time'] = time();
			unset( $time_tracking[ $status ]['end_time'] );
		}
	}

	/**
	 * Install Total Upkeep
	 * 
	 * Install Total Upkeep on a site
	 * 
	 * @since 1.17.0
	 *
	 * @param url $url The site url
	 */
	public function install_total_upkeep( $url ) {
		$site_url    = $url;
		$rest_url    = $this->get_site_rest_url( $site_url );
		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		if ( is_wp_error( $rest_url ) ) {
			$this->migrate_core->log->add( 'Error getting site rest url: ' . $rest_url->get_error_message() );
			return $rest_url;
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
					'Accept' => 'application/json',
				),
				'timeout' => $this->migrate_core->configs['conn_timeout'],
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

	/**
	 * Validate Total Upkeep Status
	 * 
	 * @since 1.17.0
	 *
	 * @param string $site_rest_url The site REST URL
	 * @param string $site_url      The site URL
	 */
	public function validate_total_upkeep_status( $site_rest_url, $site_url ) {
		$total_upkeep_status = $this->get_total_upkeep_status(
			array(
				'source_rest_url' => $site_rest_url,
				'source_site_url' => $site_url,
			)
		);

		if ( is_wp_error( $total_upkeep_status ) ) {
			$this->migrate_core->log->add( 'Error getting Total Upkeep Status: ' . $total_upkeep_status->get_error_message() );
			wp_send_json_error(
				array(
					'success' => false,
					'error'   => esc_html__(
						'Error getting Total Upkeep Status', 'boldgrid-backup'
					)
				)
			);
		}

		if ( empty( $total_upkeep_status ) ) {
			$this->migrate_core->log->add( 'Total Upkeep is not installed on the source site.' );
			wp_send_json_error(
				array(
					'success' => false,
					'error'   => sprintf(
						'<p class="notice notice-error">%1$s %2$s %3$s</p>
						<button class="install-total-upkeep button-primary" data-url="%4$s">%5$s</button>',
						esc_html__(
							'Total Upkeep is not installed on the source site.',
							'boldgrid-backup'
						),
						esc_html__(
							'Total Upkeep must be installed on the source site in order to transfer the site.',
							'boldgrid-backup'
						),
						esc_html__(
							'You may click the button below to install Total Upkeep on the source site.',
							'boldgrid-backup'
						),
						esc_url( $site_url ),
						esc_html__( 'Install Total Upkeep', 'boldgrid-backup' )
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
						'<p class="notice notice-error">%1$s %2$s %3$s %4$s %5$s</p>
						<button class="update-total-upkeep button-primary" data-url="%6$s">%7$s</button>',
						esc_html__(
							'Total Upkeep Version is not compatible with this site.',
							'boldgrid-backup'
						),
						esc_html__( 'Total Upkeep must be version', 'boldgrid-backup' ),
						esc_html( $this->min_tu_version ),
						esc_html__( 'or higher in order to transfer the site.', 'boldgrid-backup' ),
						esc_html__(
							'You may click the button below to update Total Upkeep to the newest version on the source site.',
							'boldgrid-backup'
						),
						esc_url( $site_url ),
						esc_html__( 'Update Total Upkeep', 'boldgrid-backup' )
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
						esc_html__(
							'Total Upkeep is installed but is not active on the source site.',
							'boldgrid-backup'
						),
						esc_html__(
							'Total Upkeep must be active on the source site in order to transfer the site.',
							'boldgrid-backup'
						),
						esc_html__(
							'You may click the button below to activate Total Upkeep on the source site.',
							'boldgrid-backup'
						),
						esc_url( $site_url ),
						esc_html__( 'Activate Total Upkeep', 'boldgrid-backup' )
						)
					)
				);
		}

		return true;
	}

	/**
	 * Edit Total Upkeep Status
	 *
	 * Edit the activation status of Total Upkeep
	 *
	 * @since 1.17.0
	 *
	 * @param string $site_url The site URL
	 * @param string $status   The status to set
	 */
	public function edit_total_upkeep_status( $site_url, $status ) {
		$rest_url    = $this->get_site_rest_url( $site_url );
		$authd_sites = $this->get_option( $this->authd_sites_option_name, array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		if ( is_wp_error( $rest_url ) ) {
			$this->migrate_core->log->add( 'Error getting site rest url: ' . $rest_url->get_error_message() );
			return $rest_url;
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
					'Accept' => 'application/json',
				),
				'timeout' => $this->migrate_core->configs['conn_timeout'],
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );

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
	 * @since 1.17.0
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

		if ( is_wp_error( $rest_url ) ) {
			$this->migrate_core->log->add( 'Error getting site rest url: ' . $rest_url->get_error_message() );
			return $rest_url;
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
					'Accept' => 'application/json',
				),
				'timeout' => $this->migrate_core->configs['conn_timeout'],
			)
		); 

		return $this->install_total_upkeep( $site_url );
	}

	/**
	 * Get Total Upkeep Status
	 * 
	 * @since 1.17.0
	 *
	 * @param array $transfer The transfer data
	 */
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
					'Accept' => 'application/json',
				),
				'timeout' => $this->migrate_core->configs['conn_timeout'],
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
	public function format_time( $seconds ) {
		if ( $seconds >= 86400 ) { // 24 hours or more
			$days = floor( $seconds / 86400 );
			$remaining_seconds = $seconds % 86400;
			$time = date_i18n( 'H:i:s', mktime( 0, 0, $remaining_seconds ) );
			return $days . 'd ' . $time;
		} elseif ( $seconds >= 3600 ) { // 1 hour or more, but less than 24 hours
			return date_i18n( 'H:i:s', mktime( 0, 0, $seconds ) );
		}
		// Less than 1 hour
		return date_i18n( 'i:s', mktime( 0, 0, $seconds ) );
	}

	/**
	 * Staging directory for one transfer.
	 *
	 * Built from the backup directory, the source host, and the transfer id.
	 *
	 * @since 1.17.5
	 *
	 * @param array $transfer Transfer data.
	 * @return string|false Absolute directory, or false when a segment is unusable.
	 */
	public function get_transfer_staging_dir( $transfer ) {
		if ( empty( $transfer['source_site_url'] ) || empty( $transfer['transfer_id'] ) ) {
			return false;
		}

		return $this->get_transfer_site_dir( $transfer['source_site_url'], $transfer['transfer_id'] );
	}

	/**
	 * Directory for one transfer with one remote site.
	 *
	 * Built from the backup directory, the remote site host, and the transfer id.
	 *
	 * @since 1.17.5
	 *
	 * @param string $site_url    Remote site URL.
	 * @param string $transfer_id Transfer id.
	 * @return string|false Absolute directory, or false when a segment is unusable.
	 */
	public function get_transfer_site_dir( $site_url, $transfer_id ) {
		$site_dir    = $this->url_to_safe_directory_name( (string) $site_url );
		$transfer_id = (string) $transfer_id;

		if ( ! $this->is_single_path_segment( $site_dir ) || ! $this->is_single_path_segment( $transfer_id ) ) {
			return false;
		}

		$backup_dir = $this->get_transfer_base_dir();
		if ( false === $backup_dir ) {
			return false;
		}

		return $backup_dir . '/' . $site_dir . '/' . $transfer_id;
	}

	/**
	 * Directory holding large file chunks for one transfer.
	 *
	 * @since 1.17.5
	 *
	 * @param string $transfer_id Transfer id.
	 * @return string|false Absolute directory, or false when a segment is unusable.
	 */
	public function get_transfer_chunk_dir( $transfer_id ) {
		$transfer_id = (string) $transfer_id;
		if ( ! $this->is_single_path_segment( $transfer_id ) ) {
			return false;
		}

		$backup_dir = $this->get_transfer_base_dir();
		if ( false === $backup_dir ) {
			return false;
		}

		return $backup_dir . '/temp-file-chunks/' . $transfer_id;
	}

	/**
	 * Backup directory as an absolute path for transfer files.
	 *
	 * A relative setting resolves under ABSPATH.
	 *
	 * @since 1.17.5
	 *
	 * @return string|false Absolute directory, or false when the setting is unusable.
	 */
	public function get_transfer_base_dir() {
		$backup_dir = wp_normalize_path( untrailingslashit( (string) $this->get_transfer_dir() ) );
		if ( $this->path_has_parent_segment( $backup_dir ) ) {
			return false;
		}
		if ( '' !== $backup_dir && ! $this->is_absolute_path( $backup_dir ) ) {
			$parts = $this->safe_relative_parts( $backup_dir );
			if ( false === $parts ) {
				return false;
			}
			$backup_dir = wp_normalize_path( untrailingslashit( ABSPATH ) ) . '/' . implode( '/', $parts );
		}
		if ( '' === $backup_dir || ! $this->is_absolute_path( $backup_dir ) ) {
			return false;
		}

		return $backup_dir;
	}

	/**
	 * Whether a database prefix can be stored as a PHP string literal.
	 *
	 * @since 1.17.5
	 *
	 * @param mixed $prefix Prefix from the source site.
	 * @return bool
	 */
	public function is_valid_db_prefix( $prefix ) {
		return is_string( $prefix ) && 1 === preg_match( '/\A[A-Za-z0-9_]+\z/', $prefix );
	}

	/**
	 * Whether an existing path is the directory or a file inside it.
	 *
	 * @since 1.17.5
	 *
	 * @param string $directory Directory.
	 * @param string $path      Existing path.
	 * @return bool
	 */
	public function existing_path_is_inside( $directory, $path ) {
		$directory_real = realpath( $directory );
		$path_real      = realpath( $path );

		if ( false === $directory_real || false === $path_real || is_link( $path ) ) {
			return false;
		}

		$directory_real = wp_normalize_path( $directory_real );
		$path_real      = wp_normalize_path( $path_real );

		return $path_real === $directory_real || 0 === strpos( $path_real, $directory_real . '/' );
	}

	/**
	 * Absolute path for an entry from get_files_in_dir().
	 *
	 * That listing stores an absolute path when the file is outside ABSPATH,
	 * and an ABSPATH-relative path when it is inside.
	 *
	 * @since 1.17.5
	 *
	 * @param string $path Listed path.
	 * @return string|false
	 */
	public function resolve_listed_path( $path ) {
		if ( ! is_string( $path ) || '' === $path || false !== strpos( $path, "\0" ) ) {
			return false;
		}

		$normalized = wp_normalize_path( $path );
		if ( $this->is_absolute_path( $normalized ) ) {
			return $normalized;
		}

		return wp_normalize_path( trailingslashit( ABSPATH ) . ltrim( $normalized, '/' ) );
	}

	/**
	 * Resolve a relative file path inside a staging directory without creating it.
	 *
	 * @since 1.17.5
	 *
	 * @param string $staging_dir   Existing staging directory.
	 * @param string $relative_path Relative file path.
	 * @return string|false Absolute path, or false when it is not inside staging.
	 */
	public function transfer_file_path( $staging_dir, $relative_path ) {
		$parts = $this->safe_relative_parts( $relative_path );
		if ( false === $parts || ! is_string( $staging_dir ) || '' === $staging_dir ) {
			return false;
		}

		$staging_real = realpath( $staging_dir );
		if ( false === $staging_real ) {
			return false;
		}

		$staging_real = wp_normalize_path( $staging_real );
		$candidate    = $staging_real . '/' . implode( '/', $parts );

		if ( is_link( $candidate ) || ! $this->candidate_stays_inside( $staging_real, $candidate ) ) {
			return false;
		}

		return $candidate;
	}

	/**
	 * Create parent directories and return a file path inside staging.
	 *
	 * Directories are created one level at a time. A link, or a resolved path
	 * that leaves the staging directory, stops the walk before any write.
	 *
	 * @since 1.17.5
	 *
	 * @param string $staging_dir   Staging directory.
	 * @param string $relative_path Relative file path.
	 * @return string|false Absolute file path, or false when the path is rejected.
	 */
	public function prepare_transfer_file( $staging_dir, $relative_path ) {
		$parts = $this->safe_relative_parts( $relative_path );
		if ( false === $parts || ! is_string( $staging_dir ) || '' === $staging_dir ) {
			return false;
		}

		$staging_dir = wp_normalize_path( untrailingslashit( $staging_dir ) );
		if ( ! $this->is_absolute_path( $staging_dir ) || ! wp_mkdir_p( $staging_dir ) ) {
			return false;
		}

		$staging_real = realpath( $staging_dir );
		if ( false === $staging_real ) {
			return false;
		}

		$staging_real = wp_normalize_path( $staging_real );
		$current      = $staging_real;
		$dir_parts    = $parts;
		$filename     = array_pop( $dir_parts );

		foreach ( $dir_parts as $part ) {
			$next = $current . '/' . $part;
			if ( is_link( $next ) ) {
				return false;
			}
			if ( ! is_dir( $next ) && ! mkdir( $next, 0755 ) && ! is_dir( $next ) ) {
				return false;
			}

			$next_real = realpath( $next );
			if ( false === $next_real ) {
				return false;
			}

			$next_real = wp_normalize_path( $next_real );
			if ( 0 !== strpos( $next_real, $staging_real . '/' ) ) {
				return false;
			}

			$current = $next_real;
		}

		$candidate = $current . '/' . $filename;
		if ( is_link( $candidate ) || ! $this->candidate_stays_inside( $staging_real, $candidate ) ) {
			return false;
		}

		return $candidate;
	}

	/**
	 * Relative path segments that cannot leave a base directory.
	 *
	 * @since 1.17.5
	 *
	 * @param string $relative_path Relative path.
	 * @return string[]|false
	 */
	private function safe_relative_parts( $relative_path ) {
		if ( ! is_string( $relative_path ) || '' === $relative_path || false !== strpos( $relative_path, "\0" ) ) {
			return false;
		}

		$relative_path = wp_normalize_path( $relative_path );
		if ( $this->is_absolute_path( $relative_path ) ) {
			return false;
		}

		$safe = array();
		foreach ( explode( '/', $relative_path ) as $part ) {
			if ( '' === $part || '.' === $part ) {
				continue;
			}
			if ( '..' === $part ) {
				return false;
			}
			$safe[] = $part;
		}

		if ( empty( $safe ) ) {
			return false;
		}

		return $safe;
	}

	/**
	 * Whether a path is a single directory segment.
	 *
	 * @since 1.17.5
	 *
	 * @param string $segment Path segment.
	 * @return bool
	 */
	private function is_single_path_segment( $segment ) {
		if ( ! is_string( $segment ) || '' === $segment ) {
			return false;
		}

		if ( false !== strpos( $segment, "\0" ) || false !== strpos( $segment, '/' ) || false !== strpos( $segment, '\\' ) ) {
			return false;
		}

		return '.' !== $segment && '..' !== $segment;
	}

	/**
	 * Whether any segment is a parent-directory reference.
	 *
	 * @since 1.17.5
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function path_has_parent_segment( $path ) {
		foreach ( explode( '/', wp_normalize_path( (string) $path ) ) as $part ) {
			if ( '..' === $part ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a normalized path is absolute.
	 *
	 * @since 1.17.5
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_absolute_path( $path ) {
		if ( ! is_string( $path ) || '' === $path ) {
			return false;
		}

		if ( '/' === $path[0] ) {
			return true;
		}

		return 1 === preg_match( '/^[A-Za-z]:\//', $path );
	}

	/**
	 * Whether the nearest existing ancestor of a candidate stays inside staging.
	 *
	 * The file itself may not exist yet, so the check walks up to the first
	 * existing path and compares that real path with the staging directory.
	 *
	 * @since 1.17.5
	 *
	 * @param string $staging_real Absolute real staging directory.
	 * @param string $candidate    Candidate file path.
	 * @return bool
	 */
	private function candidate_stays_inside( $staging_real, $candidate ) {
		$staging_real = wp_normalize_path( $staging_real );
		$candidate    = wp_normalize_path( $candidate );

		if ( 0 !== strpos( $candidate, $staging_real . '/' ) ) {
			return false;
		}

		$ancestor = $candidate;
		while ( ! file_exists( $ancestor ) && ! is_link( $ancestor ) ) {
			$parent = dirname( $ancestor );
			if ( $parent === $ancestor ) {
				return false;
			}
			$ancestor = $parent;
		}

		if ( is_link( $ancestor ) ) {
			return false;
		}

		$ancestor_real = realpath( $ancestor );
		if ( false === $ancestor_real ) {
			return false;
		}

		$ancestor_real = wp_normalize_path( $ancestor_real );

		return $ancestor_real === $staging_real || 0 === strpos( $ancestor_real, $staging_real . '/' );
	}
}
