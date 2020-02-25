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
	 * The core class object.
	 *
	 * @since 1.10.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core = null ) {
		$this->core = $core;
	}

	/**
	 * Deactivate and show an error.
	 *
	 * @since 1.7.0
	 *
	 * @param string $error Error message.
	 */
	public function deactivate( $error ) {
		add_action(
			'admin_notices', function () use ( $error ) {
				$allowed_html = [
					'p'      => [],
					'strong' => [],
					'br'     => [],
					'em'     => [],
				];

				$error = '<p>' . sprintf(
					// translators: 1: HTML opening strong tags, 2: HTML closing strong tag, 3: Plugin title.
					__( '%1$s%3$s%2$s has been deactivated due to the following error:', 'boldgrid-backup' ),
					'<strong>',
					'</strong>',
					BOLDGRID_BACKUP_TITLE
				) . '<br /><br />' . $error . '</p>';

				// Echo our admin notice. Hide the "plugin activated" notice.
				echo '
				<div class="notice notice-error is-dismissible">' . wp_kses( $error, $allowed_html ) . '</div>
				<style type="text/css">
					.updated.notice { display: none; }
				</style>
			';
			}
		);

		add_action(
			'admin_init', function() {
				deactivate_plugins( 'boldgrid-backup/boldgrid-backup.php', true );
			}
		);
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
	 * Whether or not this version of the Backup Plugin is compatible with the premium extension.
	 *
	 * @since 1.11.3
	 *
	 * @return bool
	 */
	public function is_premium_compatible() {
		return ! defined( 'BOLDGRID_BACKUP_MIN_VERSION_FOR_PREMIUM' ) ||
			version_compare( BOLDGRID_BACKUP_VERSION, BOLDGRID_BACKUP_MIN_VERSION_FOR_PREMIUM, '>=' );
	}

	/**
	 * Run tests.
	 *
	 * @since 1.7.1
	 *
	 * @see has_compatible_php()
	 * @see Boldgrid_Backup_Admin_Support::has_composer_installed()
	 * @see Boldgrid_Backup_Admin_Support::deactivate()
	 *
	 * @return bool
	 */
	public function run_tests() {
		if ( ! $this->has_compatible_php() ) {
			$this->deactivate(
				sprintf(
					// Translators: 1: Current PHP version, 2: Minimum supported PHP version.
					__(
						'Your PHP version (%1$s) is not supported.  Please upgrade PHP to %2$s or higher, or contact your host for further assistance.',
						'boldgrid-backup'
					),
					PHP_VERSION,
					self::PHP_MIN_VER
				)
			);

			return false;
		}

		if ( ! $this->has_composer_installed() ) {
			$this->deactivate(
				__(
					'The vendor folder is missing. Please run "composer install", or contact your host for further assistance.',
					'boldgrid-backup'
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Render the support page.
	 *
	 * @since 1.10.1
	 */
	public function page() {
		wp_enqueue_style( 'bglib-ui-css' );

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html__( 'Total Upkeep Support', 'boldgrid-backup' ) . '</h1>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-support.php';
		echo '
				</div>
			</div>
		</div>';
	}
}
