<?php
/**
 * Database Encryption class.
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
 * Class: Encryption
 *
 * This class is responsible for rendering the "Encryption" card on this plugin's Premium Features page.
 *
 * @since SINCEVERSION
 */
class Database_Encryption extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_database_encryption';

		$this->title = esc_html__( 'Database Encryption', 'boldgrid-backup' );

		$this->footer = '
			<p>' .
			esc_html__(
				'Provides another level of protection by preventing unauthorized access to your database backup archives.',
			'boldgrid-backup' ) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/encrypt-database-backups/' );

		$this->links = '
			<p style="text-align:right;">
				<a target="_blank" href="' . $url . '">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/db-lock-64.png" />';
	}
}
