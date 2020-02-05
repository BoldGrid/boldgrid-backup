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
 * Class: One_Click_Restoration
 *
 * This class is responsible for rendering the "One Click Restoration" card
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
			'boldgrid-backup' ) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/individual-file-restorations/' );

		$this->links = '
			<p style="text-align:right;">
				<a target="_blank" href="' . $url . '">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';
	}
}
