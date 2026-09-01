<?php
/**
 * File: class-boldgrid-backup-admin-migrate-restore.php
 * 
 * The class for the handling migration of transfered sites.
 * 
 * @link https://www.boldgrid.com
 * @since 1.17.0
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate_Restore
 * 
 * The main class for restoring the transferred files & DB.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate_Restore {
	/**
	 * Boldgrid_Transfer Core
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 1.17.0
	 */
	public $migrate_core;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 1.17.0
	 */
	public $util;

	/**
	 * Transfer Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $transfer_option_name;

	/**
	 * File List Option Name
	 * 
	 * @var string
	 * 
	 * @since 1.17.0
	 */
	public $lists_option_name;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Transfer $core
	 * 
	 * @since 1.17.0
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core   = $migrate_core;
		$this->util           = $this->migrate_core->util;

		$this->transfer_option_name = $this->migrate_core->configs['option_names']['transfers'];
		$this->lists_option_name    = $this->migrate_core->configs['option_names']['file_lists'];
	}

	/**
	 * Restore Site
	 * 
	 * Restores a Site that has been transferred.
	 *
	 * @param array $transfer
	 * @param string $transfer_id
	 * 
	 * @return array The result of the restore operation
	 * 
	 * @since 1.17.0
	 */
	public function restore_site( $transfer, $transfer_id ) {
		$migrate_start_time = microtime( true );
		$transfer_dir       = $this->util->get_transfer_dir();
		$source_dir         = $this->util->url_to_safe_directory_name( $transfer['source_site_url'] );
		$transfer_dir       = $transfer_dir . '/' . $source_dir . '/' . $transfer_id . '/';

		$this->migrate_core->log->add( 'Starting restoration for transfer ID ' . $transfer_id );
		$this->util->update_transfer_prop( $transfer_id, 'status', 'restoring' );
		$this->util->update_transfer_prop( $transfer_id, 'restore_start_time', $migrate_start_time );

		// 1. Download and extract the wordpress core files for the version specified in the transfer.
		if ( ! $this->download_extract_wordpress( $transfer_dir, $transfer['source_wp_version'] ) ) {
			$this->migrate_core->log->add( 'Failed to download and extract WordPress core files.' );
			$this->util->update_transfer_prop( $transfer_id, 'status', 'failed' );
			$this->util->update_transfer_prop(
				$transfer_id,
				'failed_message',
				esc_html__( 'Failed to download and extract WordPress core files.', 'boldgrid-backup' )
			);
			return false;
		}

		$this->util->update_transfer_prop( $transfer_id, 'status', 'restoring-files' );

		// 2. Get an array of files to copy.
		$files = $this->util->get_files_in_dir( $transfer_dir );

		// 3. Separate the database dump file from the files.
		$db_file = $this->seperate_db_from_files( $files, $transfer['db_dump_info']['file'] );

		// 4. Copy the files from the transfer directory to the site's root directory.
		if ( ! $this->copy_files( $files, $transfer_id, $transfer_dir ) ) {
			$this->migrate_core->log->add( 'Failed to copy files.' );
			return array(
				'success' => false,
				'error'   => 'Failed to copy files.'
			);
		}
		
		unset( $files );

		$this->migrate_core->log->add( '$db_file: ' . json_encode( $db_file, JSON_PRETTY_PRINT ) );

		$this->util->update_transfer_prop( $transfer_id, 'status', 'restoring-db' );

		// 5. Export the options to a file.
		$options_file = $this->export_options( $transfer_dir );

		// 6. Restore the WordPress database from the dump file.
		if ( ! $this->restore_database( $db_file['path'], $transfer ) ) {
			$this->migrate_core->log->add( 'Failed to restore Database.' );
			$this->util->update_transfer_prop( $transfer_id, 'status', 'failed' );
			$this->util->update_transfer_prop(
				$transfer_id,
				'failed_message',
				esc_html__( 'Failed to restore database.', 'boldgrid-backup' )
			);
			return false;
		}

		// 7. Restore the options that were exported before the migration.
		$this->restore_options( $options_file );

		$migration_end_time = microtime( true );
		$time_to_restore    = $migration_end_time - $migrate_start_time;
		$this->migrate_core->util->update_transfer_prop( $transfer_id, 'status', 'restore-completed' );
		$this->migrate_core->backup_core->cron->entry_delete_contains( 'direct-transfer.php' );
		$this->migrate_core->log->add( 
			sprintf(
				'Completed migration for transfer ID %1$s in %2$s.',
				$transfer_id,
				$this->util->format_time( $time_to_restore )
			)
		);

		$this->migrate_core->log->add( $this->migrate_core->util->get_transfer_report( $transfer_id ) );

		return array(
			'success'         => true,
			'time_to_restore' => $time_to_restore
		);
	}

	/**
	 * Separate DB Dump from files
	 *
	 * @param array  $files        The files to separate the db dump from
	 * @param string $db_dump_path The path to the db dump file
	 * 
	 * @return array The db dump file
	 * 
	 * @since 1.17.0
	 */
	public function seperate_db_from_files( &$files, $db_dump_path ) {
		$db_dump_basename = basename( $db_dump_path );
		$db_file          = array();
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
	 * @param string $transfer_dir The directory transfered files are located in
	 * @param string $wp_version   The version of wordpress to download
	 *
	 * @return bool True if the download and extraction was successful, false otherwise
	 *
	 * @since 1.17.0
	 */
	public function download_extract_wordpress( $transfer_dir, $wp_version ) {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;

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
		if ( ! $wp_filesystem->mkdir( $temp_dir ) && ! is_dir( $temp_dir ) ) {
			wp_delete_file( $zip_file ); // Clean up downloaded file
			$this->migrate_core->log->add( 'Error creating temporary directory for extraction.' );
			return false; // Failed to create temporary directory
		}

		// Extract the WordPress core files
		$zip = new ZipArchive();
		if ( $zip->open( $zip_file ) === true ) {
			$zip->extractTo( $temp_dir );
			$zip->close();
		} else {
			wp_delete_file( $zip_file ); // Clean up downloaded file
			$this->migrate_core->log->add( 'Error extracting WordPress core files.' );
			return false; // Error extracting
		}

		wp_delete_file( $zip_file ); // Clean up downloaded file

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
			$target_path = $transfer_dir . $directory_iterator->getSubPathName();
	
			if ( $file->isDir() ) {
				// Create directory if it doesn't exist
				if ( ! is_dir( $target_path ) ) {
					$wp_filesystem->mkdir( $target_path, 0755 );
				}
			} else {
				// If the file is wp-config.php, or wp-config-sample.php, skip it
				if ( 'wp-config.php' === basename( $file ) || 'wp-config-sample.php' === basename( $file ) ) {
					continue;
				}
				$wp_filesystem->copy( $file, $target_path, true );
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
				wp_delete_file( $file->getPathname() ); // Delete files and symbolic links
			} elseif ( $file->isDir() ) {
				$wp_filesystem->rmdir( $file->getPathname() ); // Remove directories
			}
		}
		
		// Finally, remove the root temporary directory
		$wp_filesystem->rmdir( $temp_dir );

		$this->migrate_core->log->add( 'Downloaded and extracted WordPress core files.' );

		return true;
	}

	/**
	 * Copy Files
	 * 
	 * Copy the files from the transfer directory to the site's root directory.
	 * 
	 * @since 1.17.0
	 * 
	 * @param array  $files        Array of files to copy
	 * @param string $transfer_dir The directory transfered files are located in
	 * 
	 */
	public function copy_files( $files, $transfer_id, $transfer_dir ) {
		$this->migrate_core->log->add( 'Copying files from transfer directory to site root directory.' );
		$failed_copies     = array();
		$total_files_count = count( $files );
		$count = 0;
		$this->util->update_transfer_prop(
			$transfer_id,
			'copy_files_stats',
			array(
				'total_files'  => $total_files_count,
				'files_copied' => $count,
			)
		);
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

			$count++;

			if ( 0 === $count % 50 ) {
				$this->util->update_transfer_heartbeat();
				$this->util->update_transfer_prop(
					$transfer_id,
					'copy_files_stats',
					array(
						'total_files'  => $total_files_count,
						'files_copied' => $count,
					)
				);
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
	 * @since 1.17.0
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
	public function restore_database( $db_dump_filepath, $transfer ) {
		global $wp_filesystem;
		$db_prefix = null;

		$site_url = $transfer['source_site_url'];

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

		$new_db_prefix = $this->migrate_core->util->rest_get(
			$transfer,
			'get-db-prefix',
			'db_prefix'
		);

		// Import the dump file.
		$memory_limit_bumped = Boldgrid_Backup_Admin_Utility::bump_memory_limit( filesize( $db_dump_filepath ) * 10 );
		$this->migrate_core->log->add( 'Memory limit bumped: ' . json_encode( $memory_limit_bumped ) );
		$importer = new Boldgrid_Backup_Admin_Db_Import( $this->migrate_core );
		$status   = $importer->import( $db_dump_filepath );

		$this->migrate_core->log->add( 'Database import status: ' . json_encode( $status ) );

		$this->migrate_core->log->add( 'New database prefix: ' . json_encode( $new_db_prefix ) );

		// If the import failed, return false.
		if ( ! $status ) {
			$this->migrate_core->log->add( 'Failed to import the database dump file.' );
			return false;
		}

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		$old_db_prefix = $wpdb->get_blog_prefix();

		// Set the database prefix, if supplied/changed.
		if ( ! empty( $new_db_prefix ) && $new_db_prefix !== $old_db_prefix ) {

			// Set the database table prefix.
			$wpdb->set_prefix( $new_db_prefix );
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
		$restored_wp_siteurl = get_option( 'siteurl' );
		$restored_wp_home    = get_option( 'home' );
		
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

	/**
	 * Export Options
	 * 
	 * Export all the options in the
	 * $this->migrate_core->configs['option_names'] array
	 * to the backup directory so they can be restored after the migration.
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $transfer_dir The directory transfered files are located in
	 */
	public function export_options( $transfer_dir ) {
		$options             = $this->migrate_core->configs['option_names'];
		$backup_options_file = $transfer_dir . '/direct-transfer-options.json';

		$this->migrate_core->log->add( 'Exporting options to: ' . $backup_options_file );

		$options_array = array();
		foreach( $options as $option_name ) {
			$option_value = $this->migrate_core->util->get_option( $option_name );
			$options_array[ $option_name ] = $option_value;
		}

		file_put_contents( $backup_options_file, json_encode( $options_array ) );

		return $backup_options_file;
	}

	/**
	 * Restore Options
	 * 
	 * Restore the options that were exported before the migration.
	 * 
	 * @since 1.17.0
	 * 
	 * @param string $options_file The file containing the options to restore
	 */
	public function restore_options( $options_file ) {
		$this->migrate_core->log->add( 'Restoring options from: ' . $options_file );

		$options_array = json_decode( file_get_contents( $options_file ), true );

		foreach( $options_array as $option_name => $option_value ) {
			update_option( $option_name, $option_value );
		}

		$this->migrate_core->log->add( 'Finished restoring options.' );
		wp_delete_file( $options_file );
	}

	/**
	 * Update Table Prefix
	 *
	 * @param string $new_db_prefix The new database prefix
	 * 
	 * @return bool True if the update was successful, false otherwise
	 * 
	 * @since 1.17.0
	 */
	public function update_table_prefix( $new_db_prefix ) {
		$wp_config_path = ABSPATH . 'wp-config.php';
		$wp_config      = file_get_contents( $wp_config_path );

		// Regex to capture something like:
		// $table_prefix  = 'wp_';
		// We want to replace only the part between the quotes.
		$pattern     = '/(\$table_prefix\s*=\s*[\'"])([^\'"]+)([\'"]\s*;)/';
		$replacement = '${1}' . $new_db_prefix . '${3}';

		$wp_config = preg_replace( $pattern, $replacement, $wp_config );
		
		$result = file_put_contents( $wp_config_path, $wp_config );

		return $result ? true : false;
	}
}
