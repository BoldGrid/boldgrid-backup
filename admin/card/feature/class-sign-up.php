<?php
/**
 * Sign_Up class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
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
 * @since 1.11.0
 */
class Sign_Up extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$new_key_url = \Boldgrid\Library\Library\Key\PostNewKey::getCentralUrl( admin_url( 'admin.php?page=boldgrid-backup-dashboard' ) );

		$this->icon = '<span class="dashicons dashicons-clipboard"></span>';

		$this->content = '<p>' . esc_html__( 'There’s more waiting for you in BoldGrid Central. Download the full-featured community versions of ALL our plugins for FREE. It’s just a click away.', 'boldgrid-backup' ) . '</p>';

		$this->content .= '<p style="text-align:right;"><a href="' . esc_url( $new_key_url ) . '" class="button button-primary boldgrid-orange">' . __( 'Sign Up for Free!', 'boldgrid-backup' ) . '</a></p>';
	}
}
