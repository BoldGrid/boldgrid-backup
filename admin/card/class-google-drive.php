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
 * Class: Google Drive
 *
 * This class is responsible for rendering the "Google drive" card on this
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
			'boldgrid-backup' ) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/auto-backup-to-google-drive/?source=google-drive' );

		$this->links = '
			<a target="_blank" href=" ' . $url . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}
}
