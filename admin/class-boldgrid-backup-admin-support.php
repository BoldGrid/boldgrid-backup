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
	 * Add an admin notice.
	 *
	 * This method use to be "deactivate". Users only have 1 chance to see the message we're showing
	 * if we deactivate the plugin. If we instead just show an admin message, the user has more than
	 * once chance to see the notice and take action to resolve the issue.
	 *
	 * @since 1.7.0
	 *
	 * @param string $error Error message.
	 */
	public function add_admin_notice( $error ) {
		add_action(
			'admin_notices', function () use ( $error ) {
				$allowed_html = [
					'p'      => [],
					'strong' => [],
					'br'     => [],
					'em'     => [],
					'pre'    => [],
					'a'      => [
						'href'   => [],
						'target' => [],
					],
				];

				$error = '<p>' . sprintf(
					// translators: 1: HTML opening strong tags, 2: HTML closing strong tag, 3: Plugin title.
					__( '%1$s%3$s%2$s is unable to load due to the following error:', 'boldgrid-backup' ),
					'<strong>',
					'</strong>',
					BOLDGRID_BACKUP_TITLE
				) . '<br /><br />' . $error . '</p>';

				// Inform the user how to get help.
				$error .= '<p>' . sprintf(
					// translators: 1 Plugin title, 2 opening anchor tag linking to plugin page, 3 url to plugin page, 4 closing anchor tag.
					__( 'Please deactivate / reactivate Total Upkeep as this often resolves issues. If you are installing %1$s from .zip, ensure you downloaded it from %2$s%3$s%4$s. For additional help, please post a question in the %5$sWordPress Support Forums.%4$s', 'boldgrid-backup' ),
					BOLDGRID_BACKUP_TITLE,
					'<a href="https://wordpress.org/plugins/boldgrid-backup/" target="_blank">',
					'https://wordpress.org/plugins/boldgrid-backup/',
					'</a>',
					'<a href="https://wordpress.org/support/plugin/boldgrid-backup/#new-topic-0" target="_blank">'
				) . '</p>';

				// Echo our admin notice. Hide the "plugin activated" notice.
				echo '
				<div class="notice notice-error is-dismissible">' . wp_kses( $error, $allowed_html ) . '</div>
				<style type="text/css">
					.updated.notice { display: none; }
				</style>
			';
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
	 * Verify appropriate library is available.
	 *
	 * This is a very basic test. It could be more exhaustive. However, a missing library is rare and
	 * exhaustive tests are not needed.
	 *
	 * @since 1.13.5
	 *
	 * @return bool
	 */
	public function has_library() {
		$has_library = true;

		$classes = array(
			'Boldgrid\Library\Library\Usage\Notice',
			// Needed on the dashboard.
			'Boldgrid\Library\Library\Ui\Card',
		);

		foreach ( $classes as $class ) {
			if ( ! class_exists( $class ) ) {
				$has_library = false;
				break;
			}
		}

		$methods = array(
			'Boldgrid\Library\Library\Plugin\Plugins' => 'getAllPlugins',
		);

		foreach ( $methods as $class => $method ) {
			if ( ! method_exists( $class, $method ) ) {
				$has_library = false;
				break;
			}
		}

		return $has_library;
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
	 * Do a basic test and ensure we have access to the library.
	 *
	 * In theory, we should never have an issue with the library loading. This method should never
	 * be needed, and any issues with the library should be troubleshooted and resolved. However,
	 * we cannot have a library issue cause a fatal error, hence this check.
	 *
	 * @since 1.13.5
	 *
	 * @return bool
	 */
	public function run_library_tests() {
		// Total Upkeep's library is only registered after activation, hence the is_active() check below.
		if ( Boldgrid_Backup_Admin_Utility::is_active() && ! $this->has_library() ) {
			$boldgrid_settings = get_option( 'boldgrid_settings', array() );

			$this->add_admin_notice( sprintf(
				// translators: 1 A list of library versions that are registered. It will be within a <pre> tag.
				__(
					'One or more library files are missing. Registered libraries: %1$s',
					'boldgrid-backup'
				),
				! empty( $boldgrid_settings['library'] ) ? '<pre>' . print_r( $boldgrid_settings['library'], 1 ) . '</pre>' : __( 'None', 'boldgrid-backup' ) // phpcs:ignore
			));

			return false;
		}

		return true;
	}

	/**
	 * Run tests.
	 *
	 * These tests are triggered by the main class-boldgrid-backup.php file. If these tests fail, the
	 * rest of the plugin will not load.
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
		// Utility method required in this method.
		if ( ! class_exists( 'Boldgrid_Backup_Admin_Utility' ) ) {
			require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-utility.php';
		}

		if ( ! $this->has_compatible_php() ) {
			$this->add_admin_notice(
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
			$this->add_admin_notice(
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
		$settings = $this->core->settings->get_settings();
		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );

		$this->core->auto_rollback->enqueue_home_scripts();
		$this->core->auto_rollback->enqueue_backup_scripts();
		$this->core->archive_actions->enqueue_scripts();

		$this->core->folder_exclusion->enqueue_scripts();
		$this->core->db_omit->enqueue_scripts();

		$in_modal = true;
		$modal    = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
		$in_modal = false;

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html__( 'Total Upkeep Support', 'boldgrid-backup' ) . '</h1>
						<div class="page-title-actions">
						<a href="#TB_inline?width=800&amp;height=600&amp;inlineId=backup_now_content" class="thickbox page-title-action page-title-action-primary">' .
							esc_html__( 'Backup Site Now', 'boldgrid-backup' ) . '
						</a>
						<a class="page-title-action add-new">' . esc_html__( 'Upload Backup', 'boldgrid-backup' ) . '</a>
					</div>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		echo $modal; //phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-support.php';
		echo '
				</div>
			</div>
		</div>';
	}
}
