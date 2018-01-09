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

?><p><?php

if ( ! empty( $this->core->configs['api_key'] ) ) {
	_e( 'You have already entered a Connect Key.', 'boldgrid-backup' );
} else if ( $isDismissed ) {
	_e(
		'You have dismissed the prompt to enter a BoldGrid Connect Key.  Click <a class="undismissBoldgridNotice" href="#">here</a> to restore the prompt.',
		'boldgrid-backup'
	);

	wp_nonce_field( 'boldgrid_set_key', 'set_key_auth' );
}

?>
</p>
