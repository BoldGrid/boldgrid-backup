<?php
/**
 * BoldGrid Backup Admin Restore Helper Class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Restore Helper Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Restore_Helper {

	/**
	 * Whether or not we are doing cron.
	 *
	 * @since 1.5.1
	 * @var   bool
	 */
	public $doing_cron;

	/**
	 * A config of files to monitor before / after a restoration.
	 *
	 * @since 1.5.1
	 * @var   array
	 */
	public $monitor_files = array(
		'htaccess' => array(
			// Filename, relative to ABSPATH.
			'filename' => '.htaccess',
			// Whether or not to make a copy of the file before restoration.
			'copy' => true,
			// Whether or not to keep the copy after restoration.
			'keep_copy' => true,
			// Whether or not the file has been copied.
			'copied' => false,
		),
		'wpconfig' => array(
			'filename' => 'wp-config.php',
			'copy' => true,
			'keep_copy' => false,
			'copied' => false,
		),
	);

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 */
	public function __construct() {
		$this->doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
	}

	/**
	 * Action to take when a .htaccess file has been restored.
	 *
	 * @since 1.5.1
	 */
	public function post_restore_htaccess() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * Action to take when the wp-config.php file has been restored.
	 *
	 * @since 1.5.1
	 */
	public function post_restore_wpconfig() {
		$result = Boldgrid_Backup_Admin_Utility::fix_wpconfig();

		if ( ! $result ) {
			$message = esc_html__( 'Could not update the WordPress configuration file.', 'boldgrid-backup' );

			error_log( __METHOD__ . ': ' . $message );

			if ( ! $this->doing_cron ) {
				do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );
			}
		}
	}

	/**
	 * Action to take after restoring an archive.
	 *
	 * @since 1.5.1
	 *
	 * @global wp_filesystem
	 *
	 * @param array $info
	 */
	public function post_restore( $info ) {
		if( $info['dryrun'] ) {
			return;
		}

		global $wp_filesystem;

		foreach( $this->monitor_files as $key => $file ) {
			$original = ABSPATH . $file['filename'];
			$new = $original . '.bgb';

			// Determine if the file was restored from backup.
			$file_restored = false;
			if( $file['copied'] && sha1_file( $original ) !== sha1_file( $new ) ) {
				$file_restored = true;
			} elseif( $file['copy'] && ! $file['copied'] && $wp_filesystem->exists( $original ) ) {
				$file_restored = true;
			}

			if( $file_restored ) {
				/**
				 * Action to take after a specific file has been restored.
				 *
				 * @since 1.5.1
				 */
				do_action( 'boldgrid_backup_post_restore_' . $key );
			}

			if( $file['copy'] && $file['copied'] && ! $file['keep_copy'] ) {
				$wp_filesystem->delete( $new );
			}
		}
	}

	/**
	 * Action to take before restoring an archive.
	 *
	 * @since 1.5.1
	 *
	 * @global wp_filesystem
	 *
	 * @param array $info
	 */
	public function pre_restore( $info ) {
		if( $info['dryrun'] ) {
			return;
		}

		global $wp_filesystem;

		foreach( $this->monitor_files as $key => $file ) {
			$original = ABSPATH . $file['filename'];
			$new = $original . '.bgb';

			if( $file['copy'] && $wp_filesystem->exists( $original ) ) {
				$wp_filesystem->copy( $original, $new, true, 0644 );
				$this->monitor_files[$key]['copied'] = true;
			}
		}
	}
}
