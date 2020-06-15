<?php
/**
 * Dream_Objects class.
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
 * Class: Dream_Objects.
 *
 * This class is responsible for rendering the "Dream Objects" card.
 * on this plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class Dream_Objects extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_dream_objects';

		$this->title = esc_html__( 'DreamObjects', 'boldgrid-backup' );

		$this->footer = '
			<p>' .
			esc_html__(
				'Safely store backups in the cloud via DreamObjects by DreamHost. Compatible with automated backups feature.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/dreamobjects-storage/?source=dreamobjects';

		$video = 'https://www.youtube.com/embed/fJXnMq5JYi8?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
		<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="Store Backups on DreamHost DreamObjects"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/dreamhost-logo.png"></img>';
	}
}
