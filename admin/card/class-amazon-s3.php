<?php
/**
 * Amazon_S3 class.
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
 * Class: Amazon_S3
 *
 * This class is responsible for rendering the "Amazon S3" card on this plugin's Premium Features page.
 *
 * @since SINCEVERSION
 */
class Amazon_S3 extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
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

		$this->links = '
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/amazon-s3-logo.png"></img>';
	}
}
