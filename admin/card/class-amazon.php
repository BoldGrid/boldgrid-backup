<?php
/**
 * Amazon class.
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
 * Class: Amazon
 *
 * This class is responsible for rendering the "Amazon S3" card on this plugin's Premium Cards page.
 *
 * @since 1.12.4
 */
class Amazon extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_amazon';

		$this->title = esc_html__( 'Amazon S3', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Automatically store backups on Amazon S3 Storage.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<img class="dashimages" src="' . plugin_dir_url( __FILE__ ) . '../image/remote/amazon-s3-logo.png"></img>';

		$this->link = array(
			'url'  => 'https://www.boldgrid.com/support/total-upkeep-backup-plugin-product-guide/how-do-i-upload-my-wordpress-backup-to-amazon-s3/',
			'text' => 'Setup Guide',
		);
	}
}
