<?php
/**
 * More_Central class.
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
 * Class: More_Central
 *
 * @since 1.11.0
 */
class More_Central extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$get_premium_url = $core->go_pro->get_premium_url( 'bgbkup-dashboard' );

		$this->icon = '<span class="dashicons boldgrid-icon"></span>';

		$this->title = esc_html__( 'More BoldGrid Central Features', 'boldgrid-backup' );

		$this->content = '<p>' . esc_html__( 'Unlock more features within BoldGrid Central, including Cloud WordPress Advanced Controls and Automated Website Speed Tests.', 'boldgrid-backup' ) . '</p>';

		$this->content .= '<p style="text-align:right;"><a href="' . esc_url( $get_premium_url ) . '" class="button button-primary boldgrid-orange">' . __( 'Get Premium', 'boldgrid-backup' ) . '</a></p>';
	}
}
