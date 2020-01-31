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
class History extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_history';

		$this->title = esc_html__( 'Update History', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-media-text"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Search for all files modified within a certain time period.You can also look for other versions of that file within your backups.',
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
