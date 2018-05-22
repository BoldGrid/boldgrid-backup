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

		// Only register this action when we know we're doing a restore.
		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	/**
	 * Prepare for a restoration via cron job.
	 *
	 * @since 1.6.1
	 *
	 * @return bool
	 */
	public function prepare_restore() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		if ( empty( $pending_rollback ) ) {
			return false;
		}

		/*
		 * Set POST variables.
		 *
		 * The archive_key and the archive_filename must match.
		 */
		$_POST['restore_now'] = 1;
		$_POST['archive_key'] = 0;
		$_POST['archive_filename'] = basename( $pending_rollback['filepath'] );

		return true;
	}

	/**
	 * Update permissions so an archive is safe to restore.
	 *
	 * The most common failure thus for when extracting an archive is file
	 * permissions related. If WordPress cannot restore a file because the current
	 * file's permissions don't allow editing, then the restoration both (1) fails
	 * and (2) gives us a half restored site.
	 *
	 * This method loops through all files in the archive and updates the actual
	 * file's permissions in an attempt to avoid file permission issues.
	 *
	 * @since 1.6.0
	 *
	 * @param string $archive_filepath Full path to an archive file.
	 */
	public function set_writable_permissions( $archive_filepath ) {
		global $wp_filesystem;

		$zip = new ZipArchive();

		if( $zip->open( $archive_filepath ) ) {
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$data = $zip->statIndex( $i );

				if( empty( $data['name'] ) ) {
					continue;
				}

				$wp_filesystem->chmod( ABSPATH . $data['name'] );
			}
		}
	}

	/**
	 * Action to take during shutdown hook.
	 *
	 * This method was written because many of the calls in
	 * wp-admin/includes/class-pclzip.php are suppressed using @. If you are
	 * restoring a relatively large file and you reach a memory limit, the
	 * fatal error will never be apparent because of the @ suppression.abstract
	 *
	 * This method allows us to show any fatal errors.
	 *
	 * @since 1.5.1
	 */
	public function shutdown() {
		if( $this->doing_cron ) {
			return;
		}

		$last_error = error_get_last();

		/*
		 * If there's no error or this is not fatal, abort.
		 *
		 * @see http://php.net/manual/en/errorfunc.constants.php
		 */
		if( empty( $last_error ) || 1 !== $last_error['type'] ) {
			return;
		}

		$message = sprintf(
			'<strong>%1$s</strong>: %2$s in %3$s on line %4$s',
			__( 'Fatal error', 'boldgrid-backup' ),
			$last_error['message'],
			$last_error['file'],
			$last_error['line']
		);

		do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );

		echo '
			<script type="text/javascript">
				jQuery( ".restoration-in-progress" ).hide();
			</script>
		';
	}

	/**
	 * If a restoration fails, take action.
	 *
	 * @since 1.5.1
	 *
	 * @param  WP_Error $error
	 * @return mixed False if no action is taken, otherwise a string containing
	 *               a description of the action.
	 */
	public function restore_fail( $error ) {
		global $wp_filesystem;

		$message = $error->get_error_message();
		$data = $error->get_error_data();

		if( __( 'Could not copy file.' ) === $message ) {

			// Take action if we are having trouble restoring .git/objects/.
			preg_match('/(.*\.git\/objects\/).*/', $data, $matches );
			if( ! empty( $matches[1] ) ) {
				$new_error = false;
				return apply_filters( 'boldgrid_backup_cannnot_restore_git_objects', $matches[1] );
			}
		}

		return false;
	}
}
