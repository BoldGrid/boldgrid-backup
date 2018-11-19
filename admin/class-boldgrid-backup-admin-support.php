<?php
/**
 * File: class-boldgrid-backup-admin-support.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.7.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Support
 *
 * @since 1.7.0
 */
class Boldgrid_Backup_Admin_Support {

	/**
	 * Deactivate and show an error.
	 *
	 * @since 1.7.0
	 *
	 * @param string $error
	 */
	public function deactivate( $error ) {
		add_action( 'admin_notices', function () use ( $error ) {
			$allowed_html = array(
				'p'      => array(),
				'strong' => array(),
				'br'     => array(),
				'em'     => array(),
			);

			$error = '<p>' . sprintf(
				/* translators: 1 and 2 are opening and closing string tags. */
				__( '%1$sBoldGrid Backup%2$s has been deactivated due to the following error:', 'boldgrid-backup' ),
				'<strong>',
			'</strong>' ) . '<br /><br />' . $error . '</p>';

			// Echo our admin notice. Hide the "plugin activated" notice.
			echo '
				<div class="notice notice-error is-dismissible">' . wp_kses( $error, $allowed_html ) . '</div>
				<style type="text/css">
					.updated.notice { display: none; }
				</style>
			';
		});

		add_action( 'admin_init', function() {
			deactivate_plugins( 'boldgrid-backup/boldgrid-backup.php', true );
		});
	}

	/**
	 * Determine whether or not we have our build directory.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function has_been_built() {
		return file_exists( BOLDGRID_BACKUP_PATH . '/build/clipboard.min.js' );
	}

	/**
	 * Determine whether or not composer has been setup.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function has_composer_installed() {
		$exists_composer = file_exists( BOLDGRID_BACKUP_PATH . '/composer.json' );
		$exists_autoload = file_exists( BOLDGRID_BACKUP_PATH . '/vendor/autoload.php' );

		return ! $exists_composer || $exists_autoload;
	}
}
