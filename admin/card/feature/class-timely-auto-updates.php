<?php
/**
 * Timely_Auto_Updates class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.14.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Feature
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card\Feature;

/**
 * Class: Timely_Auto_Updates.
 *
 * This class is responsible for rendering the, "Timely Auto Updates" feature on the BoldGrid.
 * Backup Dashboard.
 *
 * @since 1.14.0
 */
class Timely_Auto_Updates extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.14.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-clock"></span>';

		$this->title = __( 'Timely Auto Updates', 'boldgrid-backup' );

		// Determine whether or not this feature is enabled.
		$auto_update_settings = $core->settings->get_setting( 'auto_update' );

		$is_enabled = ! empty( $auto_update_settings['timely-updates-enabled'] );

		if ( $is_enabled ) {
			$this->content = '<p>' . esc_html__( 'Timely Auto Updates are enabled!', 'boldgrid-backup' ) . '</p>';
		} else {
			$this->content  = '<p>' . esc_html__( 'By setting up Timely Auto Updates, you can have more control over when new updates are installed by WordPress.', 'boldgrid-backup' ) . '</p>';
			$this->content .= '<div class="notice notice-warning inline"><p>' . wp_kses(
				sprintf(
					// translators: 1 Opening anchor tag to "Auto Updates" settings page, 2 its closing tag.
					__( 'Timely Auto Updates are not enabled. %1$sFix this%2$s.', 'boldgrid-backup' ),
					'<a href="' . esc_url( $core->settings->get_settings_url( 'section_auto_updates' ) ) . '">',
					'</a>'
				),
				[ 'a' => [ 'href' => [] ] ]
			) . '</p></div>';
		}
	}
}
