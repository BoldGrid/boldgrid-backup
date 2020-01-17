<?php
/**
 * Dreamobjects class.
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
 * Class: Dreamobjects
 *
 * This class is responsible for rendering the "Dream Objects" card
 * on this plugin's Premium Cards Page.
 *
 * @since 1.12.4
 */
class Dreamobjects extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_dreamobjects';

		$this->title = esc_html__( 'DreamObjects', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Do whatever it does that being part of DreamObjects is?', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<img class="dashimages" src="' . plugin_dir_url( __FILE__ ) . '../image/remote/dreamhost-logo.png"></img>';

		$this->features = [];

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
