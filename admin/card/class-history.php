<?php
/**
 * History class.
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
 * Class: History.
 *
 * This class is responsible for rendering the "History" card on this plugin's.
 * Premium Features Page.
 *
 * @since 1.13.0
 */
class History extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_history';

		$this->title = esc_html__( 'Update History', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-media-text"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'View a running log of significant actions to your WordPress site, including which users updated a plugin, theme, or WordPress itself.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/backup-changed-files-history/?source=update-history';

		$video = 'https://www.youtube.com/embed/kaeb30pYPYU?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
			<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="View Site Update History"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}
}
