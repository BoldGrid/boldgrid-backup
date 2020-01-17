<?php
/**
 * Encryption class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.12.4
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
 * @since 1.12.4
 */
class Encryption extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_encryption';

		$this->title = esc_html__( 'Database Encryption', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Provide higher security for confidential database.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-products"></span>';

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
