<?php
/**
 * File: class-boldgrid-transfer-rx.php
 * 
 * The main class for the receiving ( rx ) of the Transfer process.
 * 
 * @link https://www.boldgrid.com
 * @since 0.0.1
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Rx
 * 
 * The main class for the receiving ( rx ) of the Transfer process.
 *
 * @since 0.0.1
 */
class Boldgrid_Backup_Admin_Migrate_Rx {
	/**
	 * Boldgrid_Backup_Admin_Migrate
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 0.0.1
	 */
	public $migrate_core;

	/**
	 * Rest API instance
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Rx_Rest
	 * 
	 * @since 0.0.1
	 */
	public $rest;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 0.0.1
	 */
	public $util;

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
	 * Transfer Cron Interval
	 * 
	 * @var int
	 * 
	 * @since 0.0.1
	 */
	public $transfer_cron_interval = 60;



	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core   = $migrate_core;
		$this->util            = $this->migrate_core->util;

		$this->add_hooks();
	}

	/**
	 * Add hooks
	 * 
	 * @since 0.0.1
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_boldgrid_transfer_verify_files', array( $this, 'ajax_verify_files' ) );
		add_action( 'wp_ajax_boldgrid_transfer_start_rx', array( $this, 'ajax_start_handler' ) );
		add_action( 'wp_ajax_boldgrid_transfer_migrate_site', array( $this, 'ajax_migrate_site' ) );
		add_action( 'wp_ajax_boldgrid_transfer_cancel_transfer', array( $this, 'ajax_cancel_transfer' ) );
		add_action( 'wp_ajax_boldgrid_transfer_delete_transfer', array( $this, 'ajax_delete_transfer' ) );
		add_action( 'boldgrid_transfer_process_transfers', array( $this, 'wp_cron_process_transfers' ) );

		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( 'admin_init', array( $this, 'cron_activation' ) );
		add_action( 'shutdown', array( $this, 'cron_deactivation' ) );
	}

	/**
	 * WP Cron Process Transfers.
	 * 
	 * This is just a wrapper for the 'process_transfers' method
	 * but I was having trouble confirming that an action was being
	 * called in the cron job, so I added this method to confirm that
	 * in the backtrace.
	 *
	 * @return void
	 */
	public function wp_cron_process_transfers() {
		$this->migrate_core->log->add( 'Processing Transfers via WP Cron' );
		$this->process_transfers();
	}


	/**
	 * Add cron interval
	 * 
	 * @since 0.0.1
	 */
	public function add_cron_interval( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => $this->transfer_cron_interval,
			'display'  => esc_html__( 'Every Minute' ),
		);

		return $schedules;
	}

	/**
	 * Cron Deactivation
	 * 
	 * Check the transfers list for any pending or active
	 * transfers. If there are none, then deactivate the cron
	 * job.
	 * 
	 * @since 0.0.3
	 */
	public function cron_deactivation() {
		$incomplete_transfers = $this->get_incomplete_transfers();

		if ( wp_next_scheduled( 'boldgrid_transfer_process_transfers' ) && empty( $incomplete_transfers ) ) {
			wp_clear_scheduled_hook( 'boldgrid_transfer_process_transfers' );
			$this->migrate_core->log->add( 'Cron Deactivated: boldgrid_transfer_process_transfers' );
		}
	}

	/**
	 * cron_activation
	 * 
	 * @since 0.0.1
	 */
	public function cron_activation() {
		$incomplete_transfers = $this->get_incomplete_transfers();

		if ( empty( $incomplete_transfers ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'boldgrid_transfer_process_transfers' ) ) {
			$scheduled = wp_schedule_event( time(), 'every_minute', 'boldgrid_transfer_process_transfers' );
			$this->migrate_core->log->add( 'Cron Activation: ' . json_encode( $scheduled ) );
		}
	}

	public function ajax_delete_transfer() {
		check_ajax_referer( 'boldgrid_transfer_delete_transfer', 'nonce' );

		global $wp_filesystem;

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );
		$transfers   = get_option( $this->option_name, array() );

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

		$this->cleanup_filelists();

		if ( $deleted ) {
			unset( $transfers[ $transfer_id ] );
			update_option( $this->option_name, $transfers );

			$cancelled_transfers = get_option( 'boldgrid_transfer_cancelled_transfers', array() );
			$cancelled_transfers = array_filter( $cancelled_transfers, function( $id ) use ( $transfer_id ) {
				return $id !== $transfer_id;
			} );

			update_option( 'boldgrid_transfer_cancelled_transfers', array_values( $cancelled_transfers ) );
			$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' deleted.' );
			wp_send_json_success( array( 'message' => 'Transfer Deleted' ) );
		} else {
			$this->migrate_core->log->add( 'Error deleting transfer: ' . $transfer_id );
			wp_send_json_error( array( 'message' => 'Error Deleting Transfer' ) );
		}

	}

	public function cleanup_filelists() {
		$transfers = get_option( $this->option_name, array() );
		$transfer_ids = array_keys( $transfers );

		$file_lists = get_option( $this->lists_option_name, array() );

		foreach( $file_lists as $transfer_id => $file_list ) {
			if ( ! in_array( $transfer_id, $transfer_ids ) ) {
				unset( $file_lists[ $transfer_id ] );
			}
		}

		update_option( $this->lists_option_name, $file_lists );
	}

	public function ajax_cancel_transfer() {
		check_ajax_referer( 'boldgrid_transfer_cancel_transfer', 'nonce' );

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );

		$transfers = get_option( $this->option_name, array() );

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
	 * Migrate the site
	 * 
	 * @since 0.0.1
	 */
	public function ajax_migrate_site() {
		check_ajax_referer( 'boldgrid_transfer_migrate_site', 'nonce' );
		global $wp_filesystem;

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );

		$transfers = get_option( $this->option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to migrate invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$transfer = $transfers[ $transfer_id ];

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$files   = $this->util->get_files_in_dir( $transfer_dir );
		$db_file = '';
		//Find the database dump file, and remove it from the list.
		$start_time = microtime( true );
		foreach( $files as $index => $file ) {
			if ( false !== strpos( $file['path'], $transfer['db_dump_filepath'] ) ) {
				$db_file = $file;
				unset( $files[ $index ] );
				break;
			}
		}

		foreach( $files as $file ) {
			$relative_path  = str_replace( $transfer_dir, '', $file['path'] );
			$dest_file_path = ABSPATH . $relative_path;
			$this->util->create_dirpath( $dest_file_path );
			if ( file_exists( $file['path'] ) ) {
				copy( $file['path'], $dest_file_path );
			} else {
				$this->migrate_core->log->add( 'File does not exist: ' . $file['path'] );
			}
		}

		$db_prefix = null;

		// Get the database table prefix from the new "wp-config.php" file, if exists.
		if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wpcfg_contents = $wp_filesystem->get_contents( ABSPATH . 'wp-config.php' );
		}

		if ( ! empty( $wpcfg_contents ) ) {
			preg_match( '#\$table_prefix.*?=.*?' . "'" . '(.*?)' . "'" . ';#', $wpcfg_contents, $matches );

			if ( ! empty( $matches[1] ) ) {
				$db_prefix = $matches[1];
			}
		}

		$this->restore_database( $db_file['path'], $db_prefix );

		$end_time = microtime( true );
		$time_to_migrate = $end_time - $start_time;
		$this->migrate_core->log->add( 'Completed migration for transfer ID ' . $transfer_id . 'in ' . $time_to_migrate . ' seconds.' );
		wp_send_json_success( array( 'message' => 'Site Migrated', 'time_to_migrate' => $time_to_migrate ) );
	}

	/**
	 * Restore the WordPress database from a dump file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Test::run_functionality_tests()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 * @see Boldgrid_Backup_Admin_Utility::update_siteurl()
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 * @global wpdb $wpdb The WordPress database class object.
	 * @global WP_Rewrite $wp_rewrite Core class used to implement a rewrite component API.
	 *
	 * @param  string $db_dump_filepath File path to the mysql dump file.
	 * @param  string $db_prefix        The database prefix to use, if restoring and it changed.
	 * @param  bool   $db_encrypted     Is the database dump file encrypted.
	 * @return bool Status of the operation.
	 */
	public function restore_database( $db_dump_filepath, $db_prefix = null, $db_encrypted = false ) {
		// Check input.
		if ( empty( $db_dump_filepath ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The database dump file was not found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			$this->migrate_core->log->add( 'The database dump file was not found.' );

			return false;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Get the WP Options for "siteurl" and "home", to restore later.
		$wp_siteurl = get_option( 'siteurl' );
		$wp_home    = get_option( 'home' );

		// Import the dump file.
		$importer = new Boldgrid_Backup_Admin_Db_Import();
		$status   = $importer->import( $db_dump_filepath );

		$this->migrate_core->log->add( 'Database import status: ' . $status );

		// Set the database prefix, if supplied/changed.
		if ( ! empty( $db_prefix ) ) {
			// Connect to the WordPress database via $wpdb.
			global $wpdb;

			// Set the database table prefix.
			$wpdb->set_prefix( $db_prefix );
		}

		// Clear the WordPress cache.
		wp_cache_flush();

		/**
		 * In addition to flushing the cache (above), some classes may have cached option values (or other
		 * data from the database) in some other way - e.g. in class properties. That cached data cannot
		 * be flushed with wp_cache_flush.
		 *
		 * For example, the global $wp_rewrite class caches the permalink_structure option's value during
		 * its init method. Running the cache flush above will not update this class' permalink_structure
		 * property.
		 *
		 * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-rewrite.php#L1894
		 *
		 * This is important because if trying to rebuild the .htaccess file, it won't rebuild correctly
		 * based on the permalink_structure option in the database just restored.
		*/
		global $wp_rewrite;
		$wp_rewrite->init();

		// Get the restored "siteurl" and "home".
		$restored_wp_siteurl = get_option( 'siteurl' );
		$restored_wp_home    = get_option( 'home' );

		// If changed, then update the siteurl in the database.
		if ( $restored_wp_siteurl !== $wp_siteurl ) {
			$update_siteurl_success =
				Boldgrid_Backup_Admin_Utility::update_siteurl( array(
					'old_siteurl' => $restored_wp_siteurl,
					'siteurl'     => $wp_siteurl,
				) );

			if ( ! $update_siteurl_success ) {
				// Display an error notice.
				do_action(
					'boldgrid_backup_notice',
					esc_html__(
						'The WordPress siteurl has changed.  There was an issue changing it back.  You will have to fix the siteurl manually in the database, or use an override in your wp-config.php file.',
						'boldgrid-backup'
					),
					'notice notice-error is-dismissible'
				);
			}
		}

		// If changed, then restore the WP Option for "home".
		if ( $restored_wp_home !== $wp_home ) {

			// There may be a filter, so remove it.
			remove_all_filters( 'pre_update_option_home' );

			update_option( 'home', untrailingslashit( $wp_home ) );
		}

		// Return success.
		return true;
	}

	/**
	 * Start the transfer process
	 * 
	 * @since 0.0.1
	 */
	public function ajax_start_handler() {
		check_ajax_referer( 'boldgrid_transfer_start_rx', 'nonce' );

		$site_url = sanitize_text_field( $_POST['url'] );

		$authd_sites = get_option( 'boldgrid_transfer_authd_sites', array() );
		
		if ( ! isset( $authd_sites[ $site_url ] ) ) {
			$this->migrate_core->log->add( 'Site ' . $site_url . ' not authenticated.' );
			wp_send_json_error( array( 'message' => 'Site not authenticated' ) );
		}

		$auth = $authd_sites[ $site_url ];

		$this->create_new_transfer( $site_url, $auth['user'], $auth['pass'] );
	}

	/**
	 * Create a new transfer
	 * 
	 * @since 0.0.1
	 */
	public function create_new_transfer( $site_url, $user, $pass ) {
		$transfer_status = 'pending';

		$this->cleanup_filelists();

		$transfer_id = $this->util->gen_transfer_id();

		//Start time for DB transfer
		$start_time = microtime( true );

		// Generate Database Dump
		$generate_db_dump = $this->util->rest_get(
			$site_url,
			'generate-db-dump',
			'generate_db_dump'
		);

		// End time for DB transfer
		$end_time = microtime( true );

		if ( is_wp_error( $generate_db_dump ) ) {
			$this->migrate_core->log->add( 'Error generating database dump: ' . $generate_db_dump->get_error_message() );
			return $generate_db_dump;
		}

		// Get DB Info.
		$db_file_path = $generate_db_dump['path'];
		$db_file_size = $generate_db_dump['size'];
		$db_file_hash = $generate_db_dump['hash'];


		// DB Transfer rate:
		$transfer_rate = $db_file_size / ( $end_time - $start_time );

		// Generate File List & hashes;
		$file_list = $this->util->rest_get(
			$site_url,
			'generate-file-list',
			'file_list'
		);

		if ( is_wp_error( $file_list ) ) {
			return $file_list;
		}

		//Determine Largest File.
		$largest_file_size = $this->util->get_largest_file_size( json_decode( $file_list, true ), $db_file_size );

		$max_upload_size = $this->util->get_max_upload_size();

		$source_wp_version = $this->util->rest_get(
			$site_url,
			'get-wp-version',
			'wp_version'
		);


		$transfer = array(
			'transfer_id'        => $transfer_id,
			'status'             => $transfer_status,
			'source_site_url'    => $site_url,
			'dest_site_url'      => get_site_url(),
			'source_wp_version'  => $source_wp_version,
			'db_dump_filepath'   => $db_file_path,
			'db_dump_md5_hash'   => $db_file_hash,
			'largest_file_size'  => $largest_file_size,
			'rx_max_upload_size' => $max_upload_size,
			'transfer_rate'      => $transfer_rate,
			'start_time'         => microtime( true ), 
			'end_time'           => 0,
			'time_elapsed'       => 0,
		);

		$transfer_list = get_option( $this->option_name, array() );

		$transfer_list[ $transfer_id ] = $transfer;

		$file_lists = get_option( $this->lists_option_name, array() );

		[ $small_file_list, $large_file_list ] = $this->util->split_file_list( $file_list, $max_upload_size );

		$file_lists[ $transfer_id ] = array(
			'small' => $small_file_list,
			'large' => $large_file_list
		);

		update_option( $this->option_name, $transfer_list );
		update_option( $this->lists_option_name, $file_lists );

		$this->update_open_batches( array() );

		$this->migrate_core->log->add( 'Created new transfer: ' . $transfer_id );
		$this->migrate_core->log->add( 'Transfer Info: ' . json_encode( $transfer ) );
		$this->migrate_core->log->add( 'New Xfer Num Open Batches: ' . $this->determine_open_batches() );

		wp_send_json_success( array( 'success' => true, 'transfer_id' => $transfer_id ) );
	}

	/**
	 * Get Incomplete Transfers
	 * 
	 * Retrieve list of incomplete transfers
	 * 
	 * @since 0.0.3
	 * 
	 * @return array List of incomplete transfers
	 */
	public function get_incomplete_transfers() {
		$transfers = get_option( $this->option_name, array() );

		$incomplete_transfers = array();

		$non_pending_statuses = array(
			'completed',
			'failed',
			'canceled',
		);

		foreach( $transfers as $transfer_id => $transfer ) {
			if ( ! in_array( $transfer['status'], $non_pending_statuses ) ) {
				$incomplete_transfers[] = $transfer;
			}
		}

		return $incomplete_transfers;
	}

	/**
	 * Process the transfers
	 * 
	 * @since 0.0.1
	 */
	public function process_transfers() {
		$incomplete_transfers = $this->get_incomplete_transfers();

		if ( empty( $incomplete_transfers ) ) {
			return;
		}

		$this->process_active_transfer( array_shift( $incomplete_transfers ) );
	}

	public function fix_stalled_transfer( $transfer ) {
		$time_since_last_heartbeat = time() - $this->util->get_transfer_heartbeat();

		// If the last heartbeat was more than 120 seconds ago, then find the stalled file / files and retry them.
		if ( BOLDGRID_TRANSFER_STALLED_TIMEOUT > $time_since_last_heartbeat ) {
			return;
		}

		$this->migrate_core->log->add( 'Transfer has likely stalled: Time Since Last Heartbeat: ' . $time_since_last_heartbeat );
		$this->util->update_transfer_heartbeat();

		switch( $transfer['status'] ) {
			case 'transferring-large-files':
				$this->retry_stalled_large_files( $transfer );
				$this->process_transfers();
				break;
			case 'transferring-small-files':
				$this->retry_stalled_small_files( $transfer );
				$this->process_transfers();
		}
	}

	/**
	 * Retry stalled large files
	 * 
	 * @since 0.0.1
	 */
	public function retry_stalled_large_files( $transfer ) {
		// Stalled files will be marked as 'transferring' in the file list.
		// We need to find the stalled files, and retry them.
		$transfer_id = $transfer['transfer_id'];
		$file_lists  = get_option( $this->lists_option_name, array() );
		$file_list   = json_decode( $file_lists[ $transfer_id ]['large'], true );

		foreach( $file_list as $file ) {
			if ( 'transferring' === $file['status'] ) {
				$this->migrate_core->log->add( 'Retrying stalled file: ' . $file['path'] );
				$this->util->update_file_status( $transfer_id, 'large', $file['path'], 'pending' );
			}
		}
	}

	/**
	 * Process the active transfer
	 * 
	 * @since 0.0.1
	 */
	public function process_active_transfer( $transfer ) {
		$transfer_id     = $transfer['transfer_id'];
		$transfer_status = $transfer['status'];

		$this->fix_stalled_transfer( $transfer );

		/*
		 * The verify_files method will attempt to process
		 * transfers if it's in a pending state, which would
		 * result in an infinite loop. Don't do that
		 */
		if ( 'pending' !== $transfer_status ) {
			$this->verify_files( $transfer_id );
		}

		switch( $transfer_status ) {
			case 'pending':
				$this->update_transfer_status( $transfer_id, 'pending-db-tx' );
				$this->process_db_rx( $transfer );
				break;
			case 'db-transferred':
				$this->update_transfer_status( $transfer_id, 'transferring-large-files' );
				$this->process_large_files_rx( $transfer_id );
				break;
			case 'transferring-small-files':
				$this->process_small_files_rx( $transfer_id );
				break;
			case 'transferring-large-files':
				$this->process_large_files_rx( $transfer_id );
				break;
		}
	}

	/**
	 * Handle AJAX request to verify files.
	 *
	 * @since 0.0.1
	 */
	public function ajax_verify_files() {
		check_ajax_referer( 'boldgrid_transfer_verify_files', 'nonce' );

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );
		$verification_data = $this->verify_files( $transfer_id );

		if ( isset( $verification_data['error'] ) && $verification_data['error'] ) {
			wp_send_json_error( array( 'message' => $verification_data['message'] ) );
		}

		wp_send_json_success( $verification_data );
	}

	/**
	 * Verify the files for a transfer.
	 *
	 * @since 0.0.1
	 *
	 * @param string $transfer_id The ID of the transfer to verify.
	 * @return array Verification data.
	 */
	public function verify_files( $transfer_id, $include_missing_files = false ) {
		$transfers   = get_option( $this->option_name, array() );
		$file_lists  = get_option( $this->lists_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to verify files for an invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			return array(
				'error' => true,
				'message' => 'Invalid transfer ID: ' . $transfer_id,
			);
		}

		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ];

		if ( 'pending' === $transfer['status'] ) {
			$this->process_transfers();
			return array('status' => 'Transfer Still Pending');
		}

		if ( 'completed' === $transfer['status'] ) {
			return array( 'status' => 'completed', 'elapsed_time' => $transfer['time_elapsed'] );
		}

		$files = ( 'transferring-small-files' === $transfer['status'] )
			? ( json_decode( $file_list['small'], true ) ?: array() )
			: ( json_decode( $file_list['large'], true ) ?: array() );

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$total_file_count      = count( $files );
		$completed_count       = 0;
		$md5_failed_files      = array();
		$files_not_received    = array();
		$files_marked_complete = 0;

		foreach ( $files as $file ) {
			if ( 'transferred' === $file['status'] ) {
				$files_marked_complete++;
			}
		}

		foreach ( $files as $file ) {
			$rcvd_file = $transfer_dir . $file['path'];
			if ( file_exists( $rcvd_file ) ) {
				$rcvd_file_hash = md5_file( $rcvd_file );

				if ( $rcvd_file_hash === $file['md5'] ) {
					$completed_count++;
					$file['status'] = 'transferred';
				} else {
					$md5_failed_files[] = $file['path'];
					$file['status'] = 'failed';
				}
			} else {
				$files_not_received[] = $file;
			}
		}

		$progress = $total_file_count > 0 ? ( $completed_count / $total_file_count ) * 100 : 0;

		$progress_text = sprintf(
			'%1$s / %2$s Files (%3$s%%)',
			$completed_count,
			$total_file_count,
			number_format( $progress, 2 )
		);

		$status = $transfers[ $transfer_id ]['status'];

		if ( $completed_count === $total_file_count && 'transferring-small-files' === $status ) {
			$this->update_transfer_status( $transfer_id, 'completed' );
			$status = 'completed';
			$elapsed_time = $this->update_elapsed_time( $transfer_id );
			$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' completed in ' . $elapsed_time . ' seconds.' );
		} else if ( $completed_count === $total_file_count && 'transferring-large-files' === $status ) {
			$this->update_transfer_status( $transfer_id, 'transferring-small-files' );
			$status = 'transferring-small-files';
			$elapsed_time = $this->update_elapsed_time( $transfer_id );
		} else {
			$elapsed_time = $this->update_elapsed_time( $transfer_id, false );
		}

		$progress_status_text = $this->get_progress_status_text( $status );

		$this->migrate_core->log->add( 'Transfer ' . $transfer_id . ' progress: ' . $progress_text . ' - ' . round( $elapsed_time ) . 's' );

		$verification_data = array(
			'transfer_id'   => $transfer_id,
			'status'        => $transfer['status'],
			'progress'      => $progress,
			'progress_text' => $progress_text,
			'progress_status_text' => $progress_status_text,
			'elapsed_time'  => $elapsed_time
		);

		if ( $include_missing_files ) {
			$verification_data['missing_files'] = $files_not_received;
		}

		return $verification_data;
	}

	/**
	 * Get progress status text based on transfer status.
	 *
	 * @param string $status Transfer status.
	 * @return string Progress status text.
	 */
	private function get_progress_status_text( $status ) {
		switch( $status ) {
			case 'pending-db-tx':
			case 'transferring-db':
				return 'Transferring Database';
			case 'db-transferred':
				return 'Pending File Transfer';
			case 'transferring-small-files':
				return 'Transferring Small Files';
			case 'transferring-large-files':
				return 'Transferring Large Files';
			case 'completed':
				return 'Transfer Complete';
			default:
				return '';
		}
	}

	public function retry_stalled_small_files( $transfer ) {
		$transfer_id = $transfer['transfer_id'];
		$file_list   = get_option( $this->lists_option_name, array() );
		
		$verification_data = $this->verify_files( $transfer_id, true );

		// Remove any files that are not marked as 'transferred'.
		$missing_files = array();

		foreach( $verification_data['missing_files'] as $file ) {
			$missing_files[] = $file['path'];
		}

		if ( empty( $missing_files ) ) {
			return;
		}

		$this->migrate_core->log->add( 'The following files failed the initial transfer, and are being retried: ' . json_encode( $missing_files ) );
		$this->util->update_bulk_file_status( $transfer_id, $missing_files, 'pending' );
	}

	public function remove_stale_batches() {
		$open_batches = get_option( 'boldgrid_transfer_open_batches', array() );

		// Select all open batches that are older than 2 minutes.
		$stale_batches = array_filter( $open_batches, function( $batch ) {
			return time() - $batch > 120;
		} );

		if ( empty( $stale_batches ) ) {
			return;
		}

		$this->migrate_core->log->add( 'Removing stale batches: ' . json_encode( $stale_batches ) );

		$open_batches = array_diff_key( $open_batches, $stale_batches );

		update_option( 'boldgrid_transfer_open_batches', $open_batches );
	}

	public function determine_open_batches() {
		wp_cache_delete( 'boldgrid_transfer_open_batches', 'options' );

		$this->remove_stale_batches();
		
		return count( get_option( 'boldgrid_transfer_open_batches', array() ) );
	}

	public function update_open_batches( $open_batches = array() ) {
		wp_cache_delete( 'boldgrid_transfer_open_batches', 'options' );
			
		update_option( 'boldgrid_transfer_open_batches', $open_batches );
	}

	public function update_transfer_rate( $transfer_id, $transfer_rate ) {
		$transfers = get_option( $this->option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return;
		}

		$transfers[ $transfer_id ]['transfer_rate'] = $transfer_rate;

		$this->migrate_core->log->add( 'Batch transfer rate: ' . size_format( $transfer_rate, 2 ) . ' /second' );

		wp_cache_delete( $this->option_name, 'options' );
		return update_option( $this->option_name, $transfers );
	}

	public function update_transfer_status( $transfer_id, $status ) {
		$transfers = get_option( $this->option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return;
		}

		$this->migrate_core->log->add(
			"Transfer $transfer_id status updated from {$transfers[ $transfer_id ]['status']} to $status"
		);

		$transfers[ $transfer_id ]['status'] = $status;

		$this->util->update_transfer_heartbeat();

		wp_cache_delete( $this->option_name, 'options' );
		return update_option( $this->option_name, $transfers );
	}

	public function process_small_files_rx( $transfer_id ) {
		$transfers  = get_option( $this->option_name, array() );
		$file_lists = get_option( $this->lists_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			return;
		}

		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ]['small'];

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 2 );
		$file_batch = $this->create_file_batch( $transfer, json_decode( $file_list, true ) );

		if ( empty( $file_batch ) ) {
			$this->migrate_core->log->add( 'No files to transfer or max batches processing.' );
			return;
		}

		$this->process_batch( $transfer, $file_batch, 'retrieve-files' );

		$depth_of_stack = count( debug_backtrace() );

		if ( 0 >= intval( $this->determine_open_batches() ) && 100 > $depth_of_stack ) {
			$this->process_transfers();
		}
	}

	public function split_large_files( $transfer_id ) {
		$transfers  = get_option( $this->option_name, array() );
		$file_lists = get_option( $this->lists_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			return;
		}

		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ]['large'];


		// Remove any files that are not either pending or failed.
		$files = json_decode( $file_list, true );
	
		$pending_files = array_filter( $files, function( $file ) {
			return 'pending' === $file['status'] || 'failed' === $file['status'];
		} );

		if ( empty( $pending_files ) ) {
			return;
		}

		foreach( $pending_files as $file ) {
			$this->util->update_file_status( $transfer_id, 'large', $file['path'], 'splitting' );
		}

		$pending_file_list = json_encode( $pending_files );

		$response = $this->util->rest_post(
			$transfer['source_site_url'],
			'split-large-files',
			array(
				'transfer_id' => $transfer_id,
				'max_upload_size' => $transfer['rx_max_upload_size'],
				'files' => $pending_file_list,
			),
			true
		);

		if ( ! isset( $response['split_files' ] ) ) {
			$this->migrate_core->log->add( 'Error splitting large files: ' . json_encode( $response ) );
			return;
		}

		$split_files = json_decode( $response['split_files'], true );

		// Update the file list with the split files.
		foreach( $split_files as $split_file ) {
			$path = $split_file['path'];
			// check the $files array for a file with the same path
			$index = array_search( $path, array_column( $files, 'path' ) );
			// if the file is found, update the status to 'ready-to-transfer'
			// and add the split files to the file list
			if ( false !== $index ) {
				$files[ $index ]['status'] = 'ready-to-transfer';
				$files[ $index ]['parts']  = $split_file['parts'];
			}
		}

		$file_lists[ $transfer_id ]['large'] = json_encode( $files );

		update_option( $this->lists_option_name, $file_lists );
	}

	public function process_large_files_rx( $transfer_id ) {

		$this->split_large_files( $transfer_id );
		
		$transfers  = get_option( $this->option_name, array() );
		$transfer   = $transfers[ $transfer_id ];

		$file_lists = get_option( $this->lists_option_name, array() );
		$file_list  = $file_lists[ $transfer_id ]['large'];

		$file_list = json_decode( $file_list, true );

		// Only process files that are marked as 'ready-to-transfer'
		$split_files = array_filter( $file_list, function( $file ) {
			return 'ready-to-transfer' === $file['status'];
		} );

		foreach ( $split_files as $index => $split_file ) {
			$this->util->update_file_status( $transfer_id, 'large', $split_file['path'], 'transferring' );
			$this->process_large_file_rx( $transfer, $split_file );
		}
	}

	public function add_open_batch( $batch_id ) {
		wp_cache_delete( 'boldgrid_transfer_open_batches', 'options' );
		$open_batches = get_option( 'boldgrid_transfer_open_batches', array() );
		$open_batches[ $batch_id ] = time();

		update_option( 'boldgrid_transfer_open_batches', $open_batches );
		$this->util->update_transfer_heartbeat();
	}

	public function remove_open_batch( $batch_id ) {
		wp_cache_delete( 'boldgrid_transfer_open_batches', 'options' );
		$open_batches = get_option( 'boldgrid_transfer_open_batches', array() );
		unset( $open_batches[ $batch_id ] );

		update_option( 'boldgrid_transfer_open_batches', $open_batches );
	}

	public function process_batch( $transfer, $file_batch, $route ) {
		$site_url = $transfer['source_site_url'];

		$ch_batch = array();

		$mh          = curl_multi_init();
		$namespace   = $this->migrate_core->configs['REST']['namespace'];
		$request_url = $site_url . '/wp-json/' . $namespace . $route;

		$authd_sites = get_option( 'boldgrid_transfer_authd_sites', array() );
		$auth        = isset( $authd_sites[ $site_url ] ) ? $authd_sites[ $site_url ] : false;

		if ( ! $auth ) {
			$this->migrate_core->log->add( 'Site ' . $site_url . ' not authenticated.' );
			return new WP_Error( 'site_not_authenticated', 'Site not authenticated' );
		}

		$num_open_batches = $this->determine_open_batches();

		$user = $auth['user'];
		$pass = Boldgrid_Backup_Admin_Crypt::crypt( $auth['pass'], 'd' );

		$start_time = microtime( true );
		$total_bytes = 0;
		
		foreach( $file_batch as $batch_id => $batch ) {
			$files = array();

			$this->add_open_batch( $batch_id );

			foreach( $batch as $file ) {
				$files[] = $file['path'];
			}

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $request_url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, array(
				'user'     => $user,
				'pass'     => base64_encode( $pass ),
				'batch_id' => $batch_id,
				'files'    => json_encode( $files, true ),
			) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			$ch_batch[ $batch_id ] = $ch;

			curl_multi_add_handle( $mh, $ch_batch[ $batch_id ] );
		}

		do {
			Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 4 );
			$status = curl_multi_exec( $mh, $active );
			if ( $active ) {
				curl_multi_select( $mh );
			}
		} while ( $active && $status == CURLM_OK );

		$end_time = microtime( true );
		$total_time = $end_time - $start_time;

		foreach ( $ch_batch as $batch_id => $ch ) {
			$response = curl_multi_getcontent( $ch );
			$body     = json_decode( $response, true );

			// Get transfer info
			$info        = curl_getinfo( $ch );
			$size_upload = $info['size_upload'];

			if ( isset( $body['success'] ) ) {
				if ( isset( $body['files'] ) ) {
					Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 10 );
					$files = json_decode( $body['files'], true );
					foreach( $files as $file_path => $file ) {
						$this->process_small_file_rx( $transfer['transfer_id'], $file_path, $file );
					}
				}
			} else {
				$this->migrate_core->log->add( 'Error Receiving Batch: ' . json_encode( $response ) );
				foreach( $file_batch[ $batch_id ] as $file_string ) {
					if ( is_string( $file_string ) ) {
						$file = json_decode( $file_string, true );
						$file_path = $file['path'];
						$this->util->update_file_status( $transfer['transfer_id'], 'small', $file['path'], 'failed' );
					} else {
						$this->util->update_file_status( $transfer['transfer_id'], 'small', $file['path'], 'failed' );
					}
				}
			}

			if ( isset( $body['batch_id'] ) ) {
				$this->remove_open_batch( $body['batch_id'] );
			}

			curl_multi_remove_handle( $mh, $ch );

			error_log( json_encode( array(
				'method' => 'process_batch (inside loop after posting)',
				'open_batches' => $this->determine_open_batches(),
			) ) );
		}

		//Calculate transfer rate in bytes per second
		if ( $total_time > 0 ) {
			$transfer_rate = ( $size_upload / $total_time ) * 10;
		} else {
			$transfer_rate = 0;
		}

		//$this->update_transfer_rate( $transfer['transfer_id'], $transfer_rate );

		curl_multi_close( $mh );

		// Reset execution time limit
		set_time_limit( ini_get( 'max_execution_time' ) );
	}

	public function create_file_batch( $transfer, $file_list ) {
		$transfer_id     = $transfer['transfer_id'];
		$open_batches    = $this->determine_open_batches();
		$num_of_chunks   = BOLDGRID_TRANSFER_BATCH_CHUNKS - $open_batches;
		$max_batch_size  = $num_of_chunks * BOLDGRID_TRANSFER_CHUNK_SIZE;
		$max_upload_size = $this->util->get_max_upload_size();

		$batch = array();
		$current_batch_size = 0;

		// Check if 'transfer_rate is set and greater than zero
		if ( isset( $transfer['transfer_rate'] ) && 0 < $transfer['transfer_rate'] ) {
			// Calculate the maximum batch size that can be transferred in approximately 1 minute
			$transfer_rate = $transfer['transfer_rate'];
			$max_transfer_rate = $transfer_rate * 60;
		} else {
			$max_transfer_rate = $max_upload_size;
		}

		$this->migrate_core->log->add( 'Max Transfer Rate: ' . size_format( $max_transfer_rate, 2 ) . '/m' );

		error_log( json_encode( array(
			'method' => 'create_file_batch',
			'open_batches' => $open_batches,
			'num_of_chunks' => $num_of_chunks,
			'max_batch_size' => $max_batch_size,
			'transfer_rate' => $transfer['transfer_rate'],
			'max_transfer_rate' => size_format( $max_transfer_rate ) . '/minute',
		) ) );

		foreach ( $file_list as $file ) {
			$file_size = $file['size'];

			// Skip files that are not pending
			if ( 'pending' !== $file['status'] ) {
				continue;
			}

			// If a single file is larger than what can be transferred in 1 minute
			if ( $file_size > $max_transfer_rate ) {
				// If batch is empty, add the large file as the only file in the batch
				if ( empty( $batch ) ) {
					$batch[] = $file;
					$current_batch_size += $file_size;
					$open_batches++;
				}
				// Break after adding the large file.
				break;
			}

			// Check if adding the file exceeds the maximum transfer size for 1 minute.
			if ( $current_batch_size + $file_size > $max_transfer_rate ) {
				break;
			}

			if ( $current_batch_size + $file_size > $max_upload_size ) {
				break;
			}

			if ( count( $batch ) + 1 >= $max_batch_size ) {
				break;
			}

			$batch[] = $file;
			$current_batch_size += $file_size;
			$open_batches++;
		}

		if ( empty( $batch ) ) {
			return;
		}

		$this->migrate_core->log->add(
			'Number of Files in current batch: ' . count( $batch ) . ' - Current Batch Size: ' . size_format( $current_batch_size )
		);

		$this->util->update_bulk_file_status( $transfer_id, $batch, 'transferring' );

		$batches = array_chunk( $batch, BOLDGRID_TRANSFER_CHUNK_SIZE );

		$keyed_batches = array();

		foreach( $batches as $batch ) {
			$batch_id = wp_generate_password( 8, false );
			$keyed_batches[ $batch_id ] = $batch;
		}

		return $keyed_batches;
	}

	/**
	 * Process Large File RX
	 *
	 * Process a large file by using curl to retrieve each file part,
	 * and then reassemble the file. The file is then checked against
	 * the MD5 hash to ensure the file was properly received.
	 * 
	 * @param array $transfer
	 * @param array $split_file
	 * @return void
	 */
	public function process_large_file_rx( $transfer, $split_file ) {
		// Use php curl init to retrieve the file parts
		$output_file = $this->util->get_transfer_dir() .
			'/' . $this->util->url_to_safe_directory_name( $transfer['source_site_url'] ) .
			'/' . $transfer['transfer_id'] .
			'/' . $split_file['path'];

		$this->util->create_dirpath( $output_file );
		$handle = fopen( $output_file, 'wb' );

		foreach( $split_file['parts'] as $file_part ) {
			Boldgrid_Backup_Admin_Utility::bump_memory_limit( intval( $transfer['largest_file_size'] ) * 2 );
			$response = $this->util->rest_post(
				$transfer['source_site_url'],
				'retrieve-large-file-part',
				array(
					'transfer_id' => $transfer['transfer_id'],
					'file_part'   => $file_part,
				),
				true
			);

			if ( is_wp_error( $response ) ) {
				$this->migrate_core->log->add( 'Error retrieving large file part: ' . $response->get_error_message() );
				$errors[] = 'Error retrieving large file part: ' . $response->get_error_message();
				break;
			}

			if ( ! isset( $response['file_part'] ) ) {
				$this->migrate_core->log->add( 'Error retrieving large file part: ' . json_encode( $response ) );
				$errors[] = 'Error retrieving large file part: ' . json_encode( $response );
				break;
			}

			fwrite( $handle, base64_decode( $response['file_part'] ) );
			$this->util->update_transfer_heartbeat();
		}

		fclose( $handle );

		$md5_hash = md5_file( $output_file );
		
		if ( $md5_hash !== $split_file['md5'] ) {
			$this->migrate_core->log->add( 'MD5 hash mismatch for file: ' . $output_file );
			$errors[] = 'MD5 hash mismatch for file: ' . $output_file;
		}

		if ( ! empty( $errors ) ) {
			$this->util->update_file_status( $transfer['transfer_id'], 'large', $split_file['path'], 'failed' );
		} else {
			$this->util->update_file_status( $transfer['transfer_id'], 'large', $split_file['path'], 'transferred' );
			$this->util->rest_post(
				$transfer['source_site_url'],
				'delete-large-file-parts',
				array(
					'transfer_id' => $transfer['transfer_id'],
					'file_parts'  => $split_file['parts']
				)
			);
		}
	}

	public function process_small_file_rx( $transfer_id, $file_path, $file_contents ) {
		global $wp_filesystem;
		$transfers  = get_option( $this->option_name, array() );
		$file_lists = get_option( $this->lists_option_name, array() );
		if ( ! isset( $transfers[ $transfer_id ] ) || ! isset( $file_lists[ $transfer_id ] ) ) {
			return;
		}
		$transfer  = $transfers[ $transfer_id ];
		$file_list = $file_lists[ $transfer_id ]['small'];

		if ( 'canceled' === $transfer['status'] ) {
			return;
		}

		$backup_dir  = $this->util->get_transfer_dir();
		$source_dir  = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$backup_dir  = trailingslashit( $backup_dir );
		$transfer_id = $transfer['transfer_id'];

		$rcvd_file = $backup_dir . $source_dir . '/' . $transfer_id . '/' . $file_path;

		if ( ! $this->util->create_dirpath( $rcvd_file ) ) {
			$this->util->update_file_status( $transfer_id, 'small', $file_path, 'pending' );
			$this->migrate_core->log->add( 'There was an error creating the directory path for the file: ' . $rcvd_file );
			return new WP_Error( 'boldgrid_transfer_rx_file_dir_error', __( 'There was an error creating the directory path for the file.', 'boldgrid-transfer' ) );
		}

		$file_contents = base64_decode( $file_contents );
		file_put_contents( $rcvd_file, $file_contents );
		unset( $file_contents );

		if ( ! file_exists( $rcvd_file ) ) {
			$this->util->update_file_status( $transfer_id, 'small', $file_path, 'pending' );
			$this->migrate_core->log->add( 'The file was not properly not received: ' . $rcvd_file );
			return new WP_Error( 'boldgrid_transfer_rx_file_not_received', __( 'The file was properly not received.', 'boldgrid-transfer' ) );
		}

		$new_file_hash = md5_file( $rcvd_file );
		$old_file_hash = '';

		foreach( json_decode( $file_list, true ) as $file ) {
			if ( $file['path'] === $file_path ) {
				$old_file_hash = $file['md5'];
				break;
			}
		}

		if ( ! file_exists( $rcvd_file ) ) {
			$this->util->update_file_status( $transfer_id, 'small', $file_path, 'pending' );
			$this->migrate_core->log->add( 'The file was not properly not received: ' . $rcvd_file );
			return new WP_Error( 'boldgrid_transfer_rx_file_not_received', __( 'The file was properly not received.', 'boldgrid-transfer' ) );
		} elseif ( $new_file_hash !== $old_file_hash ) {
			$filesize = size_format( filesize( $rcvd_file ) );
 			$this->migrate_core->log->add(
				"File hash mismatch for $rcvd_file with a size of $filesize ... Expected: $old_file_hash Got: $new_file_hash",
			);
			$wp_filesystem->delete( $rcvd_file );
			$this->util->update_file_status( $transfer_id, 'small', $file_path, 'pending' );
			return new WP_Error( 'boldgrid_transfer_rx_file_hash_mismatch', __( 'The file hash does not match the expected hash.', 'boldgrid-transfer' ) );
		} else {
			$this->util->update_file_status( $transfer_id, 'small', $file_path, 'transferred' );
			return true;
		}
	}

	public function process_db_rx( $transfer ) {
		error_log( 'Processing DB RX' );
		$safe_dump_filepath = str_replace( '.sql', '', $transfer['db_dump_filepath'] );
		$db_dump = $this->util->rest_get(
			$transfer['source_site_url'],
			'get-db-dump/' . $safe_dump_filepath,
			'db_dump'
		);

		if ( is_wp_error( $db_dump ) ) {
			update_option(
				'boldgrid_transfer_last_error',
				$transfer['source_site_url']
			);
			$this->migrate_core->log->add( 'Error Transferring DB: ' . json_encode( $db_dump ) );
			return $db_dump;
		}

		$transfer_dir = $this->util->get_transfer_dir();
		$source_dir   = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir = trailingslashit( $transfer_dir );

		$db_dump_file = $transfer_dir . $source_dir . '/' . $transfer['transfer_id'] . '/' . basename( $transfer['db_dump_filepath'] );

		$path_created = $this->util->create_dirpath( $db_dump_file, 0755 );

		if ( ! $path_created ) {
			update_option( 'boldgrid_transfer_last_error', 'There was an error creating the directory path for the database dump: ' . date( 'Y-m-d H:i:s' ) );
			$this->migrate_core->log->add( 'There was an error creating the directory path for the database dump ' . $db_dump_file );
			return new WP_Error( 'boldgrid_transfer_rx_db_dir_error', __( 'There was an error creating the directory path for the database dump.', 'boldgrid-transfer' ) );
		}

		file_put_contents( $db_dump_file, base64_decode( $db_dump ) );

		$new_file_hash = md5_file( $db_dump_file );

		if ( $new_file_hash !== $transfer['db_dump_md5_hash'] ) {
			$wp_filesystem->delete( $db_dump_file );
			update_option( 'boldgrid_transfer_last_error', 'Database dump file hash mismatch: ' . date( 'Y-m-d H:i:s' ) );
			$this->migrate_core->log->add( 'DB Dump file hash mismatch. Expected: ' . $transfer['db_dump_md5_hash'] . ' Got: ' . $new_file_hash );
			return new WP_Error( 'boldgrid_transfer_rx_db_hash_mismatch', __( 'The database dump file hash does not match the expected hash.', 'boldgrid-transfer' ) );
		} else {
			$this->update_transfer_status( $transfer['transfer_id'], 'db-transferred' );
			$this->update_elapsed_time( $transfer['transfer_id'] );
			$this->process_transfers();
			return true;
		}
	}

	public function update_elapsed_time( $transfer_id, $save = true ) {
		$transfers = get_option( $this->option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			return;
		}

		$elapsed_time = microtime( true ) - $transfers[ $transfer_id ]['start_time'];

		$transfers[ $transfer_id ]['time_elapsed'] = $elapsed_time;

		if ( $save ) {
			update_option( $this->option_name, $transfers );
		}

		return $elapsed_time;
	}
}