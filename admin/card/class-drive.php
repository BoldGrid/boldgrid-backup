<?php
/**
 * Drive class.
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
 * Class: Drive
 *
 * This class is responsible for rendering the "Google drive" card on this
 * plugin's Premium Cards Page.
 *
 * @since 1.12.4
 */
class Drive extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_drive';

		$this->title = esc_html__( 'Google Drive', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Automatically store backups on Google Drive.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<img class="dashimages" src="' . plugin_dir_url( __FILE__ ) . '../image/remote/google-drive.png"></img>';

		$this->link = array(
			'url'  => 'https://www.boldgrid.com/support/total-upkeep-backup-plugin-product-guide/using-a-wordpress-plugin-to-backup-to-google-drive/',
			'text' => 'Setup Guide',
		);
	}
}
