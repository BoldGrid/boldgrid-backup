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
 * card on this plugin's Premium Cards Page.
 *
 * @since SINCEVERSION
 */
class Plugin_Editor_Tools extends \Boldgrid\Library\Library\Ui\PremiumFeatures\Card {
	/**
	 * Init.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		$this->id = 'bgbkup_plugin_editor_tools';

		$this->title = esc_html__( 'Plugin Editor Tools', 'boldgrid-backup' );

		$this->footer = esc_html__( 'All the tools you would ever need to edit your plugins ( or ours ).', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-media-code"></span>';

		$this->link = array(
			'url'  => '#',
			'text' => 'Setup Guide',
		);
	}
}
