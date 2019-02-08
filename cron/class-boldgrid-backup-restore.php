<?php
/**
 * File: class-boldgrid-backup-restore.php
 *
 * Emergency restoration script.  This script is used when there is a severe issue with the site
 * which requires immediate restoration from the latest backup archive.
 *
 * @link https://www.boldgrid.com
 * @since 1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput,WordPress.WP.AlternativeFunctions
 */

/**
 * Class: BoldGrid_Backup_Restore.
 *
 * @since 1.8.0
 */
class BoldGrid_Backup_Restore {
	/**
	 * Archive information.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @var array
	 */
	private $info = [];

	/**
	 * Errors.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Boldgrid_Backup_Url_Helper object.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @var Boldgrid_Backup_Url_Helper
	 */
	private $url_helper;

	/**
	 * Constructor.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {
		require __DIR__ . '/class-boldgrid-backup-url-helper.php';
		$this->url_helper = new Boldgrid_Backup_Url_Helper();
	}

	/**
	 * Run the restoration process.
	 *
	 * @since 1.8.0
	 */
	public function run() {
		echo 'Starting emergency restoration process...' . PHP_EOL;

		$this->get_restore_info();

		// If there were any errors, then print them and exit.
		if ( ! $this->validate() ) {
			echo implode( PHP_EOL, $this->errors ) . PHP_EOL;
			exit( 1 );
		}

		echo 'Attempting to restore "' . $this->info['siteurl'] . '" from backup archive file "' .
			$this->info['filepath'] . '"...' . PHP_EOL;

		echo ( $this->restore() ? 'Success.' : 'Error: Could not perform restoration.' ) . PHP_EOL;
	}

	/**
	 * Retrieve validated restoration information.
	 *
	 * If validation failes, then errors are saved in the $errors class property.
	 *
	 * @since 1.8.0
	 * @access private
	 */
	private function get_restore_info() {
		if ( ini_get( 'safe_mode' ) ) {
			$this->errors[] = 'Error: Cannot continue in PHP safe mode.';
			return;
		}

		// Get the backup results file.
		$results_file_path = __DIR__ . '/restore-info.json';

		if ( ! file_exists( $results_file_path ) ) {
			$this->errors[] = 'Error: No backup results file ("' . $results_file_path . '").';
			return;
		}

		$results = json_decode( file_get_contents( $results_file_path ), true );

		// Validate results file content.
		if ( empty( $results ) ) {
			$this->errors[] = 'Error: No backup results found.';
			return;
		}

		if ( empty( $results['filepath'] ) ) {
			$this->errors[] = 'Error: Unknown backup archive file path.';
			return;
		}

		if ( empty( $results['file_md5'] ) ) {
			$this->errors[] = 'Error: Missing archive file checksum.';
			return;
		}

		// Check if archive exists, and matches checksum.
		if ( ! file_exists( $results['filepath'] ) ) {
			$this->errors[] = 'Error: No backup archive file ("' . $results['filepath'] . '").';
			return;
		}

		if ( md5_file( $results['filepath'] ) !== $results['file_md5'] ) {
			$this->errors[] = 'Error: Failed archive file checksum.';
			return;
		}

		// Get the archive log file and merge info.
		$archive_log_filepath = preg_replace( '/\.zip$/', '.log', $results['filepath'] );

		if ( ! file_exists( $archive_log_filepath ) ) {
			$this->errors[] = 'Error: Backup archive log file "' . $archive_log_filepath .
				'" does not exist.';
			return;
		}

		$this->info = json_decode( file_get_contents( $archive_log_filepath ), true );

		// Validate results file content.
		if ( empty( $this->info ) ) {
			$this->errors[] = 'Error: No backup information found in the log file "' .
				$archive_log_filepath . '".';
			return;
		}

		// Merge info and results arrays.
		$this->info = array_merge( $this->info, $results );

		// Validate more data.
		if ( empty( $this->info['ABSPATH'] ) ) {
			$this->errors[] = 'Error: Unknown ABSPATH.';
			return;
		}

		if ( ! is_dir( $this->info['ABSPATH'] ) ) {
			$this->errors[] = 'Error: ABSPATH directory "' . $this->info['ABSPATH'] .
				'" does not exist or is not a directory.';
		}

		if ( ! is_writable( $this->info['ABSPATH'] ) ) {
			$this->errors[] = 'Error: ABSPATH directory "' . $this->info['ABSPATH'] .
				'" is not writable.';
		}

		if ( empty( $this->info['siteurl'] ) ) {
			$this->errors[] = 'Error: Unknown siteurl.';
		}

		if ( empty( $this->info['cron_secret'] ) ) {
			$this->errors[] = 'Error: Unknown cron_secret.';
		}

		if ( empty( $this->info['db_filename'] ) ) {
			$this->errors[] = 'Error: Unknown database dump filename.';
		} else {
			$this->info['db_filepath'] = $this->info['ABSPATH'] . $this->info['db_filename'];
		}
	}

