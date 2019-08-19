<?php
/**
 * My Backups class.
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
 * Class: Versions
 *
 * @since 1.11.0
 */
class Versions extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 *
	 */
	public function init() {
		$data = wp_get_update_data();

		$has_updates = ! empty( $data['counts']['plugins'] ) || ! empty( $data['counts']['themes'] ) || ! empty( $data['counts']['wordpress'] );

		$this->icon = '<span class="dashicons dashicons-wordpress"></span>';

		$this->title = esc_html__( 'WordPress, Plugins, & Theme Version', 'boldgrid-backup' );

		if ( $has_updates ) {
			$this->content = '<p>' . wp_kses(
				sprintf(
					// translators: 1 A description of what updates are available (such as plugins, themes, or core).
					__( 'The following updates are available: %1$s', 'boldgrid-backup' ),
					$data['title']
				),
				[]
			) . '</p>';

			$this->content .= '<div class="notice notice-error inline"><p>' .
				wp_kses(
					sprintf(
						// translators: 1 An opening anchor tag to the update-core.php page, 2 its closing tag.
						__( 'Not everything is up to date. %1$sFix this%2$s.', 'boldgrid-backup' ),
						'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
						'</a>'
					),
					[
						'a' => [
							'href' => [],
						],
					]
				) .
				'</p></div>';
		} else {
			$this->content = '<p>' . esc_html__( 'Everything is up to date!', 'boldgrid-backup' ) . '</p>';
		}
	}
}
