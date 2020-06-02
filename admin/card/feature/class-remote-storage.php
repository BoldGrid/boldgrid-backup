<?php
/**
 * Remote_Storage class.
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
 * Class: Remote_Storage
 *
 * @since 1.11.0
 */
class Remote_Storage extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-networking"></span>';

		$this->title = esc_html__( 'Remote Storage', 'boldgrid-backup' );

		if ( $core->settings->has_remote_configured() ) {
			$storage_locations = $core->remote->get_enabled( 'title' );

			$this->content = '<p>' .
				wp_kses(
					sprintf(
						// translators: 1 An opening span tag, A list of remote backup storage locations (csv), its closing span tag.
						__( 'Backups saved to: %1$s%2$s%3$s', 'boldgrid-bakcup' ),
						'<span class="bglib-feature-value">',
						esc_html( implode( ', ', $storage_locations ) ),
						'</span>'
					),
					[
						'span' => [
							'class' => [],
						],
					]
				) .
				'</p>';
		} else {
			$this->content  = '<p>' . esc_html__( 'Don\'t put all of your eggs in one basket! Store your backups remotely.', 'boldgrid-backup' ) . '</p>';
			$this->content .= '<div class="notice notice-error inline"><p>' . wp_kses(
				sprintf(
					// translators: 1 An opening anchor tag to the Remote Storage settings, 2 its closing anchor tag.
					__( 'Remote Storage is not configured. %1$sFix this%2$s', 'boldgrid-backup' ),
					'<a href="' . esc_url( $core->settings->get_settings_url( 'section_storage' ) ) . '">',
					'</a>'
				),
				[ 'a' => [ 'href' => [] ] ]
			) . '</p></div>';
		}
	}
}
