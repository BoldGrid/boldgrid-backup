<?php
/**
 * Speed_Coach class.
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
 * Class: Speed_Coach
 *
 * @since xxx
 */
class Speed_Coach extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->icon = '<span class="dashicons dashicons-chart-line"></span>';

		$this->title = esc_html__( 'Speed Coach', 'boldgrid-backup' );

		$this->content = '<p>' . esc_html__( 'Speed Coach, our automated website speed and usability tool, provides a customized report of actionable steps that will increase both your website\'s speed and usability.', 'boldgrid-backup' ) . '</p>';
	}
}
