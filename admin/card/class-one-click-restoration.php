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
class One_Click_Restoration extends \Boldgrid\Library\Library\Ui\PremiumFeatures\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_one_click_restoration';

		$this->title = esc_html__( 'One Click File Restorations', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Restore Backup files quickly and easily.', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-undo"></span>';

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
