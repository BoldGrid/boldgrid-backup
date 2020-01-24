<?php
/**
 * HistoricalVersions class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: Historical_Versions
 *
 * This class is responsible for rendering the "Historical Versions" card on this
 * plugin's Premium Features Page.
 *
 * @since SINCEVERSION
 */
class Historical_Versions extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_historical_versions';

		$this->title = esc_html__( 'Historical Versions', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-images-alt2"></span>';

		$this->footer = '
			<p>' .
			esc_html__( 'Somehow this is different than update history?', 'boldgrid-backup' ) .
			'</p>
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';
	}
}
