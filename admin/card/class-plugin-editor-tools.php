<?php
/**
 * PluginEditorTools class.
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
 * Class: Plugin_Editor_Tools
 *
 * This class is responsible for rendering the "Plugin Editor Tools"
 * card on this plugin's Premium Features Page.
 *
 * @since SINCEVERSION
 */
class Plugin_Editor_Tools extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_plugin_editor_tools';

		$this->title = esc_html__( 'Plugin Editor Tools', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-media-code"></span>';

		$this->footer = '
			<p>' .
			esc_html__( 'All the tools you would ever need to edit your plugins ( or ours ).', 'boldgrid-backup' ) .
			'</p>
			<p style="text-align:right;">
				<a href="#">' .
				esc_html__( 'Setup Guide' ) . '
				</a>
			</p>';
	}
}
