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
class Historical_Versions extends \Boldgrid\Library\Library\Ui\PremiumFeatures\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_historical_versions';

		$this->title = esc_html__( 'Historical Versions', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Somehow this is different than update history?', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-images-alt2"></span>';

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
