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
	 * Display "setup" admin notices.
	 *
	 * This method is currently used to display admin notices to help guide the
	 * user to getting a premium key and getting / activating the premium extension.
	 *
	 * @since 1.6.0
	 */
	public function admin_notice_setup() {
		// If the premium plugin is installed and all is good, abort!
		if ( $this->core->config->is_premium_done ) {
			return;
		}

		// Check user role.
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( ! class_exists( '\Boldgrid\Library\Library\Notice' ) ) {
			return;
		}

		$is_premium = $this->core->config->get_is_premium();

		$notices = array(
			array(
				'id'      => 'boldgrid_backup_activate_premium',
				'show'    => $is_premium && $this->core->config->is_premium_installed,
				'message' => '<p>' . sprintf(
					// translators: 1: URL address for the wp-admin plugins page.
					__(
						'You have a <strong>Premium BoldGrid Connect Key</strong> and you have the <strong>BoldGrid Backup Premium Extension installed</strong>. Please go to your <a href="%1$s">plugins page</a> and activate your premium extension!',
						'boldgrid-backup'
					),
					admin_url( 'plugins.php' )
				) . '</p>',
			),
			array(
				'id'      => 'boldgrid_backup_upgrade_premium',
				'show'    => ! $is_premium && $this->core->config->is_premium_active,
				'message' => '<p>' . sprintf(
					// translators: 1: URL address for the upgrade page.
					__( 'Thank you for activating the <strong>BoldGrid Backup Premium Extension</strong>! Before you can begin using all of the premium features, you must <a href="%2$s">add your premium key</a>. If you are using an Official BoldGrid Host, contact them or login to their management system to retrieve your Premium key. Otherwise, please visit <a href="%1$s" target="_blank">BoldGrid Central</a> to upgrade.', 'boldgrid-backup' ),
					self::$url,
					admin_url( 'admin.php?page=boldgrid-backup-settings&section=connect_key' )
				) . '</p>',
			),
			array(
				'id'      => 'boldgrid_backup_download_premium',
				'show'    => $is_premium && ! $this->core->config->is_premium_installed,
				'message' => '<p>' . sprintf(
					// translators: 1: URL address for BoldGrid Central.
					__(
						'Hello there! We see that you have a <strong>Premium BoldGrid Connect Key</strong> and you have the <strong>BoldGrid Backup Plugin</strong> activated! Be sure to download the <strong>BoldGrid Backup Premium Extension</strong> from <a href="%1$s">BoldGrid Central</a> to gain access to more features!',
						'boldgrid-backup'
					),
					'https://www.boldgrid.com/central'
				) . '</p>',
			),
		);

		foreach ( $notices as $notice ) {
			if ( $notice['show'] ) {
				\Boldgrid\Library\Library\Notice::show( $notice['message'], $notice['id'] );
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

		return $url;
	}
}
