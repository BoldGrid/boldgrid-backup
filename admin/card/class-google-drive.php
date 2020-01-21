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
 * plugin's Premium Cards Page.
 *
 * @since SINCEVERSION
 */
class Google_Drive extends \Boldgrid\Library\Library\Ui\PremiumFeatures\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_google_drive';

		$this->title = esc_html__( 'Google Drive', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Automatically store backups on Google Drive.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<img class="dashimages" src="' . plugin_dir_url( __FILE__ ) . '../image/remote/google-drive.png"></img>';

		$this->link = array(
			'url'  => 'https://www.boldgrid.com/support/total-upkeep-backup-plugin-product-guide/using-a-wordpress-plugin-to-backup-to-google-drive/',
			'text' => 'Setup Guide',
		);
	}
}
