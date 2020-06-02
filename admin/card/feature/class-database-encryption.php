<?php
/**
 * Database_Encryption class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.13.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card\Feature;

/**
 * Class: Database_Encryption
 *
 * @since 1.13.0
 */
class Database_Encryption extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->icon = '<img class="feature-icon" src="' . plugin_dir_url( BOLDGRID_BACKUP_PATH ) . 'boldgrid-backup/admin/image/db-lock-64.png" />';

		$this->title = esc_html__( 'Database Encryption', 'boldgrid-backup' );

		$this->content  = '<p>' . esc_html__( 'Secure your sensitive data with Database Encryption.', 'boldgrid-backup' ) . '</p>';
		$this->content .= '<div class="notice notice-warning inline"><p>' . wp_kses(
			sprintf(
				// translators: 1 An opening anchor tag to the Remote Storage settings, 2 its closing anchor tag.
				__( 'Database Encryption is not configured. %1$sFix this%2$s', 'boldgrid-backup' ),
				'<a href="' . esc_url( $core->settings->get_settings_url( 'section_security' ) ) . '">',
				'</a>'
			),
			[ 'a' => [ 'href' => [] ] ]
		) . '</p></div>';
	}
}
