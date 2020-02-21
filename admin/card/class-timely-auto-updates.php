<?php
/**
 * Timely Auto Updates class.
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
 * Class: Encryption
 *
 * This class is responsible for rendering the "Encryption" card on this plugin's Premium Features page.
 *
 * @since 1.13.0
 */
class Timely_Auto_Updates extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_timely_auto_updates';

		$this->title = esc_html__( 'Timely Auto Updates', 'boldgrid-backup' );

		$this->footer = '
			<p>' .
			esc_html__(
				'Gives you more control over when new updates are installed by WordPress\' Automatic Updates.',
			'boldgrid-backup' ) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/timely-auto-updates/?source=timely-auto-updates' );

		$this->links = '
			<a target="_blank" href="' . $url . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';

		$this->icon = '<span class="dashicons dashicons-clock"></span>';
	}
}
