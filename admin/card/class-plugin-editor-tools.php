<?php
/**
 * PluginEditorTools class.
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
 * Class: PluginEditorTools
 *
 * This class is responsible for rendering the "Plugin Editor Tools" 
 * card on this plugin's Premium Cards Page.
 *
 * @since 1.12.4
 */
class PluginEditorTools extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_plugin_editor_tools';

		$this->title = esc_html__( 'Plugin Editor Tools', 'boldgrid-backup' );

		$this->footer = esc_html__( 'All the tools you would ever need to edit your plugins ( or ours ).', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-media-code"></span>';

        $this->link = array(
		    "url" => "#",
		    "text" => "Setup Guide"
		);
	}
}
