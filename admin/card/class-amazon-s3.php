<?php
/**
 * Amazon_S3 class.
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
 * Class: Amazon_S3
 *
 * This class is responsible for rendering the "Amazon S3" card on this plugin's Premium Features page.
 *
 * @since 1.13.0
 */
class Amazon_S3 extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_amazon_s3';

		$this->title = esc_html__( 'Amazon S3', 'boldgrid-backup' );

		$this->footer = '
			<p>' .
				esc_html__(
					'Safely store backups in the cloud via Amazon S3. Compatible with automated remote backups feature.',
				'boldgrid-backup' ) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/backup-wordpress-to-amazon-s3/?source=amazon-s3' );

		$this->links = '
			<a target="_blank" href="' . $url . '">' .
				esc_html__( 'Setup Guide' ) . '
			</a>';

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/amazon-s3-logo.png"></img>';
	}
}
