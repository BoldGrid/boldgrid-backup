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
	 * Minumum PHP supported version.
	 *
	 * @since 1.7.1
	 *
	 * @var string
	 */
	const PHP_MIN_VER = '5.4.0';

	/**
	 * Deactivate and show an error.
	 *
	 * @since 1.7.0
	 *
	 * @param string $error Error message.
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
		} );

		add_action( 'admin_init', function() {
			deactivate_plugins( 'boldgrid-backup/boldgrid-backup.php', true );
		} );
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

	/**
	 * Verify compatible PHP version.
	 *
	 * @since 1.7.1
	 *
	 * @return bool
	 */
	public function has_compatible_php() {
		return version_compare( PHP_VERSION, self::PHP_MIN_VER, '>=' );
	}

	/**
	 * Run tests.
	 *
	 * @since 1.7.1
	 *
	 * @see has_compatible_php()
	 * @see Boldgrid_Backup_Admin_Support::has_composer_installed()
	 * @see Boldgrid_Backup_Admin_Support::has_been_built()
	 * @see Boldgrid_Backup_Admin_Support::deactivate()
	 *
	 * @return bool
	 */
	public function run_tests() {
		if ( ! $this->has_compatible_php() ) {
			$this->deactivate( sprintf(
				// Translators: 1: Current PHP version, 2: Minimum supported PHP version.
				__(
					'Your PHP version (%1$s) is not supported.  Please upgrade PHP to %2$s or higher, or contact your host for further assistance.',
					'boldgrid-backup'
				),
				PHP_VERSION,
				self::PHP_MIN_VER
			) );

			return false;
		}

		if ( ! $this->has_composer_installed() ) {
			$this->deactivate( __(
				'The vendor folder is missing. Please run "composer install", or contact your host for further assistance.',
				'boldgrid-backup'
			) );

			return false;
		}

		if ( ! $this->has_been_built() ) {
			$this->deactivate( __(
				'The "build" folder is missing. Please run "yarn install" and "gulp", or contact your host for further assistance.',
				'boldgrid-backup'
			) );

			return false;
		}

		return true;
	}
}
