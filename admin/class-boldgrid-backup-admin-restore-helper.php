<?php
/**
 * FIle: class-boldgrid-backup-admin-restore-helper.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Restore_Helper
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Restore_Helper {
	/**
	 * An array of error messages.
	 *
	 * @since 1.9.3
	 * @access private
	 * @var array
	 */
	private $errors;

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
			'filename'  => '.htaccess',
			// Whether or not to make a copy of the file before restoration.
			'copy'      => true,
			// Whether or not to keep the copy after restoration.
			'keep_copy' => true,
			// Whether or not the file has been copied.
			'copied'    => false,
		),
		'wpconfig' => array(
			'filename'  => 'wp-config.php',
			'copy'      => true,
			'keep_copy' => false,
			'copied'    => false,
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
	 * Get the last error message.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	public function get_last_error() {
		$last_error = '';

		if ( ! empty( $this->errors ) ) {
			$last_error = end( $this->errors );
		}

		return $last_error;
	}

	/**
	 * Action to take when a .htaccess file has been restored.
	 *
	 * @since 1.5.1
	 */
	public function post_restore_htaccess() {
		add_action( 'shutdown', '\Boldgrid_Backup_Admin_Utility::flush_rewrite_rules' );
	}

	/**
	 * Action to take when the wp-config.php file has been restored.
	 *
	 * @since 1.5.1
	 *
	 * @see Boldgrid_Backup_Admin_Utility::fix_wpconfig()
	 */
	public function post_restore_wpconfig() {
		if ( ! Boldgrid_Backup_Admin_Utility::fix_wpconfig() && ! $this->doing_cron ) {
			do_action(
				'boldgrid_backup_notice',
				esc_html__(
					'Could not update the WordPress configuration file.',
					'boldgrid-backup'
				),
				'notice notice-error is-dismissible'
			);
		}
	}

	/**
	 * Action to take after restoring an archive.
	 *
	 * @since 1.5.1
	 *
	 * @global wp_filesystem
	 *
	 * @param array $info Archive information.
	 */
	public function post_restore( $info ) {
		if ( $info['dryrun'] ) {
			return;
		}

		global $wp_filesystem;

		foreach ( $this->monitor_files as $key => $file ) {
			$original = ABSPATH . $file['filename'];
			$new      = $original . '.bgb';

			// Determine if the file was changed during restoration.
			$post_sha1   = sha1_file( $original );
			$has_changed = $this->monitors_files[ $key ]['pre_sha1'] !== $post_sha1;

			if ( $has_changed ) {
				/**
				 * Action to take after a specific file has been restored.
				 *
				 * @since 1.5.1
				 */
				do_action( 'boldgrid_backup_post_restore_' . $key );
			}

			if ( $file['copy'] && $file['copied'] && ! $file['keep_copy'] ) {
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
	 * @param array $info Archive information.
	 */
	public function pre_restore( $info ) {
		if ( $info['dryrun'] ) {
			return;
		}

		global $wp_filesystem;

		foreach ( $this->monitor_files as $key => $file ) {
			$original = ABSPATH . $file['filename'];
			$new      = $original . '.bgb';

			if ( $file['copy'] && $wp_filesystem->exists( $original ) ) {
				$wp_filesystem->copy( $original, $new, true, 0644 );
				$this->monitor_files[ $key ]['copied'] = true;
			}

			// Store sha1 to help identify if file was restored (later on).
			$this->monitor_files[ $key ]['pre_sha1'] = sha1_file( $original );
		}

		// Only register this action when we know we're doing a restore.
		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	/**
	 * Prepare for a restoration via cron job.
	 *
	 * The restoration request is validated elsewhere.
	 *
	 * @since 1.6.1
	 *
	 * @return bool
	 */
	public function prepare_restore() {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		if ( empty( $pending_rollback ) && empty( $_GET['archive_filename'] ) ) {
			return false;
		}

		/*
		 * Set POST variables.
		 *
		 * The archive_key and the archive_filename must match.
		 */
		$_POST['restore_now']      = 1;
		$_POST['archive_key']      = ( ! empty( $_GET['archive_key'] ) && is_numeric( $_GET['archive_key'] ) ) ?
			(int) $_GET['archive_key'] : 0;
		$_POST['archive_filename'] = ! empty( $pending_rollback['filepath'] ) ?
			basename( $pending_rollback['filepath'] ) : $_GET['archive_filename'];

		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP
		return true;
	}

	/**
	 * Update permissions so an archive is safe to restore.
	 *
	 * The most common failure thus far when extracting an archive is file
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
	 * @return bool True if permissions were able to be updated successfully.
	 */
	public function set_writable_permissions( $archive_filepath ) {
		global $wp_filesystem;

		$zip = new ZipArchive();

		if ( $zip->open( $archive_filepath ) ) {
			for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$data = $zip->statIndex( $i );

				if ( empty( $data['name'] ) ) {
					continue;
				}

				$full_path = ABSPATH . $data['name'];

				// If the file does not exists, no need to check its permissions.
				if ( ! $wp_filesystem->exists( $full_path ) ) {
					continue;
				}

				if ( ! $wp_filesystem->chmod( $full_path ) ) {
					$this->errors[] = sprintf(
						// translators: 1 The path to a file that cannot be restored due to file permissions.
						__( 'Permission denied. Unable to restore the following file: %1$s', 'boldgrid-backup' ),
						$full_path
					);
					return false;
				}
			}
		}

		return true;
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
		if ( $this->doing_cron ) {
			return;
		}

		$last_error = error_get_last();

		/*
		 * If there's no error or this is not fatal, abort.
		 *
		 * @see http://php.net/manual/en/errorfunc.constants.php
		 */
		if ( empty( $last_error ) || 1 !== $last_error['type'] ) {
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
	 * @param  WP_Error $error WP_Error object.
	 * @return mixed False if no action is taken, otherwise a string containing
	 *               a description of the action.
	 */
	public function restore_fail( $error ) {
		global $wp_filesystem;

		$message = $error->get_error_message();
		$data    = $error->get_error_data();

		if ( __( 'Could not copy file.' ) === $message ) {

			// Take action if we are having trouble restoring .git/objects/.
			preg_match( '/(.*\.git\/objects\/).*/', $data, $matches );
			if ( ! empty( $matches[1] ) ) {
				$new_error = false;
				return apply_filters( 'boldgrid_backup_cannnot_restore_git_objects', $matches[1] );
			}
		}

		return false;
	}
}
