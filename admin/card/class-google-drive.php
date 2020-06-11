<?php
/**
 * Google Drive class.
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
 * Class: Google Drive.
 *
 * This class is responsible for rendering the "Google drive" card on this.
 * plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class Google_Drive extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_google_drive';

		$this->title = esc_html__( 'Google Drive', 'boldgrid-backup' );

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/google-drive.png"></img>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Keep your backup archives safe and secure with remote, automated backups to Google Drive.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/auto-backup-to-google-drive/?source=google-drive';

		$video = 'https://www.youtube.com/embed/p6I_xxo4TLo?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
			<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="Store Backups on Google Drive" ><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}
}
