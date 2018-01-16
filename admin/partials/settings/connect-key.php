<?php
/**
 * File: connect-key.php
 *
 * Show Connect Key status.
 *
 * @since 1.5.4
 */

defined( 'WPINC' ) ? : die;

$releaseChannel = new \Boldgrid\Library\Library\ReleaseChannel();
$key = new \Boldgrid\Library\Library\Key( $releaseChannel );
$keyPrompt = new \Boldgrid\Library\Library\Notice\KeyPrompt( $key );
$isDismissed = $keyPrompt->isDismissed( 'bg-key-prompt' );

ob_start();
if ( ! empty( $this->core->configs['api_key'] ) ) {
	printf(
		__( 'You have entered a <strong>%1$s</strong> BoldGrid Connect Key.', 'boldgrid-backup' ),
		$this->core->config->get_is_premium() ? __( 'Premium', 'boldgrid-backup' ) : __( 'Free', 'boldgrid-backup' )
	);
} else if ( $isDismissed ) {
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

return sprintf( '
	<div class="bg-box">
		<div class="bg-box-top">
			%1$s
		</div>
		<div class="bg-box-bottom">
			%2$s
		</div>
	</div>',
	__( 'BoldGrid Connect Key', 'boldgrid-backup' ),
	$output
);
?>