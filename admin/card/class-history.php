<?php
/**
 * History class.
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
 * Class: History
 *
 * This class is responsible for rendering the "History" card on this plugin's
 * Premium Features Page.
 *
 * @since SINCEVERSION
 */
class History extends \Boldgrid\Library\Library\Ui\PremiumFeatures\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_history';

		$this->title = esc_html__( 'Update History', 'boldgrid-backup' );

		$this->footer = esc_html__( 'See detailed history of all updates.', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-media-text"></span>';

		$this->link = array(
			'url'  => 'https://www.boldgrid.com/support/total-upkeep-backup-plugin-product-guide/how-to-use-the-history-in-boldgrid-backup-premium/',
			'text' => 'Setup Guide',
		);
	}
}
