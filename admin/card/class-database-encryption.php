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
 * This class is responsible for rendering the "Encryption" card on this plugin's dashboard.
 *
 * @since SINCEVERSION
 */
class Database_Encryption extends \Boldgrid\Library\Library\Ui\PremiumFeatures\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_database_encryption';

		$this->title = esc_html__( 'Database Encryption', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Provide higher security for confidential database.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<img class="dashimages" src="' . plugin_dir_url( __FILE__ ) . '../image/db-lock-64.png" />';

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
