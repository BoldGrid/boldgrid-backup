<?php
/**
 * Show free / premium message.
 *
 * @summary Show an intro atop the settings page regarding free / premium version of the plugin.
 *
 * @since 1.3.1
 */

if( $this->core->config->get_is_premium() ) {
	?><p><?php
	/*
	 * Print this message:
	 *
	 * You are running the Premium version of the BoldGrid Backup Plugin. Please visit our
	 * <a>BoldGrid Backup User Guide</a> for more information.
	 */
	printf(
		wp_kses(
			__( 'You are running the Premium version of the BoldGrid Backup Plugin. Please visit our <a href="%s" target="_blank">BoldGrid Backup User Guide</a> for more information.', 'boldgrid-backup' ),
			array( 'a' => array( 'href' => array(), 'target' => array() ) )
		),
		esc_url( $this->core->configs['urls']['user_guide'] )
	);
	?></p><?php
} else {
	/*
	 * Print this message:
	 *
	 * The BoldGrid Backup plugin comes in two versions, the Free and Premium. The Premium
	 * version is part of the BoldGrid Premium Suite. To learn about the capabilities of the
	 * BoldGrid Backup Plugin, check out our <a>BoldGrid Backup User Guide</a>.
	 *
	 * Key differences are size of backups supported, scheduling capabilities, and number of
	 * archives supported. To upgrade now, go <a>here</a>.
	 */
	printf(
		wp_kses(
			__(
				'
				<p>The BoldGrid Backup plugin comes in two versions, the Free and Premium. The Premium version is part of the BoldGrid Premium Suite. To learn about the capabilities of the BoldGrid Backup Plugin, check out our <a href="%1$s" target="_blank">BoldGrid Backup User Guide</a>.</p>
				<p>Key differences are size of backups supported, scheduling capabilities, and number of archives supported. To upgrade now, go <a href="%2$s" target="_blank">here</a>.</p>
				',
				'boldgrid-backup'
			),
			array(
				'a' => array( 'href' => array(), 'target' => array() ),
				'p' => array(),
			)
		),
		esc_url( $this->core->configs['urls']['user_guide'] ),
		esc_url( $this->core->configs['urls']['upgrade'] )
	);
}
?>
