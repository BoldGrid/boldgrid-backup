<?php
/**
 * Scheduled_Backups class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
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
 * This class is responsible for displaying the scheduled backups feature on this plugin's dashboard.
 *
 * @since 1.11.0
 */
class Scheduled_Backups extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-clock"></span>';

		$this->title = esc_html__( 'Scheduled Backups', 'boldgrid-backup' );

		$cron         = new \Boldgrid\Backup\Admin\Cron();
		$backup_entry = $cron->get_entry( 'backup' );

		/*
		 * If a user does not have backups scheduled, suggest they schedule them.
		 *
		 * As of 1.11.4, in addition to checking if a schedule is saved in the settings, we also ensure
		 * that the backup cron can be found. We need to check for the scenario in which the user has
		 * manually deleted the cron entry yet the settings say backups are scheduled.
		 *
		 * In some cases where the settings say backups are scheduled but the cron doesn't actually
		 * exist, false positives may be given or errors triggered. We may want to explore the idea
		 * of showing an admin notice. For now, if this scenario exists, the dashboard will tell the
		 * user to schedule backups, and resaving the settings will resave the cron entry.
		 */
		if ( $core->settings->has_scheduled_backups() && $backup_entry->is_set() ) {
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
					__( 'Scheduled Backups not configured. %1$sFix this%2$s.', 'boldgrid-backup' ),
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
