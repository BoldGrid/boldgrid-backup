<?php
/**
 * My Backups class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Feature;

/**
 * Class: RemoteStorage
 *
 * @since xxx
 */
class RemoteStorage extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 *
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<span class="dashicons dashicons-networking"></span>';

		$this->title = __( 'Remote Storage', 'boldgrid-backup' );

		if ( $core->settings->has_remote_configured() ) {
			$storage_locations = $core->remote->get_enabled( 'title' );

			$this->content = '<p>' . wp_kses(
				sprintf(
					__( 'Backups saved to: %1$s%2$s%3$s', 'boldgrid-bakcup' ),
					'<span class="bglib-feature-value">',
					esc_html( implode( ', ', $storage_locations ) ),
					'</span>'
				),
				[ 'span' => [ 'class' => [] ] ]
			) .	'</p>';
		} else {
			$this->content = '<p>' . esc_html__( 'Don\'t put all of your eggs in one basket! Store your backups remotely.', 'boldgrid-backup' ) . '</p>';
			$this->content .= '<div class="notice notice-error inline"><p>' . wp_kses(
				sprintf(
					__( 'Remote storage is not configured. %1$sFix this%2$s', 'boldgrid-backup' ),
					'<a href="' . esc_url( $core->settings->get_settings_url( 'section_storage' ) ) . '">',
					'</a>'
				),
				[ 'a' => [ 'href' => [] ] ]
			) . '</p></div>';
		}
	}
}
