<?php
/**
 * File: class-boldgrid-backup-admin-migrate-restore.php
 * 
 * The class for the handling migration of transfered sites.
 * 
 * @link https://www.boldgrid.com
 * @since 0.0.7
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Restore
 * 
 * The main class for restoring the transferred files & DB.
 *
 * @since 0.0.7
 */
class Boldgrid_Backup_Admin_Migrate_Restore {
	/**
	 * Boldgrid_Transfer Core
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 0.0.1
	 */
	public $migrate_core;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 0.0.1
	 */
	public $util;

	/**
	 * Transfer Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.1
	 */
	public $transfer_option_name;

	/**
	 * File List Option Name
	 * 
	 * @var string
	 * 
	 * @since 0.0.2
	 */
	public $lists_option_name;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Transfer $core
	 * 
	 * @since 0.0.7
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core   = $migrate_core;
		$this->util           = $this->migrate_core->util;

		$this->transfer_option_name = $this->migrate_core->configs['option_names']['transfers'];
		$this->lists_option_name    = $this->migrate_core->configs['option_names']['file_lists'];

		$this->add_hooks();
	}

	/**
	 * Add Hooks
	 * 
	 * @since 0.0.7
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_boldgrid_transfer_migrate_site', array( $this, 'ajax_migrate_site' ) );
	}

	/**
	 * Migrate the site
	 * 
	 * @since 0.0.1
	 */
	public function ajax_migrate_site() {
		check_ajax_referer( 'boldgrid_transfer_migrate_site', 'nonce' );

		$transfer_id = sanitize_text_field( $_POST['transfer_id'] );

		$this->migrate_core->log->init( 'direct-transfer-' . $transfer_id );

		$transfers = $this->util->get_option( $this->transfer_option_name, array() );

		if ( ! isset( $transfers[ $transfer_id ] ) ) {
			$this->migrate_core->log->add(
				'Attempted to migrate invalid transfer: ' . $transfer_id .
				' - Transfer ID must be present in the following list: ' . json_encode( array_keys( $transfers ) )
			);
			wp_send_json_error( array( 'message' => 'Invalid transfer ID.' ) );
		}

		$result = $this->migrate_site( $transfers[ $transfer_id ], $transfer_id );
		
		wp_send_json_success( $result );
	}

	public function migrate_site( $transfer, $transfer_id ) {
		$migrate_start_time = microtime( true );
		$transfer_dir       = $this->util->get_transfer_dir();
		$source_dir         = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir       = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$this->migrate_core->log->add( 'Starting migration for transfer ID ' . $transfer_id );

		// 1. Download and extract the wordpress core files for the version specified in the transfer.
		if ( ! $this->download_extract_wordpress( $transfer_dir, $transfer['source_wp_version'] ) ) {
			$this->migrate_core->log->add( 'Failed to download and extract WordPress core files.' );
			return array(
				'success' => false,
				'error'   => 'Failed to download and extract WordPress core files.'
			);
		}

		// 2. Get an array of files to copy.
		$files = $this->util->get_files_in_dir( $transfer_dir );

		// 3. Separate the database dump file from the files.
		$db_file = $this->seperate_db_from_files( $files, $transfer['db_dump_info']['file'] );

		// 4. Copy the files from the transfer directory to the site's root directory.
		if ( ! $this->copy_files( $files, $transfer_dir ) ) {
			$this->migrate_core->log->add( 'Failed to copy files.' );
			return array(
				'success' => false,
				'error'   => 'Failed to copy files.'
			);
		}
		
		unset( $files );

		$this->migrate_core->log->add( '$db_file: ' . json_encode( $db_file, JSON_PRETTY_PRINT ) );

		// 5. Restore the WordPress database from the dump file.
		if ( ! $this->restore_database( $db_file['path'], $transfer['source_site_url'] ) ) {
			$this->migrate_core->log->add( 'Failed to restore the database.' );
			return array(
				'success' => false,
				'error'   => 'Failed to restore the database.'
			);
		}

		$migration_end_time = microtime( true );
		$time_to_migrate    = $migration_end_time - $migrate_start_time;
		$this->migrate_core->log->add(
			sprintf(
				'Completed migration for transfer ID %1$s in %2$s.',
				$transfer_id,
				$this->util->convert_to_mmss( $time_to_migrate )
			)
		);

		return array(
			'success'         => true,
			'time_to_migrate' => $time_to_migrate
		);
	}

	/**
	 * Separate DB Dump from files
	 * 
	 * @since 0.0.7
	 * 
	 * @param array  $files          The files to separate the db dump from
	 * @param string $db_dump_path   The path to the db dump file
	 * 
	 * @return array The db dump file
	 */
	public function seperate_db_from_files( $files, $db_dump_path ) {
		$db_dump_basename = basename( $db_dump_path );
		$db_file = array();
		//Find the database dump file, and remove it from the list.
		foreach( $files as $index => $file ) {
			if ( false !== strpos( $file['path'], $db_dump_basename ) ) {
				$db_file = $file;
				unset( $files[ $index ] );
				break;
			}
		}

		return $db_file;
	}

	/**
	 * Download Extract WordPress
	 * 
	 * Downloads and extracts the wordpress core files into the
	 * directory where the transfered files are located. This method
	 * should merge the wordpress core files with the transfered files,
	 * merging directories when necessary, but not overridding any files.
	 * 
	 * @since 0.0.7
	 * 
	 * @param string $transfer_dir The directory transfered files are located in
	 * @param string $wp_version   The version of wordpress to download
	 * 
	 * @return bool True if the download and extraction was successful, false otherwise
	 */
	public function download_extract_wordpress( $transfer_dir, $wp_version ) {
		// Validate Inputs
		if ( ! is_dir( $transfer_dir ) ) {
			$this->migrate_core->log->add( 'Transfer directory does not exist: ' . $transfer_dir );
			return false; // Transfer directory doesn't exist
		}

		// Compare versions.
		$current_wp_version = get_bloginfo( 'version' );
		if ( version_compare( $current_wp_version, $wp_version, '==' ) ) {
			$this->migrate_core->log->add( 'Current version of WP matches migrated version. No need to download and extract core files.' );
			return true; // Already up to date
		}

		// Download the WordPress core files.
		$wp_download_url = "https://wordpress.org/wordpress-$wp_version.zip";
		$zip_file        = download_url( $wp_download_url );

		if ( is_wp_error( $zip_file ) ) {
			$this->migrate_core->log->add( 'Error downloading WordPress core files: ' . $zip_file->get_error_message() );
			return false; // Error downloading
		}

		// Create a temporary directory for extraction
		$temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('wp_', true);
		if ( ! mkdir( $temp_dir ) && ! is_dir( $temp_dir ) ) {
			unlink( $zip_file ); // Clean up downloaded file
			$this->migrate_core->log->add( 'Error creating temporary directory for extraction.' );
			return false; // Failed to create temporary directory
		}

		// Extract the WordPress core files
		$zip = new ZipArchive();
		if ( $zip->open( $zip_file ) === true ) {
			$zip->extractTo( $temp_dir );
			$zip->close();
		} else {
			unlink( $zip_file ); // Clean up downloaded file
			$this->migrate_core->log->add( 'Error extracting WordPress core files.' );
			return false; // Error extracting
		}

		unlink( $zip_file ); // Clean up downloaded file

		// WordPress files are in the 'wordpress' subdirectory of the temp dir
		$wp_dir = $temp_dir . DIRECTORY_SEPARATOR . 'wordpress';
		
		if ( ! is_dir( $wp_dir ) ) {
			$this->migrate_core->log->add( 'Extracted WordPress directory not found.' );
			return false; // Extracted directory not found
		}

		// Merge WordPress files into the transfer directory
		$directory_iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $wp_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $directory_iterator as $file ) {
			$target_path = $transfer_dir . DIRECTORY_SEPARATOR . $directory_iterator->getSubPathName();
	
			if ( $file->isDir() ) {
				// Create directory if it doesn't exist
				if ( ! is_dir( $target_path ) ) {
					mkdir( $target_path, 0755, true );
				}
			} else {
				// Copy file only if it doesn't already exist
				if ( ! file_exists( $target_path ) ) {
					copy( $file, $target_path );
				}
			}
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$temp_dir,
				RecursiveDirectoryIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		
		foreach ( $iterator as $file ) {
			if ( $file->isFile() || $file->isLink() ) {
				unlink( $file->getPathname() ); // Delete files and symbolic links
			} elseif ( $file->isDir() ) {
				rmdir( $file->getPathname() ); // Remove directories
			}
		}
		
		// Finally, remove the root temporary directory
		rmdir( $temp_dir );

		$this->migrate_core->log->add( 'Downloaded and extracted WordPress core files.' );

		return true;
	}

	/**
	 * Copy Files
	 * 
	 * Copy the files from the transfer directory to the site's root directory.
	 * 
	 * @since 0.0.7
	 * 
	 * @param array  $files        Array of files to copy
	 * @param string $transfer_dir The directory transfered files are located in
	 * 
	 */
	public function copy_files( $files, $transfer_dir ) {
		$this->migrate_core->log->add( 'Copying files from transfer directory to site root directory.' );
		$failed_copies = array();
		foreach( $files as $file ) {
			$relative_path  = str_replace( $transfer_dir, '', $file['path'] );
			$dest_file_path = ABSPATH . $relative_path;
			$this->util->create_dirpath( $dest_file_path );
			if ( file_exists( $file['path'] ) ) {
				copy( $file['path'], $dest_file_path );
			} else {
				$this->migrate_core->log->add( 'File does not exist: ' . $file['path'] );
				$failed_copies[] = $file['path'];
			}
		}

		if ( ! empty( $failed_copies ) ) {
			$this->migrate_core->log->add( 'Failed to copy the following files: ' . json_encode( $failed_copies, JSON_PRETTY_PRINT ) );
			return false;
		}

		$this->migrate_core->log->add( 'Finished Copying Files.' );

		return true;
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
	public function restore_database( $db_dump_filepath, $site_url ) {
		global $wp_filesystem;
		$db_prefix = null;

		$this->migrate_core->log->add( 'Restoring the WordPress database from the dump file: ' . $db_dump_filepath );

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
		$wp_siteurl = $this->util->get_option( 'siteurl' );
		$wp_home    = $this->util->get_option( 'home' );

		// Import the dump file.
		$memory_limit_bumped = Boldgrid_Backup_Admin_Utility::bump_memory_limit( filesize( $db_dump_filepath ) * 10 );
		$this->migrate_core->log->add( 'Memory limit bumped: ' . json_encode( $memory_limit_bumped ) );
		$importer = new Boldgrid_Backup_Admin_Db_Import( $this->migrate_core );
		$status   = $importer->import( $db_dump_filepath );

		$this->migrate_core->log->add( 'Database import status: ' . json_encode( $status ) );

		$new_db_prefix = $this->migrate_core->util->rest_get(
			$site_url,
			'get-db-prefix',
			'db_prefix'
		);

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		$old_db_prefix = $wpdb->get_blog_prefix();

		// Set the database prefix, if supplied/changed.
		if ( ! empty( $new_db_prefix ) && $new_db_prefix !== $old_db_prefix ) {

			// Set the database table prefix.
			$wpdb->set_prefix( $db_prefix );
			$this->update_table_prefix( $new_db_prefix );
		} else {
			$new_db_prefix = $old_db_prefix;
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
		$restored_wp_siteurl = $this->util->get_option( 'siteurl', false, $db );
		$restored_wp_home    = $this->util->get_option( 'home' );
		
		$this->migrate_core->log->add( json_encode( array(
		    'site_url' => $wp_siteurl,
		    'home'     => $wp_home,
		    'restored_wp_siteurl' => $restored_wp_siteurl,
		    'restored_wp_home' => $restored_wp_home,
		), JSON_PRETTY_PRINT ) );

		// If changed, then update the siteurl in the database.
		if ( $restored_wp_siteurl !== $wp_siteurl ) {
			$update_siteurl_success =
				Boldgrid_Backup_Admin_Utility::update_siteurl( array(
					'old_siteurl' => $restored_wp_siteurl,
					'siteurl'     => $wp_siteurl,
				) );

			if ( ! $update_siteurl_success ) {
			    $this->migrate_core->log->add( 'The WordPress siteurl has changed.  There was an issue changing it back.  You will have to fix the siteurl manually in the database, or use an override in your wp-config.php file.' );
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

	public function update_table_prefix( $new_db_prefix ) {
		$wp_config_path = ABSPATH . 'wp-config.php';
		$wp_config      = file_get_contents( $wp_config_path );

		// Regex to capture something like:
		// $table_prefix  = 'wp_';
		// We want to replace only the part between the quotes.
		$pattern     = '/(\$table_prefix\s*=\s*[\'"])([^\'"]+)([\'"]\s*;)/';
		$replacement = '${1}' . $new_db_prefix . '${3}';

		$wp_config = preg_replace( $pattern, $replacement, $wp_config );
		
		file_put_contents( $wp_config_path, $wp_config );

		return true;
	}
}