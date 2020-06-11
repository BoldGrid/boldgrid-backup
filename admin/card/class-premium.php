<?php
/**
 * Premium class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: Premium
 *
 * This class is responsible for rendering the "Premium" card on this plugin's dashboard.
 *
 * @since 1.11.0
 */
class Premium extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$api_key = apply_filters( 'Boldgrid\Library\License\getApiKey', '' ); // phpcs:ignore

		$this->id = 'bgbkup_get_premium';

		$this->icon = '<span class="dashicons dashicons-admin-network"></span>';

		$features = [];

		if ( empty( $api_key ) ) {
			$this->title = esc_html__( 'BoldGrid Central', 'boldgrid-backup' );

			$this->subTitle = esc_html__( 'All the tools and services you need to succeed.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

			$this->features = [
				new Feature\Cloud_Wordpress(),
				new Feature\Speed_Coach(),
				new Feature\Sign_Up(),
			];
		} elseif ( ! $core->config->get_is_premium() ) {
			$this->title = esc_html__( 'Enjoying your free account?', 'boldgrid-backup' );

			$this->subTitle = esc_html__( 'We hope so. There\'s more available by upgrading now!', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

			$this->features = [
				new Feature\More_Backup(),
				new Feature\More_Boldgrid(),
				new Feature\More_Central(),
			];
		} else {
			$this->title = esc_html__( 'BoldGrid Premium', 'boldgrid-backup' );

			$this->subTitle = esc_html__( 'Thank you for running BoldGrid Premium!', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

			$this->features = [
				new Feature\Central(),
			];
		}
	}
}
