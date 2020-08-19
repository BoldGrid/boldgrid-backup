<?php
/**
 * HistoricalVersions class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.13.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: Historical_Versions.
 *
 * This class is responsible for rendering the "Historical Versions" card on this.
 * plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class Historical_Versions extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_historical_versions';

		$this->title = esc_html__( 'Historical Versions', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-images-alt2"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Search through all backup archives for a particular, individual file and restore it.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/restore-historical-files/?source=historical-versions';

		$video = 'https://www.youtube.com/embed/wGda86tdScg?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
			<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="Restore Historical Versions of Files"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}
}
