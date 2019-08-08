<?php
/**
 * Backups class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: Backups
 *
 * This class is responsible for rendering the "Backups" card on the BoldGrid Backup dashboard.
 *
 * @since xxx
 */
class Backups extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->id = 'bgbkup_backups';

		$this->title = esc_html__( 'Backups', 'boldgrid-backup' );

		$this->subTitle = esc_html__( 'It\'s website insurance. Make sure you have a backup.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-vault"></span>';

		$this->features = [
			new \Boldgrid\Backup\Admin\Card\Feature\Scheduled_Backups(),
			new \Boldgrid\Backup\Admin\Card\Feature\Remote_Storage(),
		];
	}
}
