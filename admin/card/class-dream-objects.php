<?php
/**
 * Dream_Objects class.
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
 * Class: Dream_Objects
 *
 * This class is responsible for rendering the "Dream Objects" card
 * on this plugin's Premium Features Page.
 *
 * @since SINCEVERSION
 */
class Dream_Objects extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_dream_objects';

		$this->title = esc_html__( 'DreamObjects', 'boldgrid-backup' );

		$this->footer = '
			<p>' .
			esc_html__( 'Do whatever it does that being part of DreamObjects is?', 'boldgrid-backup' ) .
			'</p>
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/dreamhost-logo.png"></img>';

		$this->features = [];
	}
}
