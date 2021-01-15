<?php
/**
 * File: class-info.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Restorer;

/**
 * Class: Info
 *
 * @since SINCEVERSION
 */
class Db {
	private $error;

	private $db_dump_filepath;

	/**
	 *
	 */
	public function __construct( $zip_filepath, $db_dump_filepath ) {
		$this->core             = apply_filters( 'boldgrid_backup_get_core', null );
		$this->zip_filepath     = $zip_filepath;
		$this->db_dump_filepath = $db_dump_filepath;
		$this->logger           = apply_filters( 'boldgrid_backup_get_restore_logger', null );

		$this->log( '$db_dump_filepath = ' . $db_dump_filepath );
		$this->log( '$zip_filepath = ' . $zip_filepath );
	}

	/**
	 *
	 */
	private function get_prefix() {
		$db_prefix = null;

		// Get the database table prefix from the new "wp-config.php" file, if exists.
		if ( $this->core->wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wpcfg_contents = $this->core->wp_filesystem->get_contents( ABSPATH . 'wp-config.php' );
		}

		if ( ! empty( $wpcfg_contents ) ) {
			preg_match( '#\$table_prefix.*?=.*?' . "'" . '(.*?)' . "'" . ';#', $wpcfg_contents, $matches );

			if ( ! empty( $matches[1] ) ) {
				$db_prefix = $matches[1];
			}
		}

		return $db_prefix;
	}

	/**
	 *
	 */
	private function is_encrypted() {
		// todo. Fix this. We have the sql.zip filepath, but not the main zip.
		return false;

		$this->core->archive->init( $this->filepath );
		return $this->core->archive->get_attribute( 'encrypt_db' );
	}

	/**
	 *
	 */
	private function log( $message ) {
		$this->logger->add( 'DB Restorer: ' . $message );
	}

	/**
	 *
	 */
	public function run() {
		// Check input.
		if ( empty( $this->db_dump_filepath ) ) {
			$this->error = esc_html__( 'The database dump file was not found.', 'boldgrid-backup' );

			$this->log( $this->error );
			do_action( 'boldgrid_backup_notice', $this->error, 'notice notice-error is-dismissible' );

			return false;
		}

		// Check if functional.
		if ( ! $this->core->test->run_functionality_tests() ) {
			$this->core->notice->functionality_fail_notice();
			return false;
		}

		$this->core->set_time_limit();

		$db_prefix = $this->get_prefix();
		$this->log( '$db_prefix = ' . $db_prefix );

		// Get the WP Options for "siteurl" and "home", to restore later.
		$wp_siteurl = get_option( 'siteurl' );
		$wp_home    = get_option( 'home' );

		$is_encrypted = $this->is_encrypted();
		$this->log( '$is_encrypted = ' . $is_encrypted );
		if ( $is_encrypted ) {
			/**
			 * If BGBP is activated, then check for encryption and decrypt the file.
			 *
			 * @since 1.12.0
			 */
			do_action( 'boldgrid_backup_crypt_file', $this->db_dump_filepath, 'd' );
		}

		// Import the dump file.
		$this->log( 'Running import() method...' );
		$importer = new \Boldgrid_Backup_Admin_Db_Import();
		$status   = $importer->import( $this->db_dump_filepath );

		if ( ! empty( $status['error'] ) ) {
			$this->error = $status['error'];

			$this->log( 'import() method failed: ' . $this->error );
			do_action( 'boldgrid_backup_notice', $status['error'], 'notice notice-error is-dismissible' );

			return false;
		} else {
			$this->log( 'import() method successful.' );
		}

		// Set the database prefix, if supplied/changed.
		if ( ! empty( $db_prefix ) ) {
			global $wpdb;
			$wpdb->set_prefix( $db_prefix );
		}

		// Clear the WordPress cache.
		wp_cache_flush();

		// Get the restored "siteurl" and "home".
		$restored_wp_siteurl = get_option( 'siteurl' );
		$restored_wp_home    = get_option( 'home' );

		// If changed, then update the siteurl in the database.
		if ( $restored_wp_siteurl !== $wp_siteurl ) {
			$this->log( 'siteurl has changed.' );

			$update_siteurl_success = \Boldgrid_Backup_Admin_Utility::update_siteurl( $restored_wp_siteurl, $wp_siteurl );

			if ( ! $update_siteurl_success ) {
				$this->error = esc_html__(
					'The WordPress siteurl has changed.  There was an issue changing it back.  You will have to fix the siteurl manually in the database, or use an override in your wp-config.php file.',
					'boldgrid-backup'
				);

				$this->log( $this->error );
				do_action( 'boldgrid_backup_notice', $this->error, 'notice notice-error is-dismissible' );
			}
		} else {
			$this->log( 'siteurl has not changed.' );
		}

		// If changed, then restore the WP Option for "home".
		if ( $restored_wp_home !== $wp_home ) {
			$this->log( 'home has changed.' );

			// There may be a filter, so remove it.
			remove_all_filters( 'pre_update_option_home' );

			update_option( 'home', untrailingslashit( $wp_home ) );
		} else {
			$this->log( 'home has not changed.' );
		}

		$this->core->wp_filesystem->delete( $this->db_dump_filepath, false, 'f' );

		return true;
	}
}
