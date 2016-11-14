<?php
/**
 * Show free / premium message.
 *
 * @summary Show an intro atop the archives page regarding free / premium version of the plugin.
 *
 * @since 1.3.1
 */

if( $this->config->get_is_premium() ) {
	/*
	 * Print this message:
	 *
	 * You are running the Premium version of the BoldGrid Backup Plugin.  Please visit our
	 * <a>BoldGrid Backup User Guide - Premium Addition for more information.
	 */
	printf(
		wp_kses(
			__( '<p>You are running the Premium version of the BoldGrid Backup Plugin. Please visit our <a href="%s" target="_blank">BoldGrid Backup User Guide - Premium Addition</a> for more information.</p>', 'boldgrid-backup' ),
			array(
				'a' => array( 'href' => array(), 'target' => array() ),
				'p' => array(),
			)
		),
		esc_url( 'https://www.boldgrid.com' )
	);
} else {
	/*
	 * Print this message:
	 *
	 * The BoldGrid Backup plugin comes in two versions, the Free and Premium.  The Premium version
	 * is part of the BoldGrid Premium Suite.  To learn about the capabilities of the BoldGrid
	 * Backup Plugin, check out our <a>BoldGrid Backup User Guide - Free Addition.
	 */
	printf(
		wp_kses(
			__( '<p>The BoldGrid Backup plugin comes in two versions, the Free and Premium. The Premium version is part of the BoldGrid Premium Suite. To learn about the capabilities of the BoldGrid Backup Plugin, check out our <a href="%s" target="_blank">BoldGrid Backup User Guide - Free Addition</a>.</p>', 'boldgrid-backup' ),
			array(
				'a' => array( 'href' => array(), 'target' => array() ),
				'p' => array(),
			)
		),
		esc_url( 'https://www.boldgrid.com' )
	);

	/*
	 * Print this message:
	 *
	 * Key differences are size of backups supported, scheduling capabilities, and finer grain
	 * rollback support.  To upgrade now, go <a>here</a>.
	 */
	printf(
		wp_kses(
			__( '<p>Key differences are size of backups supported, scheduling capabilities, and finer grain rollback support. To upgrade now, go <a href="%s" target="_blank">here</a>.</p>', 'boldgrid-backup' ),
			array(
				'a' => array( 'href' => array(), 'target' => array() ),
				'p' => array(),
			)
		),
		esc_url( 'https://www.boldgrid.com' )
	);
}
?>
