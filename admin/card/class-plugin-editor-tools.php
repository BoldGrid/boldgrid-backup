<?php
/**
 * PluginEditorTools class.
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
 * Class: Plugin_Editor_Tools.
 *
 * This class is responsible for rendering the "Plugin Editor Tools".
 * card on this plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class Plugin_Editor_Tools extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_plugin_editor_tools';

		$this->title = esc_html__( 'Plugin Editor Tools', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-media-code"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'When using the WordPress Plugin Editor, Total Upkeep Premium will save a copy of the file in case you need to undo any changes.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/plugin-editor-backup/?source=plugin-editor-tools';

		$video = 'https://www.youtube.com/embed/Nb0AFEXpE00?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
			<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="Save copies of Plugin Files from the Plugin Editor"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}
}
