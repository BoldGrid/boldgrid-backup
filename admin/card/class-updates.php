<?php
/**
 * Updates class.
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
 * Class: Updates
 *
 * This class is responsible for rendering the "Update Management" card on the BoldGrid Backup
 * dashboard.
 *
 * @since xxx
 */
class Updates extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->id = 'bgbkup_updates';

		$this->title = esc_html__( 'Update Management', 'boldgrid-backup' );

		$this->subTitle = esc_html__( 'Keep everything tidy and up to date.', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-plugins-checked"></span>';

		$features = [
			new \Boldgrid\Backup\Admin\Feature\Versions(),
			new \Boldgrid\Backup\Admin\Feature\AutoRollback(),
			new \Boldgrid\Backup\Admin\Feature\AutoUpdateBackup(),
		];

		foreach ( $features as $feature ) {
			$feature->init();
			$this->footer .= $feature->print( false );
		}
	}
}
