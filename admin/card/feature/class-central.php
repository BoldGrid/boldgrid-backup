<?php
/**
 * Central class.
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
 * Class: Central
 *
 * This class is responsible for initializing a BoldGrid Central "feature" for use within a card.
 *
 * @since 1.11.0
 */
class Central extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$reseller = new \Boldgrid\Library\Library\Reseller();

		$this->icon = '<span class="dashicons boldgrid-icon"></span>';

		$this->title = esc_html__( 'BoldGrid Central', 'boldgrid-backup' );

		$this->content = '<p>' . esc_html__( 'Manage your account, Run Automated Website Speed Tests, and more within BoldGrid Central.', 'boldgrid-backup' ) . '</p>';

		$this->content .= '<p style="text-align:right;"><a href="' . esc_url( $reseller->centralUrl ) . '">' . esc_html__( 'BoldGrid Central Login', 'boldgrid-backup' ) . '</a></p>'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	}
}
