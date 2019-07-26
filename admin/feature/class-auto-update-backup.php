<?php
/**
 * AutoUpdateBackup class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Feature
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Feature;

/**
 * Class: AutoUpdateBackup
 *
 * This class is responsible for rendering the, "Auto Backup Before Updates" feature on the BoldGrid
 * Backup Dashboard.
 *
 * @since xxx
 */
class AutoUpdateBackup extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-update-alt"></span>';

		$this->title = __( 'Auto Backup Before Updates', 'boldgrid-backup' );

		// Determine whether or not this feature is enabled.
		$setting_value = $core->settings->get_setting( 'auto_backup' );
		$is_enabled    = ! empty( $setting_value );

		if ( $is_enabled ) {
			$this->content = '<p>' . esc_html__( 'Auto Backup Before Updates is enabled!', 'boldgrid-backup' ) . '</p>';
		} else {
			$this->content = '<p>' . esc_html__( 'When Auto Backup is enabled, we will backup your site before any auto-updates occur.', 'boldgrid-backup' ) . '</p>';
			$this->content .= '<div class="notice notice-error inline"><p>' . wp_kses(
				sprintf(
					__( 'Auto Backup Before Updates is not enabled. %1$sFix this%2$s.', 'boldgrid-backup' ),
					'<a href="' . esc_url( $core->settings->get_settings_url( 'section_auto_updates' ) ) . '">',
					'</a>'
				),
				[ 'a' => [ 'href' => [] ] ]
			) . '</p></div>';
		}
	}
}
