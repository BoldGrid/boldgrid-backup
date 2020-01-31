<?php
/**
 * Google Drive class.
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
 * Class: Google Drive
 *
 * This class is responsible for rendering the "Google drive" card on this
 * plugin's Premium Features Page.
 *
 * @since SINCEVERSION
 */
class Google_Drive extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_google_drive';

		$this->title = esc_html__( 'Google Drive', 'boldgrid-backup' );

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/google-drive.png"></img>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Keep your backup archives safe and secure with remote, automated backups to Google Drive.',
			'boldgrid-backup' ) .
			'</p>';

		$this->links = '
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';
	}
}
