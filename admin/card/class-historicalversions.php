<?php
/**
 * HistoricalVersions class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.12.4
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: HistoricalVersions
 *
 * This class is responsible for rendering the "Historical Versions" card on this
 * plugin's Premium Cards Page.
 *
 * @since 1.12.4
 */
class HistoricalVersions extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_historical_versions';

		$this->title = esc_html__( 'Historical Versions', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Somehow this is different than update history?', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-images-alt2"></span>';

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
