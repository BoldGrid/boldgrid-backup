<?php
/**
 * File: class-site-restore.php
 *
 * Used when there is a severe issue with the site which requires immediate restoration from the
 * latest backup archive.  A restoration can be forced using the "restore" flag.
 *
 * @link       https://www.boldgrid.com
 * @since      1.9.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput,WordPress.WP.AlternativeFunctions
 */

namespace Boldgrid\Backup\Cron;

/**
 * Class: Site_Restore.
 *
 * @since 1.9.0
 */
class Site_Restore {
	/**
	 * Run the restoration process.
	 *
	 * @since 1.9.0
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 * @see self::restore()
	 */
	public function run() {
		echo 'Starting restoration process...' . PHP_EOL;
		echo 'Attempting to restore "' . Info::get_info()['siteurl'] .
			'" from backup archive file "' . Info::get_info()['filepath'] . '"...' . PHP_EOL;
		echo ( $this->restore() ? 'Success.' : 'Error: Could not perform restoration.' ) . PHP_EOL;
	}

	/**
	 * Ensure that archive file destinations are writable.
	 *
	 * @since  1.9.0
	 * @access private
	 *
	 * @see ZipArchive()
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 */
	private function set_writable_permissions() {
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new \ZipArchive();

			if ( $zip->open( Info::get_info()['filepath'] ) ) {
				for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$data = $zip->statIndex( $i );

					if ( ! empty( $data['name'] ) ) {
						chmod( Info::get_info()['ABSPATH'] . $data['name'], 0644 );
					}
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
	 * @since  1.9.0
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
	 * @since  1.9.0
	 * @access private
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 *
	 * @return bool
	 */
	private function get_db_config() {
		$wpconfig = file_get_contents( Info::get_info()['ABSPATH'] . 'wp-config.php' );

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
	 * @since  1.9.0
	 * @access private
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 * @see \Boldgrid\Backup\Cron\Info::choose_method()
	 * @see \Boldgrid_Backup_Admin_Cli::call_command()
	 * @see self::set_time_limit()
	 * @see self::restore_files()
	 * @see self::restore_database()
	 * @see self::increment_restore_attempts()
	 *
	 * @return bool;
	 */
	private function restore() {
		$restore_cmd = ! empty( Info::get_info()['restore_cmd'] ) ?
			Info::get_info()['restore_cmd'] : null;

		if ( 'ajax' === Info::choose_method() ) {
			// Call the normal restore command.
			echo 'Using Ajax URL address restoration process...' . PHP_EOL;
			\Boldgrid_Backup_Admin_Cli::call_command( $restore_cmd, $success, $return_var );
		} else {
			// Start the standalone restoration process.
			echo 'Cannot reach the site URL; using standalone restoration process...' . PHP_EOL;
			ignore_user_abort( true );
			$this->set_time_limit();
			$success = $this->restore_files() && $this->restore_database();
		}

		$this->increment_restore_attempts();

		return $success;
	}

	/**
	 * Perform restoration of files.
	 *
	 * @since  1.9.0
	 * @access private
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 * @see \Boldgrid_Backup_Admin_Cli::call_command()
	 * @see \ZipArchive::open()
	 * @see \ZipArchive::extractTo()
	 * @see \PclZip::extract()
	 *
	 * @return bool
	 */
	private function restore_files() {
		$success = false;
		$info    = Info::get_info();
		$method  = Info::choose_method();

		switch ( true ) {
			case class_exists( 'ZipArchive' ):
				echo 'Attempting file restoration using PHP ZipArchive...' . PHP_EOL;
				$archive = new \ZipArchive();
				if ( true === $archive->open( $info['filepath'] ) ) {
					$success = $archive->extractTo( $info['ABSPATH'] );
					$archive->close();
				}
				break;

			case file_exists( $info['ABSPATH'] . 'wp-admin/includes/class-pclzip.php' ):
				echo 'Attempting file restoration using PHP PCLZip...' . PHP_EOL;
				require $info['ABSPATH'] . 'wp-admin/includes/class-pclzip.php';
				$archive = new \PclZip( $info['filepath'] );
				$result  = $archive->extract(
					PCLZIP_OPT_PATH,
					$info['ABSPATH'],
					PCLZIP_OPT_REPLACE_NEWER
				);
				$success = is_array( $result ) && ! empty( $result );
				if ( ! $success ) {
					echo 'Error: ' . $archive->errorInfo( true ) . PHP_EOL;
				}
				break;

			case \Boldgrid_Backup_Admin_Cli::call_command( 'unzip', $success, $return_var ) || $success || 0 === $return_var:
				echo 'Attempting file restoration using unzip (CLI)...' . PHP_EOL;
				$cmd = 'cd ' . $info['ABSPATH'] . ';unzip -oqq ' . $info['filepath'];
				\Boldgrid_Backup_Admin_Cli::call_command(
					$cmd,
					$success,
					$return_var
				);

				echo 'Checking/fixing permissions...' . PHP_EOL;
				// Assuming Linux.
				$cmd = 'for i in $(unzip -Z1 ' . $info['filepath'] .
				' | sed -e "/^\.\/$/d" -e "s~/$~~"); do test -d "$i" && chmod 755 "$i";test -f "$i" && chmod 644 "$i"; done';
				\Boldgrid_Backup_Admin_Cli::call_command(
					$cmd,
					$success2,
					$return_var2
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
	 * @since  1.9.0
	 * @access private
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 * @see self::get_db_config()
	 * @see \Boldgrid_Backup_Admin_Db_Import::import()
	 * @see \Boldgrid_Backup_Admin_Cli::call_command()
	 *
	 * @return bool;
	 */
	private function restore_database() {
		$info = Info::get_info();

		if ( ! file_exists( $info['db_filepath'] ) ) {
			echo 'Error: Database dump file "' . $info['db_filepath'] . '" does not exist.' .
				PHP_EOL;
			$success = false;
		} else {
			$success = $this->get_db_config();
		}

		switch ( true ) {
			case ! $success:
				echo 'Error: Could not get database credentials from "' .
					$info['ABSPATH'] . 'wp-config.php".' . PHP_EOL;
				break;

			case class_exists( 'PDO' ):
				echo 'Attempting to restore database using PHP PDO...' . PHP_EOL;
				require dirname( __DIR__ ) . '/admin/class-boldgrid-backup-admin-db-import.php';
				$importer = new \Boldgrid_Backup_Admin_Db_Import();
				$success  = $importer->import( $info['ABSPATH'] . $info['db_filename'] );

				if ( ! $success ) {
					echo 'Error: Could not import database (using PDO).' . PHP_EOL;
				}
				break;

			case \Boldgrid_Backup_Admin_Cli::call_command( 'mysql -V', $success, $return_var ) || $success || 0 === $return_var:
				echo 'Attempting to restore database using mysql client (CLI)...' . PHP_EOL;
				$cmd = 'mysql -h ' . DB_HOST . ' -p' . DB_PASSWORD . ' -u ' . DB_USER . ' ' .
					DB_NAME . ' < "' . $info['db_filepath'] . '"';
				\Boldgrid_Backup_Admin_Cli::call_command( $cmd, $success, $return_var );

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

	/**
	 * Increment restore attemps counter and update results file.
	 *
	 * @since  1.9.0
	 * @access private
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_results_filepath()
	 * @see \Boldgrid\Backup\Cron\Info::set_info_item()
	 */
	private function increment_restore_attempts() {
		$results = json_decode( file_get_contents( Info::get_results_filepath() ), true );

		$results['restore_attempts'] = isset( $results['restore_attempts'] ) ?
			++$results['restore_attempts'] : 1;

		file_put_contents( Info::get_results_filepath(), json_encode( $results ) );

		Info::set_info_item( 'restore_attempts', $results['restore_attempts'] );
	}
}