	/**
	 * Validate.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @return bool
	 */
	private function validate() {
		$this->is_cli();
		$this->have_execution_functions();

		return empty( $this->errors );
	}

	/**
	 * Is this process running from the command line.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Cron_Helper::is_cli()
	 */
	private function is_cli() {
		require __DIR__ . '/class-boldgrid-backup-cron-helper.php';

		if ( ! Boldgrid_Backup_Cron_Helper::is_cli() ) {
			$this->errors[] = 'Error: This process must run from the CLI.';
		}
	}

	/**
	 * Are there available execution functions.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Cli::get_execution_functions()
	 */
	private function have_execution_functions() {
		require dirname( __DIR__ ) . '/admin/class-boldgrid-backup-admin-cli.php';

		$exec_functions = Boldgrid_Backup_Admin_Cli::get_execution_functions();

		if ( empty( $exec_functions ) ) {
			$this->errors[] = 'Error: No available PHP executable functions.';
		}
	}

	/**
	 * Is the site reachable.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @return bool;
	 */
	private function is_siteurl_reachable() {
		return false !== $this->url_helper->call_url( $this->info['siteurl'] );
	}

	/**
	 * Ensure that archive file destinations are writable.
	 *
	 * @since 1.8.0
	 * @access private
	 */
	public function set_writable_permissions() {
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive();

			if ( $zip->open( $this->info['filepath'] ) ) {
				for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$data = $zip->statIndex( $i );

					if ( empty( $data['name'] ) ) {
						continue;
					}

					chmod( $this->info['ABSPATH'] . $data['name'], 0644 );
				}
			}
		}
	}

	/**
	 * Set the PHP timeout limit to at least 15 minutes.
	 *
	 * Various places within this class use to set the timeout limit to 300 seconds. This timeout
	 * limit has been increased to 900 seconds and moved into its own method.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @param int $time_limit Limit in seconds.
	 */
	private function set_time_limit( $time_limit = 900 ) {
		$max_execution_time = ini_get( 'max_execution_time' );
		set_time_limit( $max_execution_time > $time_limit ? $max_execution_time : $time_limit );
	}

	/**
	 * Get database config from wp-config.php file.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @return bool
	 */
	private function get_db_config() {
		$wpconfig = file_get_contents( $this->info['ABSPATH'] . 'wp-config.php' );

		if ( $wpconfig ) {
			preg_match_all( '/define\(.+DB_(NAME|USER|PASSWORD|HOST).+\);/', $wpconfig, $matches1 );
			foreach ( $matches1[0] as $match1 ) {
				preg_match_all( '/\'(.+?)\'/', $match1, $matches2 );
				if ( ! empty( $matches2[1] ) ) {
						define( $matches2[1][0], $matches2[1][1] );
				}
			}
		}

		return defined( 'DB_NAME' ) && defined( 'DB_USER' ) && defined( 'DB_PASSWORD' ) && defined( 'DB_HOST' );
	}

	/**
	 * Perform restoration.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Cli::call_command()
	 * @see self::restore_files()
	 * @see self::restore_database()
	 *
	 * @return bool;
	 */
	private function restore() {
		$is_siteurl_reachable = 0&&$this->is_siteurl_reachable();
		$restore_cmd          = ! empty( $this->info['restore_cmd'] ) ?
			$this->info['restore_cmd'] : null;

		if ( $is_siteurl_reachable && $restore_cmd ) {
			// Get environment information.
			$env_info = json_decode(
				$this->url_helper->call_url(
					$this->info['siteurl'] . '/wp-content/plugins/boldgrid-backup/cron/env-info.php'
				),
				true
			);

			// Call the normal restore command.
			echo 'Using URL address restoration process...' . PHP_EOL .
				Boldgrid_Backup_Admin_Cli::call_command( $restore_cmd, $success, $return_var ) .
				PHP_EOL;
		} else {
			// Start the standalone restoration process.
			echo 'Cannot reach the site URL; using standalone restoration process...' . PHP_EOL;

			ignore_user_abort( true );
			$this->set_time_limit();
			$success = $this->restore_files() && $this->restore_database();
		}

		return $success;
	}

	/**
	 * Perform restoration of files.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Cli::call_command()
	 * @see ZipArchive::open()
	 * @see ZipArchive::extractTo()
	 * @see PclZip::extract()
	 *
	 * @return bool
	 */
	private function restore_files() {
		$success = false;

		switch ( true ) {
			case false://class_exists( 'ZipArchive' ):
				echo 'Attempting file restoration using PHP ZipArchive...' . PHP_EOL;
				$archive = new ZipArchive();
				if ( true === $archive->open( $this->info['filepath'] ) ) {
					$success = $archive->extractTo( $this->info['ABSPATH'] );
				}
				break;

			case false://file_exists( $this->info['ABSPATH'] . 'wp-admin/includes/class-pclzip.php' ):
				echo 'Attempting file restoration using PHP PCLZip...' . PHP_EOL;
				require $this->info['ABSPATH'] . 'wp-admin/includes/class-pclzip.php';
				$archive = new PclZip( $this->info['filepath'] );
				$result  = $archive->extract( $this->info['ABSPATH'] );
				$success = is_array( $result ) && ! empty( $result );
				break;

			case Boldgrid_Backup_Admin_Cli::call_command( 'unzip', $success, $return_var ) || $success || 0 === $return_var:
				echo 'Attempting file restoration using unzip (CLI)...' . PHP_EOL;
				// Assuming Linux.
				$cmd = 'cd ' . $this->info['ABSPATH'] . ';unzip -oqq ' .
					$this->info['filepath'] . ';for item in $(unzip -Z1 ' . $this->info['filepath'] .
					' | sed -e "/^\.\/$/d" -e "s~/$~~"); do test -d "$i" && chmod 755 "$i";test -f "$i" && chmod 644 "$i"; done';

				Boldgrid_Backup_Admin_Cli::call_command(
					$cmd,
					$success,
					$return_var
				);
				break;

			default:
				echo 'Error: Could not extract files; ZipArchive, PCLZip, and unzip (CLI) unavailable.' .
					PHP_EOL;
				break;
		}

		echo ( $success ? 'Success.' : 'Failed.' ) . PHP_EOL;

		return $success;
	}

	/**
	 * Perform restoration of database.
	 *
	 * @since 1.8.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Db_Import::import()
	 * @see Boldgrid_Backup_Admin_Cli::call_command()
	 *
	 * @return bool;
	 */
	private function restore_database() {
		if ( ! file_exists( $this->info['db_filepath'] ) ) {
			$this->errors[] = 'Error: Database dump file "' . $this->info['db_filepath'] .
				'" does not exist.';
			$success        = false;
		} else {
			$success = $this->get_db_config();
		}

		switch ( true ) {
			case ! $success:
				echo 'Error: Could not get database credentials from "' .
					$this->info['ABSPATH'] . 'wp-config.php".' . PHP_EOL;
				break;

			case false://class_exists( 'PDO' ):
				echo 'Attempting to restore database using PHP PDO...' . PHP_EOL;
				require dirname( __DIR__ ) . '/admin/class-boldgrid-backup-admin-db-import.php';
				$importer = new Boldgrid_Backup_Admin_Db_Import();
				$success  = $importer->import( $this->info['ABSPATH'] . $this->info['db_filename'] );

				if ( ! $success ) {
					echo 'Error: Could not import database (using PDO).' . PHP_EOL;
				}
				break;

			case Boldgrid_Backup_Admin_Cli::call_command( 'mysql -V', $success, $return_var ) || $success || 0 === $return_var:
				echo 'Attempting to restore database using mysql client (CLI)...' . PHP_EOL;
				$cmd = 'mysql -h ' . DB_HOST . ' -p' . DB_PASSWORD . ' -u ' . DB_USER . ' ' .
					DB_NAME . ' < "' . $this->info['db_filepath'] . '"';

				Boldgrid_Backup_Admin_Cli::call_command( $cmd, $success, $return_var );

				if ( ! $success ) {
					echo 'Error: Could not import database (using mysql client).' . PHP_EOL;
				}
				break;

			default:
				echo 'Error: Could not import database; PDO and mysql client (CLI) unavailable.' .
					PHP_EOL;
				$success = false;
				break;
		}

		return $success;
	}
}
