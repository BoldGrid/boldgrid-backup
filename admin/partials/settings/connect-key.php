<?php
/**
 * File: connect-key.php
 *
 * Show Connect Key status.
 *
 * @since 1.5.4
 */

defined( 'WPINC' ) ? : die;

$is_dismissed = apply_filters( 'Boldgrid\Library\Notice\KeyPrompt\getIsDismissed', false );
$is_displayed = apply_filters( 'Boldgrid\Library\Notice\KeyPrompt\getIsDisplayed', false );

ob_start();
if ( ! empty( $this->core->configs['api_key'] ) ) {
	printf(
		__( 'You have entered a <strong>%1$s</strong> BoldGrid Connect Key.', 'boldgrid-backup' ),
		$this->core->config->get_is_premium() ? __( 'Premium', 'boldgrid-backup' ) : __( 'Free', 'boldgrid-backup' )
	);
} else if ( $is_dismissed ) {
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
if( ! $this->core->config->get_is_premium() && ! $is_displayed ) {
	$bottom_box_premium = '<div class="bg-box-bottom premium">' .
		$this->core->go_pro->get_premium_button() .
		$this->core->lang['want_to'] .
		'</div>';
}

return sprintf( '
	<div class="bg-box">
		<div class="bg-box-top">
			%1$s
		</div>
		<div class="bg-box-bottom">
			%2$s
		</div>
		%3$s
	</div>',
	__( 'BoldGrid Connect Key', 'boldgrid-backup' ),
	$output,
	$bottom_box_premium
);
?>