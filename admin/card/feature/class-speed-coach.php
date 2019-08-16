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

		$this->content = '<p>' . esc_html__( 'A faster website means happier visitors and higher rankings on the search engines. Simply type in your website’s URL and receive detailed advice on making your site lightning fast.', 'boldgrid-backup' ) . '</p>';
	}
}
