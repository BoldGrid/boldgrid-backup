<?php
/**
 * Database Encryption class.
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
 * Class: Encryption.
 *
 * This class is responsible for rendering the "Encryption" card on this plugin's Premium Features page.
 *
 * @since 1.13.0
 */
class Database_Encryption extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->id = 'bgbkup_database_encryption';

		$this->title = esc_html__( 'Database Encryption', 'boldgrid-backup' );

		$this->footer = '
			<p>' .
			esc_html__(
				'Provides another level of protection by preventing unauthorized access to your database backup archives.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = 'https://www.boldgrid.com/support/total-upkeep/encrypt-database-backups/?source=encrypt-database-backups';

		$video = 'https://www.youtube.com/embed/Pwxous6_LKg?controls=1&autoplay=1&modestbranding=1&width=560&height=315&KeepThis=true&TB_iframe=true';

		$this->links = '
		<a class="video button thickbox" href=' . esc_url( $video ) . '" data-id="' . $this->id . '" title="Encrypt Your Database Backups"><span class="dashicons dashicons-video-alt3"></span>' .
			esc_html__( 'Learn More' ) .
			'<a target="_blank" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Setup Guide' ) . '
			</a>';

		$this->icon = '<img src="' . plugin_dir_url( __FILE__ ) . '../image/db-lock-64.png" />';
	}
}
