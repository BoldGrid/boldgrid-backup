<?php
/**
 * Go Pro class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Go Pro Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Go_Pro {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Generic upgrade link.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $url = 'https://boldgrid.com/update-backup';

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
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
	 * @since 1.5.4
	 */
	public function admin_notice_setup() {

		// If the premium plugin is installed and all is good, abort!
		if( $this->core->config->is_premium_done ) {
			return;
		}

		// Check user role.
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// @todo Proper way to show this type of notice? Also, needs to be dismissable.
		if( $this->core->config->get_is_premium() && $this->core->config->is_premium_installed ) {
			$message = sprintf(
				__( 'You have a <strong>Premium BoldGrid Connect Key</strong> and you have the <strong>BoldGrid Backup Premium Extension installed</strong>. Please go to your <a href="%1$s">plugins page</a> and activate your premium extension!', 'boldgrid-backup' ),
				admin_url( 'plugins.php' )
			);
		}

		// @todo Proper way to show this type of notice? Also, needs to be dismissable.
		if( ! $this->core->config->get_is_premium() && $this->core->config->is_premium_active ) {
			$message = sprintf(
				__( 'Thank you for activating the <strong>BoldGrid Backup Premium Extension</strong>! Before you can begin using all of the premium features, please visit <a href="%1$s" target="_blank">BoldGrid Central</a> and upgrade your BoldGrid Connect Key.', 'boldgrid-backup' ),
				$this->url
			);
		}

		// @todo Proper way to show this type of notice? Also, needs to be dismissable.
		if( $this->core->config->get_is_premium() && ! $this->core->config->is_premium_installed ) {
			$message = sprintf(
				__( 'Hello there! We see that you have a <strong>Premium BoldGrid Connect Key</strong> and you have the <strong>BoldGrid Backup Plugin</strong> activated! Be sure to download the <strong>BoldGrid Backup Premium Extension</strong> from <a href="%1$s">BoldGrid Central</a> to gain access to more features!', 'boldgrid-backup' ),
				'https://www.boldgrid.com/central'
			);
		}

		if( isset( $message ) ) {
			$this->core->notice->boldgrid_backup_notice( $message, 'notice notice-warning is-dismissible' );
		}
	}

	/**
	 * Get a "Get Premium" button.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $url
	 * @param  string $text
	 * @return string
	 */
	public function get_premium_button( $url = 'https://boldgrid.com/update-backup', $text = 'Get Premium' ) {
		return sprintf( '
			<a href="%1$s" class="button button-success" target="_blank">%2$s</a>',
			esc_url( $url ),
			$text
		);
	}
}