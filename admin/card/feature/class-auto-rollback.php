<?php
/**
 * Auto_Rollback class.
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
 * Class: Auto_Rollback
 *
 * This class is responsible for rendering the "Auto Rollback" feature on this plugin's dashboard.
 *
 * @since 1.11.0
 */
class Auto_Rollback extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-controls-back"></span>';

		$this->title = __( 'Auto Rollback', 'boldgrid-backup' );

		if ( $core->auto_rollback->is_enabled() ) {
			$this->content = '<p>' . esc_html__( 'Auto Rollback is enabled!', 'boldgrid-backup' ) . '</p>';
		} else {
			$this->content  = '<p>' . esc_html__( 'With Auto Rollback, we can help fix your site if anything goes wrong while performing updates.', 'boldgrid-backup' ) . '</p>';
			$this->content .= '<div class="notice notice-error inline"><p>' .
				wp_kses(
					sprintf(
						// translators: 1 An opening anchor tag to the "Auto Updates" settings page, 2 its closing tag.
						__( 'Auto Rollback is not enabled. %1$sFix this%2$s.', 'boldgrid-backup' ),
						'<a href="' . esc_url( $core->settings->get_settings_url( 'section_auto_updates' ) ) . '">',
						'</a>'
					),
					[ 'a' => [ 'href' => [] ] ]
				) . '</p></div>';
		}
	}
}
