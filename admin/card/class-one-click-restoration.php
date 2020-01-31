<?php
/**
 * One_Click_Restoration class.
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
 * Class: One_Click_Restoration
 *
 * This class is responsible for rendering the "One Click Restoration" card
 * on this plugin's Premium Features Page.
 *
 * @since SINCEVERSION
 */
class One_Click_Restoration extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
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

		$this->links = '
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';
	}
}
