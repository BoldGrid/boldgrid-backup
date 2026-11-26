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
		$transfer_dir       = $this->util->get_transfer_staging_dir( $transfer );
		if ( ! $transfer_dir ) {
			$this->util->update_transfer_prop( $transfer_id, 'status', 'failed' );
			$this->util->update_transfer_prop(
				$transfer_id,
				'failed_message',
				esc_html__( 'The transfer directory could not be opened.', 'boldgrid-backup' )
			);
			return false;
		}
		$transfer_dir = trailingslashit( $transfer_dir );

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
		$db_dump_name = isset( $transfer['db_dump_info']['file'] ) ? $transfer['db_dump_info']['file'] : '';
		$db_file      = $this->seperate_db_from_files( $files, $db_dump_name );
		if ( empty( $db_file['path'] ) ) {
			$this->migrate_core->log->add( 'Failed to restore Database.' );
			$this->util->update_transfer_prop( $transfer_id, 'status', 'failed' );
			$this->util->update_transfer_prop(
				$transfer_id,
				'failed_message',
				esc_html__( 'Failed to restore database.', 'boldgrid-backup' )
			);
			return false;
		}

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
			$source_path    = $this->util->resolve_listed_path( isset( $file['path'] ) ? $file['path'] : '' );
			$dest_file_path = false;
			if ( $source_path && $this->util->existing_path_is_inside( $transfer_dir, $source_path ) ) {
				$source_real    = wp_normalize_path( realpath( $source_path ) );
				$transfer_real  = wp_normalize_path( realpath( $transfer_dir ) );
				$relative_path  = ltrim( substr( $source_real, strlen( $transfer_real ) ), '/' );
				$dest_file_path = $this->util->prepare_transfer_file( untrailingslashit( ABSPATH ), $relative_path );
			}

			if ( false === $dest_file_path || ! copy( $source_path, $dest_file_path ) ) {
				$this->migrate_core->log->add( 'File could not be copied.' );
				$failed_copies[] = $source_path;
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
			$this->util->update_transfer_prop( $transfer_id, 'status', 'failed' );
			$this->util->update_transfer_prop(
				$transfer_id,
				'failed_message',
				esc_html__( 'Failed to copy files.', 'boldgrid-backup' )
			);
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

		$staging_dir      = $this->util->get_transfer_staging_dir( $transfer );
		$db_dump_filepath = $this->util->resolve_listed_path( $db_dump_filepath );
		if ( empty( $db_dump_filepath ) || ! $this->util->existing_path_is_inside( $staging_dir, $db_dump_filepath ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The database dump file was not found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			$this->migrate_core->log->add( 'The database dump file was not found.' );

			return false;
		}

		$this->migrate_core->log->add( 'Restoring the WordPress database from the dump file.' );

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

		if ( ! is_string( $new_db_prefix ) ) {
			$this->migrate_core->log->add( 'Database prefix was not read.' );
			return false;
		}

		global $wpdb;

		$old_db_prefix = $wpdb->get_blog_prefix();

		/*
		 * Confirm a changed prefix can be stored before importing.
		 * A rejected prefix must leave the destination database unchanged.
		 */
		if ( $new_db_prefix !== $old_db_prefix && ! $this->prefix_change_is_writable( $new_db_prefix ) ) {
			return false;
		}

		// Import the dump file.
		$memory_limit_bumped = Boldgrid_Backup_Admin_Utility::bump_memory_limit( filesize( $db_dump_filepath ) * 10 );
		$this->migrate_core->log->add( 'Memory limit bumped: ' . json_encode( $memory_limit_bumped ) );
		$importer = new Boldgrid_Backup_Admin_Db_Import( $this->migrate_core );
		$status   = $importer->import( $db_dump_filepath );

		$this->migrate_core->log->add( 'Database import status: ' . json_encode( $status ) );

		// If the import failed, return false.
		if ( ! $status ) {
			$this->migrate_core->log->add( 'Failed to import the database dump file.' );
			return false;
		}

		if ( $new_db_prefix !== $old_db_prefix ) {
			/*
			 * Write wp-config.php first. Switching $wpdb before that write
			 * succeeds would store a failure on the imported tables, and the
			 * next request would still read the destination record.
			 */
			if ( ! $this->update_table_prefix( $new_db_prefix ) ) {
				$this->migrate_core->log->add( 'Database prefix was not updated.' );
				return false;
			}

			$prefix_set = $wpdb->set_prefix( $new_db_prefix );
			if ( is_wp_error( $prefix_set ) ) {
				$this->update_table_prefix( $old_db_prefix );
				$this->migrate_core->log->add( 'Database prefix was not updated.' );
				return false;
			}
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
	public function update_table_prefix( $new_db_prefix, $wp_config_path = '' ) {
		if ( ! $this->util->is_valid_db_prefix( $new_db_prefix ) ) {
			return false;
		}

		if ( '' === $wp_config_path ) {
			$wp_config_path = ABSPATH . 'wp-config.php';
		}

		$wp_config = file_get_contents( $wp_config_path );
		if ( ! is_string( $wp_config ) ) {
			return false;
		}

		$span = $this->table_prefix_assignment_span( $wp_config );
		if ( false === $span ) {
			return false;
		}

		$escaped     = str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $new_db_prefix );
		$replacement = '$table_prefix = \'' . $escaped . '\';';
		$updated     = substr( $wp_config, 0, $span['start'] ) . $replacement . substr( $wp_config, $span['end'] );

		$verified = $this->table_prefix_assignment_span( $updated );
		if ( false === $verified ) {
			return false;
		}

		$assignment = substr( $updated, $verified['start'], $verified['end'] - $verified['start'] );
		if ( $assignment !== $replacement ) {
			return false;
		}

		$result = file_put_contents( $wp_config_path, $updated );

		return false !== $result;
	}

	/**
	 * Whether a changed prefix can be written before the database is imported.
	 *
	 * @since 1.17.5
	 *
	 * @param string $new_db_prefix Prefix from the source site.
	 * @return bool
	 */
	private function prefix_change_is_writable( $new_db_prefix ) {
		if ( ! $this->util->is_valid_db_prefix( $new_db_prefix ) ) {
			$this->migrate_core->log->add( 'Rejected database prefix.' );
			return false;
		}

		$wp_config_path = ABSPATH . 'wp-config.php';
		$wp_config      = is_readable( $wp_config_path ) ? file_get_contents( $wp_config_path ) : false;
		$can_write      = is_string( $wp_config ) && is_writable( $wp_config_path );
		if ( ! $can_write || false === $this->table_prefix_assignment_span( $wp_config ) ) {
			$this->migrate_core->log->add( 'Database prefix was not updated.' );
			return false;
		}

		return true;
	}

	/**
	 * Byte span of the only quoted $table_prefix assignment.
	 *
	 * Every executable write counts, including a computed value and a
	 * $GLOBALS['table_prefix'] write. A later write would stay in force,
	 * so the file is left unchanged unless there is exactly one quoted
	 * $table_prefix assignment.
	 *
	 * @since 1.17.5
	 *
	 * @param string $wp_config File contents.
	 * @return array|false Start and end offsets, or false.
	 */
	private function table_prefix_assignment_span( $wp_config ) {
		$tokens = token_get_all( $wp_config );
		$count  = count( $tokens );
		$offset = 0;
		$spans  = array();
		$writes = 0;

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			$text  = is_array( $token ) ? $token[1] : $token;

			$target_end = $this->prefix_write_target_end( $tokens, $i );
			if ( false !== $target_end && $this->token_is_prefix_write( $tokens, $target_end ) ) {
				$writes++;
				if ( $target_end === $i ) {
					$end = $this->quoted_prefix_assignment_end( $tokens, $i, $offset );
					if ( is_int( $end ) ) {
						$spans[] = array(
							'start' => $offset,
							'end'   => $end,
						);
					}
				}
			}

			$offset += strlen( $text );
		}

		if ( 1 !== $writes || 1 !== count( $spans ) ) {
			return false;
		}

		return $spans[0];
	}

	/**
	 * Last token of a $table_prefix or $GLOBALS['table_prefix'] target.
	 *
	 * @since 1.17.5
	 *
	 * @param array $tokens Tokens from token_get_all().
	 * @param int   $index  Index of a variable token.
	 * @return int|false
	 */
	private function prefix_write_target_end( $tokens, $index ) {
		$token = $tokens[ $index ];
		if ( ! is_array( $token ) || T_VARIABLE !== $token[0] ) {
			return false;
		}

		if ( '$table_prefix' === $token[1] ) {
			return $index;
		}

		if ( '$GLOBALS' !== $token[1] ) {
			return false;
		}

		$open = $this->next_code_token_index( $tokens, $index );
		if ( ! $this->token_is_char( $tokens, $open, '[' ) ) {
			return false;
		}

		$key = $this->next_code_token_index( $tokens, $open );
		if ( ! $this->token_is_prefix_key( $tokens, $key ) ) {
			return false;
		}

		$close = $this->next_code_token_index( $tokens, $key );
		if ( ! $this->token_is_char( $tokens, $close, ']' ) ) {
			return false;
		}

		return $close;
	}

	/**
	 * Whether the token is the table_prefix array key.
	 *
	 * @since 1.17.5
	 *
	 * @param array $tokens Tokens from token_get_all().
	 * @param int   $index  Token index.
	 * @return bool
	 */
	private function token_is_prefix_key( $tokens, $index ) {
		if ( ! isset( $tokens[ $index ] ) || ! is_array( $tokens[ $index ] ) ) {
			return false;
		}

		if ( T_CONSTANT_ENCAPSED_STRING !== $tokens[ $index ][0] ) {
			return false;
		}

		return 'table_prefix' === substr( $tokens[ $index ][1], 1, -1 );
	}

	/**
	 * Whether the token is one character.
	 *
	 * @since 1.17.5
	 *
	 * @param array  $tokens Tokens from token_get_all().
	 * @param int    $index  Token index.
	 * @param string $char   Expected character.
	 * @return bool
	 */
	private function token_is_char( $tokens, $index, $char ) {
		return isset( $tokens[ $index ] ) && $char === $tokens[ $index ];
	}

	/**
	 * Next token that is not whitespace or a comment.
	 *
	 * @since 1.17.5
	 *
	 * @param array $tokens Tokens from token_get_all().
	 * @param int   $index  Current token index.
	 * @return int
	 */
	private function next_code_token_index( $tokens, $index ) {
		$count = count( $tokens );
		$next  = $index + 1;
		$skip  = array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT );

		while ( $next < $count && is_array( $tokens[ $next ] ) && in_array( $tokens[ $next ][0], $skip, true ) ) {
			$next++;
		}

		return $next;
	}

	/**
	 * Whether the next code token writes to the variable.
	 *
	 * @since 1.17.5
	 *
	 * @param array $tokens Tokens from token_get_all().
	 * @param int   $index  Last token of the prefix target.
	 * @return bool
	 */
	private function token_is_prefix_write( $tokens, $index ) {
		$count = count( $tokens );
		$next  = $this->next_code_token_index( $tokens, $index );

		if ( $next >= $count ) {
			return false;
		}

		$token = $tokens[ $next ];
		if ( '=' === $token ) {
			return true;
		}

		if ( ! is_array( $token ) ) {
			return false;
		}

		$writes = array(
			T_PLUS_EQUAL,
			T_MINUS_EQUAL,
			T_MUL_EQUAL,
			T_DIV_EQUAL,
			T_CONCAT_EQUAL,
			T_MOD_EQUAL,
			T_AND_EQUAL,
			T_OR_EQUAL,
			T_XOR_EQUAL,
			T_SL_EQUAL,
			T_SR_EQUAL,
			T_POW_EQUAL,
			T_COALESCE_EQUAL,
		);

		return in_array( $token[0], $writes, true );
	}

	/**
	 * Offset just after a quoted assignment that starts at a variable token.
	 *
	 * @since 1.17.5
	 *
	 * @param array $tokens Tokens from token_get_all().
	 * @param int   $index  Index of the $table_prefix variable.
	 * @param int   $offset Byte offset of that variable.
	 * @return int|false
	 */
	private function quoted_prefix_assignment_end( $tokens, $index, $offset ) {
		$count  = count( $tokens );
		$cursor = $offset + strlen( $tokens[ $index ][1] );
		$index++;

		$index = $this->skip_token_whitespace( $tokens, $index, $cursor );
		if ( $index >= $count || '=' !== $tokens[ $index ] ) {
			return false;
		}
		$cursor++;
		$index++;

		$index = $this->skip_token_whitespace( $tokens, $index, $cursor );
		if ( $index >= $count || ! is_array( $tokens[ $index ] ) || T_CONSTANT_ENCAPSED_STRING !== $tokens[ $index ][0] ) {
			return false;
		}
		$cursor += strlen( $tokens[ $index ][1] );
		$index++;

		$index = $this->skip_token_whitespace( $tokens, $index, $cursor );
		if ( $index >= $count || ';' !== $tokens[ $index ] ) {
			return false;
		}

		return $cursor + 1;
	}

	/**
	 * Advance past whitespace tokens.
	 *
	 * @since 1.17.5
	 *
	 * @param array $tokens Tokens from token_get_all().
	 * @param int   $index  Current token index.
	 * @param int   $cursor Byte offset, passed by reference.
	 * @return int Next token index.
	 */
	private function skip_token_whitespace( $tokens, $index, &$cursor ) {
		$count = count( $tokens );

		while ( $index < $count && is_array( $tokens[ $index ] ) && T_WHITESPACE === $tokens[ $index ][0] ) {
			$cursor += strlen( $tokens[ $index ][1] );
			$index++;
		}

		return $index;
	}
}
