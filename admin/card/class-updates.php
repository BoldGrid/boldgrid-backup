<?php
/**
 * Updates class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
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
 * This class is responsible for rendering the "Update Management" card on this plugin's dashboard.
 *
 * @since 1.11.0
 */
class Updates extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {

		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->id = 'bgbkup_updates';

		$this->title = esc_html__( 'Update Management', 'boldgrid-backup' );

		$this->subTitle = esc_html__( 'Keep everything tidy and up to date.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-plugins-checked"></span>';

		$this->features = [
			new Feature\Versions(),
			new Feature\Auto_Rollback(),
			new Feature\Auto_Update_Backup(),
		];
		if ( $core->config->get_is_premium() ) {
			$this->features[] = new Feature\Timely_Auto_Updates();
		}
	}
}
