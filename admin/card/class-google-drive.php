<?php
/**
 * Google Drive class.
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
 * Class: Google Drive.
 *
 * This class is responsible for rendering the "Google drive" card on this.
 * plugin's Premium Features Page.
 *
 * @since 1.13.0
 */
class Google_Drive extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_google_drive';

		$this->title = esc_html__( 'Google Drive', 'boldgrid-backup' );

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/remote/google-drive.png"></img>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Keep your backup archives safe and secure with remote, automated backups to Google Drive.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/auto-backup-to-google-drive/?source=google-drive' );

		$video = esc_url( 'admin.php?page=google-drive-video&TB_iframe=true&width=700&height=420' );

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

		echo '<iframe width="711" height="400" src="https://www.youtube.com/embed/p6I_xxo4TLo?controls=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	}

	/**
	 * Add Submenus.
	 *
	 * @since SINCEVERSION
	 */
	public function add_submenus() {
		add_submenu_page(
			null,
			__( 'Google Drive', 'boldgrid-backup' ),
			__( 'DreamObjects', 'boldgrid-backup' ),
			'administrator',
			'google-drive-video',
			array(
				$this,
				'video_subpage',
			)
		);
	}
}
