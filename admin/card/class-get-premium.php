<?php
/**
 * Get Premium class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: GetPremium
 *
 * This class is responsible for rendering the "Get Premium" card on the BoldGrid Backup dashboard.
 *
 * @since xxx
 */
class GetPremium extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$api_key = apply_filters( 'Boldgrid\Library\License\getApiKey', '' );

		$this->id = 'bgbkup_get_premium';

		$this->icon = '<span class="dashicons dashicons-admin-network"></span>';

		$features = [];

		if ( empty( $api_key ) ) {
			$this->title = __( 'Sign up for BoldGrid Central!', 'boldgrid-backup' );

			$this->subTitle = __( 'We can help your website thrive in more ways than one.', 'boldgrid-backup' );

			$features = [
				new \Boldgrid\Backup\Admin\Card\Feature\CloudWordpress(),
				new \Boldgrid\Backup\Admin\Card\Feature\SpeedCoach(),
				new \Boldgrid\Backup\Admin\Card\Feature\SignUp(),
			];
		} elseif ( ! $core->config->get_is_premium() ) {
			$this->title = esc_html__( 'Enjoying your free account?', 'boldgrid-backup' );

			$this->subTitle = esc_html__( 'We hope so. There\'s more available by upgrading now!', 'boldgrid-backup' );

			$features = [
				new \Boldgrid\Backup\Admin\Card\Feature\MoreBackup(),
				new \Boldgrid\Backup\Admin\Card\Feature\MoreBoldgrid(),
				new \Boldgrid\Backup\Admin\Card\Feature\MoreCentral(),
			];
		} else {
			$this->title = esc_html__( 'BoldGrid Premium', 'boldgrid-backup' );

			$this->subTitle = esc_html__( 'Thank you for running BoldGrid Premium!', 'boldgrid-backup' );

			$features = [
				new \Boldgrid\Backup\Admin\Card\Feature\Central(),
			];
		}

		foreach ( $features as $feature ) {
			$feature->init();
			$this->footer .= $feature->print( false );
		}
	}
}
