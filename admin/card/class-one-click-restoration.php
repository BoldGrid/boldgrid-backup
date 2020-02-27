<?php
/**
 * One_Click_Restoration class.
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
 * Class: One_Click_Restoration.
 *
 * This class is responsible for rendering the "One Click Restoration" card.
 * on this plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class One_Click_Restoration extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_one_click_restoration';

		$this->title = esc_html__( 'One Click File Restorations', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-undo"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Restore a single file within the backup browser. Helpful when modifying individual files.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/individual-file-restorations/?source=one-click-restore' );

		$video = esc_url( 'admin.php?page=one-click-restoration-video&TB_iframe=true&width=700&height=420' );

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

		echo '<iframe width="711" height="400" src="https://www.youtube.com/embed/r2VCQ-9fQP8?controls=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	}

	/**
	 * Add Submenus.
	 *
	 * @since SINCEVERSION
	 */
	public function add_submenus() {
		add_submenu_page(
			null,
			__( 'One Click File Restorations', 'boldgrid-backup' ),
			__( 'One Click File Restorations', 'boldgrid-backup' ),
			'administrator',
			'one-click-restoration-video',
			array(
				$this,
				'video_subpage',
			)
		);
	}
}
