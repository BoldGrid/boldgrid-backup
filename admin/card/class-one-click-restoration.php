<?php
/**
 * One_Click_Restoration class.
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
 * Class: One_Click_Restoration.
 *
 * This class is responsible for rendering the "One Click Restoration" card.
 * on this plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class One_Click_Restoration extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_one_click_restoration';

		$this->title = esc_html__( 'One Click File Restorations', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-undo"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Restore a single file within the backup browser. Helpful when modifying individual files.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/individual-file-restorations/?source=one-click-restore';

		$video = 'https://www.youtube.com/embed/r2VCQ-9fQP8?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
			<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="Restore Files with One Click"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}
}
