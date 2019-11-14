<?php
/**
 * File: premium-message.php
 *
 * Show free / premium message.
 *
 * @summary Show an intro atop the settings page regarding free / premium version of the plugin.
 *
 * @link https://www.boldgrid.com
 * @since 1.3.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

if ( $this->core->config->get_is_premium() ) {
	?><p>
	<?php

	/*
	 * Print this message:
	 *
	 * You are running the Premium version of the BOLDGRID_BACKUP_TITLE Plugin. Please visit our
	 * <a>BOLDGRID_BACKUP_TITLE User Guide</a> for more information.
	 */
	printf(
		wp_kses(
			// translators: 1: URL address, 2: Plugin title.
			esc_html__(
				'You are running the Premium version of the %2$s plugin. Please visit our <a href="%1$s" target="_blank">%2$s User Guide</a> for more information.',
				'boldgrid-backup'
			),
			[
				'a' => [
					'href'   => [],
					'target' => [],
				],
			]
		),
		esc_url( $this->core->configs['urls']['user_guide'] ),
		esc_html( BOLDGRID_BACKUP_TITLE )
	);
	?>
	</p>
	<?php
} else {
	/*
	 * Print this message:
	 *
	 * The BOLDGRID_BACKUP_TITLE plugin comes in two versions, the Free and Premium. The Premium
	 * version is part of the BoldGrid Premium Suite. To learn about the capabilities of the
	 * BOLDGRID_BACKUP_TITLE Plugin, check out our <a>BOLDGRID_BACKUP_TITLE User Guide</a>.
	 *
	 * Key differences are size of backups supported, scheduling capabilities, and number of
	 * archives supported. To upgrade now, go <a>here</a>.
	 */
	printf(
		wp_kses(
			// translators: 1: URL address for user guide, 2: URL address for upgrade, 3: Plugin title.
			esc_html__(
				'
				<p>The %3$s plugin comes in two versions, the Free and Premium. The Premium version is part of the BoldGrid Premium Suite. To learn about the capabilities of the %3$s plugin, check out our <a href="%1$s" target="_blank">%3$s User Guide</a>.</p>
				<p>Key differences are size of backups supported, scheduling capabilities, and number of archives supported. To upgrade now, go <a href="%2$s" target="_blank">here</a>.</p>
				',
				'boldgrid-backup'
			),
			[
				'a' => [
					'href'   => [],
					'target' => [],
				],
				'p' => [],
			]
		),
		esc_url( $this->core->configs['urls']['user_guide'] ),
		esc_url( $this->core->configs['urls']['upgrade'] ),
		esc_html( BOLDGRID_BACKUP_TITLE )
	);
}
