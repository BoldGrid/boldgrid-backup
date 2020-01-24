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
			esc_html__( 'Provide higher security for confidential database.', 'boldgrid-backup' ) .
			'</p>
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';

		$this->icon = '<img class="dashimages" src="' . plugin_dir_url( __FILE__ ) . '../image/db-lock-64.png" />';
	}
}
