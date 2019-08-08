<?php
/**
 * Sign_Up class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card\Feature;

/**
 * Class: Sign_Up
 *
 * @since xxx
 */
class Sign_Up extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$new_key_url = \Boldgrid\Library\Library\Key\PostNewKey::getCentralUrl( admin_url( 'admin.php?page=boldgrid-backup-dashboard' ) );

		$this->icon = '<span class="dashicons dashicons-clipboard"></span>';

		$this->content = '<p>' . esc_html__( 'Enhance your site even more by signing up for BoldGrid Central and get access to additional tools!', 'boldgrid-backup' ) . '</p>';

		$this->content .= '<p style="text-align:right;"><a href="' . esc_url( $new_key_url ) . '" class="button button-primary boldgrid-orange">' . __( 'Sign Up!', 'boldgrid-backup' ) . '</a></p>';
	}
}
