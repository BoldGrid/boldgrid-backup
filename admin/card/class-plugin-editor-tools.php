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

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/plugin-editor-backup/?source=plugin-editor-tools' );

		$video = esc_url( 'admin.php?page=plugin-editor-tools-video&TB_iframe=true&width=700&height=420' );

		$this->links = '
			<a class="button thickbox" href=' . $video . '"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . $url . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';
	}

	/**
	 * Video Subpage.
	 *
	 * @since SINCEVERSION
	 */
	public function video_subpage() {
		wp_enqueue_style( 'boldgrid-backup-admin-hide-all' );
		wp_enqueue_style( 'bglib-ui-css' );
		wp_enqueue_script( 'bglib-ui-js' );
		wp_enqueue_script( 'bglib-sticky' );
		echo '<iframe width="711" height="400" src="https://www.youtube.com/embed/Nb0AFEXpE00?controls=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	}

	/**
	 * Add Submenus.
	 *
	 * @since SINCEVERSION
	 */
	public function add_submenus() {
		add_submenu_page(
			null,
			__( 'Plugin Editor Tools', 'boldgrid-backup' ),
			__( 'Plugin Editor Tools', 'boldgrid-backup' ),
			'administrator',
			'plugin-editor-tools-video',
			array(
				$this,
				'video_subpage',
			)
		);
	}
}
