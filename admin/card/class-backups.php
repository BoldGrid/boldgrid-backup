<?php
/**
 * Backups class.
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
 * Class: Backups.
 *
 * This class is responsible for rendering the "Backups" card on this plugin's dashboard.
 *
 * @since 1.11.0
 */
class Backups extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$this->id = 'bgbkup_backups';

		$this->title = esc_html__( 'Backups', 'boldgrid-backup' );

		$this->subTitle = esc_html__( 'It\'s website insurance. Make sure you have a backup.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		$this->icon = '<span class="dashicons dashicons-vault"></span>';

		$this->features = array(
			new Feature\Scheduled_Backups(),
			new Feature\Remote_Storage(),
		);
		if ( ! get_option( 'boldgrid_backup_settings' )['encrypt_db'] ) {
			$this->features[] = new Feature\Database_Encryption();
		}
	}
}
