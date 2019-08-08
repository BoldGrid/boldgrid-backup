<?php
/**
 * Scheduled_Backups class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card\Feature;

/**
 * Class: Scheduled_Backups
 *
 * This class is responsible for displaying the scheduled backups feature on the BoldGrid Backup
 * dashboard.
 *
 * @since xxx
 */
class Scheduled_Backups extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-clock"></span>';

		$this->title = esc_html__( 'Scheduled Backups', 'boldgrid-backup' );

		if ( $core->settings->has_scheduled_backups() ) {
			$cron         = new \Boldgrid\Backup\Admin\Cron();
			$backup_entry = $cron->get_entry( 'backup' );
			$next_runtime = $backup_entry->get_next_runtime();

			$this->content = '<p>' . wp_kses(
				sprintf(
					// Translators: 1 An opening span tag, 2 the date of the next backup, 3 its closing span tag.
					__( 'Next backup in: %1$s%2$s%3$s', 'boldgrid-backup' ),
					'<span class="bglib-feature-value" title="' . esc_attr( date( 'M j, Y h:i a', $next_runtime ) ) . '">',
					human_time_diff( time(), $next_runtime ),
					'</span>'
				),
				[
					'span' => [
						'class' => [],
						'title' => [],
					],
				]
			) . '</p>';
		} else {
			$this->content  = '<p>' . esc_html__( 'It\'s easy to forget to make a backup. Schedule automatic backups so they\'re made for you.', 'boldgrid-backup' ) . '</p>';
			$this->content .= '<div class="notice notice-error inline"><p>' . wp_kses(
				sprintf(
					// translators: 1 An opening anchor tag to the settings page, 2 its closing tag.
					__( 'Scheduled backups not configured. %1$sFix this%2$s.', 'boldgrid-backup' ),
					'<a href="' . esc_url( $core->settings->get_settings_url() ) . '">',
					'</a>'
				),
				[
					'a' => [
						'href' => [],
					],
				]
			) . '</p></div>';
		}
	}
}
