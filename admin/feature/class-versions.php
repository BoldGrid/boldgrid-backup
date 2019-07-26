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
 * Class: Versions
 *
 * @since xxx
 */
class Versions extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 *
	 */
	public function init() {
		global $wp_version;

		$data = wp_get_update_data();

		$has_updates = ! empty( $data['counts']['plugins'] ) || ! empty( $data['counts']['themes'] ) || ! empty( $data['counts']['wordpress'] );

		$this->icon = '<span class="dashicons dashicons-wordpress"></span>';

		$this->title = __( 'WordPress, Plugins, & Theme Version', 'boldgrid-backup' );

		if ( $has_updates ) {
			$this->content = '<p>' . wp_kses(
				sprintf(
					__( 'The following updates are available: %1$s', 'boldgrid-backup' ),
					$data['title']
				),
				[]
			) . '</p>';

			$this->content .= '<div class="notice notice-error inline"><p>' . wp_kses(
				sprintf(
					__( 'Not everything is up to date. %1$sFix this%2$s.', 'boldgrid-backup' ),
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>',
				),
				[ 'a' => [ 'href' => [] ] ]
			) . '</p></div>';
		} else {
			$this->content = '<p>' . esc_html( 'Everything is up to date!', 'boldgrid-backup' ) . '</p>';
		}
	}
}
