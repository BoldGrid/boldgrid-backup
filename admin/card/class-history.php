<?php
/**
 * History class.
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
 * Class: History
 *
 * This class is responsible for rendering the "History" card on this plugin's
 * Premium Features Page.
 *
 * @since 1.13.0
 */
class History extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_history';

		$this->title = esc_html__( 'Update History', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-media-text"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Search for all files modified within a certain time period. You can also look for other versions of that file within your backups.',
			'boldgrid-backup' ) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/backup-changed-files-history/' );

		$this->links = '
			<p style="text-align:right;">
				<a target="_blank" href="' . $url . '">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';
	}
}
