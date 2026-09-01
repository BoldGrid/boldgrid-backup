<?php
/**
 * File: class-boldgrid-backup-admin-go-pro.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Go_Pro
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Go_Pro {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Generic upgrade link.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    string
	 */
	public static $url = 'https://www.boldgrid.com/update-backup';

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get "setup" admin notices.
	 *
	 * This method is currently used to create the admin notices to help guide the user to getting a
	 * premium key and getting / activating the premium extension.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_admin_notices() {
		$notices = [];

		// If the premium plugin is installed and all is good, abort!
		if ( $this->core->config->is_premium_done ) {
			return $notices;
		}

		// Check user role.
		if ( ! current_user_can( 'update_plugins' ) ) {
			return $notices;
		}

		if ( ! class_exists( '\Boldgrid\Library\Library\Notice' ) ) {
			return $notices;
		}

		// Avoid a fatal error.
		if ( ! class_exists( '\Boldgrid\Library\Library\Plugin\Plugin' ) ) {
			$notices = [
				[
					'id'      => 'boldgrid_backup_missing_library',
					'show'    => true,
					'message' => '<p>' . __( 'Class "Boldgrid\Library\Library\Plugin\Plugin" not found. Please ensure you are running the lastest version of the BoldGrid Library.', 'boldgrid-backup' ) . '</p>',
					'class'   => 'notice notice-error',
				],
			];

			return $notices;
		}

		$is_premium = $this->core->config->get_is_premium();

		$premium_plugin = \Boldgrid\Library\Library\Plugin\Factory::create( 'boldgrid-backup-premium' );

		$notices = [
			[
				'id'      => 'boldgrid_backup_activate_premium',
				'show'    => $is_premium && $this->core->config->is_premium_installed,
				'message' => '<p>' . sprintf(
					// translators: 1: URL address for the wp-admin plugins page, 2: Plugin/extension title.
					__(
						'You have a <strong>Premium BoldGrid Connect Key</strong> and you have the <strong>%2$s Extension installed</strong>. Please go to your <a href="%1$s">plugins page</a> and activate your premium extension!',
						'boldgrid-backup'
					),
					admin_url( 'plugins.php' ),
					BOLDGRID_BACKUP_TITLE . ' Premium'
				) . '</p>',
				'class'   => 'notice notice-warning',
			],
			[
				'id'      => 'boldgrid_backup_upgrade_premium',
				'show'    => ! $is_premium && $this->core->config->is_premium_active,
				'message' => '<p>' . sprintf(
					// translators: 1: URL address for the upgrade page, 2: Premium plugin title.
					__( 'Thank you for activating the <strong>%3$s</strong> plugin! Before you can begin using all of the premium features, you must <a href="%2$s">add your premium key</a>. If you are using an Official BoldGrid Host, contact them or login to their management system to retrieve your Premium key. Otherwise, please visit <a href="%1$s" target="_blank">BoldGrid Central</a> to upgrade.', 'boldgrid-backup' ),
					$this->get_premium_url( 'bgbkup-premium-activate' ),
					admin_url( 'options-general.php?page=boldgrid-connect.php' ),
					BOLDGRID_BACKUP_TITLE . ' Premium'
				) . '</p>',
				'class'   => 'notice notice-warning',
			],
			[
				'id'      => 'boldgrid_backup_download_premium',
				'show'    => $is_premium && ! $this->core->config->is_premium_installed,
				'message' => '<p>' . sprintf(
					// translators: 1: URL address to download plugin 2: URL to plugin-installer.php, 3: Plugin title, 4: Premium plugin title.
					__(
						'Hello there! We see that you have a <strong>Premium BoldGrid Connect Key</strong> and you have the <strong>%3$s</strong> plugin activated! <a href="%1$s">Click here</a> to download the <strong>%4$s</strong> plugin and gain access to more features! After the download completes, go to <a href="%2$s">Plugins &raquo; Add New</a> and click the <em>Upload Plugin</em> button at the top of the page to upload your new plugin.',
						'boldgrid-backup'
					),
					$premium_plugin->getDownloadUrl(),
					admin_url( 'plugin-install.php' ),
					BOLDGRID_BACKUP_TITLE,
					BOLDGRID_BACKUP_TITLE . ' Premium'
				) . '</p>',
				'class'   => 'notice notice-warning',
			],
		];

		return $notices;
	}

	/**
	 * Display "setup" admin notices.
	 *
	 * This method is currently used to display admin notices to help guide the
	 * user to getting a premium key and getting / activating the premium extension.
	 *
	 * @since 1.6.0
	 */
	public function admin_notice_setup() {
		// Don't bombard the user with admin notices. Only show these notices if the user is on a page for this plugin.
		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : ''; // phpcs:ignore
		if ( substr( $page, 0, strlen( 'boldgrid-backup' ) ) !== 'boldgrid-backup' ) {
			return;
		}

		$notices = $this->get_admin_notices();

		foreach ( $notices as $notice ) {
			if ( $notice['show'] ) {
				\Boldgrid\Library\Library\Notice::show( $notice['message'], $notice['id'], $notice['class'] );
				break;
			}
		}
	}

	/**
	 * Get a "Get Premium" button.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $url  URL address for the upgrade page.
	 * @param  string $text Button text.
	 * @return string
	 */
	public function get_premium_button( $url = null, $text = 'Get Premium' ) {
		$url = ! empty( $url ) ? $url : $this->get_premium_url();

		return sprintf(
			'
			<a href="%1$s" class="button button-success" target="_blank">%2$s</a>',
			esc_url( $url ),
			$text
		);
	}

	/**
	 * Get a "Get Premium" url.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $source Source to append to url.
	 * @param  string $url    URL address for the upgrade page.
	 * @return string
	 */
	public function get_premium_url( $source = 'bgbkup', $url = 'https://www.boldgrid.com/update-backup' ) {
		$url = add_query_arg( 'source', $source, $url );

		/**
		 * Allow the filtering of the premium url.
		 *
		 * @since SINCEVERISON
		 *
		 * @param string $url    The url to be filtered.
		 * @param string $source A label (used in the url) to uniquely identify this link.
		 */
		$url = apply_filters( 'boldgrid_backup_premium_url', $url, $source );

		return $url;
	}
}
