<?php
/**
 * File: connect-key.php
 *
 * Show Connect Key status.
 *
 * @link https://www.boldgrid.com
 * @since      1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

/* phpcs:disable WordPress.NamingConventions.ValidHookName */
$is_dismissed = apply_filters( 'Boldgrid\Library\Notice\KeyPrompt\getIsDismissed', false );
$is_displayed = apply_filters( 'Boldgrid\Library\Notice\KeyPrompt\getIsDisplayed', false );

$has_key_entered = ! empty( $this->core->configs['api_key'] );

// Check again button to refresh license status.
$refresh_key = ! $has_key_entered || $this->core->config->get_is_premium() ? '' : '<p>' .
	__( 'If you recently upgraded your BoldGrid Connect Key to Premium, click <strong>Check again</strong> to refresh the status of your license.', 'boldgrid-backup' ) .
	'<br />' .
	sprintf( '<a class="button" id="license_check_again">%1$s</a>', __( 'Check again', 'boldgrid-backup' ) ) .
	' <strong>' . __( 'License type', 'boldgrid-backup' ) . '</strong>: <span id="license_string">' . $this->core->config->get_license_string() . '</span>' .
	' <span class="spinner inline" style="display:none;vertical-align:text-bottom;"></span>' .
	'</p>' .
	'<p id="license_reload_page" class="hidden">' .
	$this->core->lang['icon_warning'] .
	__( 'Please reload this page for your new license status to take affect.', 'boldgrid-bakcup' ) .
	'</p>';

ob_start();
if ( $has_key_entered ) {
	printf(
		// translators: 1: Subscription type ("Premium" or "Free").
		esc_html__( 'You have entered a <strong>%1$s</strong> BoldGrid Connect Key.', 'boldgrid-backup' ),
		$this->core->config->get_is_premium() ? __( 'Premium', 'boldgrid-backup' ) : __( 'Free', 'boldgrid-backup' )
	);
} elseif ( $is_dismissed ) {
	_e(
		'You have dismissed the prompt to enter a BoldGrid Connect Key.  Click <a class="undismissBoldgridNotice" href="#">here</a> to restore the prompt.',
		'boldgrid-backup'
	);

	wp_nonce_field( 'boldgrid_set_key', 'set_key_auth' );
} else {
	_e( 'Please enter your BoldGrid Connect Key in the form at the top of this page.', 'boldgrid-backup' );
}
$output = ob_get_contents();
ob_end_clean();

// Add a "Get Premium" section under the Connect Key.
$bottom_box_premium = '';
if ( ! $this->core->config->get_is_premium() && ! $is_displayed ) {
	$bottom_box_premium = '<div class="bg-box-bottom premium">' .
		$this->core->go_pro->get_premium_button() .
		$this->core->lang['want_to'] .
		'</div>';
}

return '
<div class="bg-box">
	<div class="bg-box-top">
		' . __( 'BoldGrid Connect Key', 'boldgrid-backup' ) . '
	</div>
	<div class="bg-box-bottom">
		' . $output . $refresh_key . '
	</div>
	' . $bottom_box_premium . '
</div>
';
