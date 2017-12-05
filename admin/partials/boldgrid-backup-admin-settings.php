<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

// Check if settings are available, show an error notice if not.
if ( empty( $settings ) ) {
	add_action( 'admin_footer',
		array(
			$this,
			'notice_settings_retrieval',
		)
	);
}

wp_nonce_field( 'boldgrid_backup_settings' );

?>
<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup and Restore Settings', 'boldgrid-backup' ); ?></h1>

	<?php include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php'; ?>

	<p>
		<?php
		/*
		 * Print this text:
		 *
		 * The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without
		 * being afraid it will do something you cannot easily undo. We perform a Preflight Check to see
		 * if the needed support is available on your web hosting account.
		 */
		$url = admin_url( 'admin.php?page=boldgrid-backup-test' );
		$link = sprintf(
			wp_kses(
				__( 'The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without being afraid it will do something you cannot easily undo. We perform a <a href="%s">Preflight Check</a> to see if the needed support is available on your web hosting account.', 'boldgrid-backup' ),
				array(  'a' => array( 'href' => array() ) )
			),
			esc_url( $url )
		);
		echo $link;
		?>
	</p>

	<?php
		include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/premium-message.php';

		echo( include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-size-data.php' );
	?>

	<form id='schedule-form' method='post'>
	<?php wp_nonce_field( 'boldgrid-backup-settings', 'settings_auth' ); ?>
		<input type='hidden' name='save_time' value='<?php echo time(); ?>' />

		<?php

		printf( '<h2>%1$s</h2>', __( 'Backup Schedule', 'boldgrid-backup' ) );

		echo '<table class="form-table">';

		include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/scheduler.php';

		if( $this->core->scheduler->is_available( 'cron' ) || $this->core->scheduler->is_available( 'wp-cron' ) ) {
			include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/cron.php';
			include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage.php';
		}

		echo '</table>';

		echo '</hr>';

		$folders_include = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/folders.php';
		echo $folders_include;

		include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/db.php';

		include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/retention.php';

		?>

		<h2><?php echo __( 'Auto Updates and Rollback', 'boldgrid-inspirations' ); ?></h2>

		<table class="form-table">
			<tr>
				<th>
					<?php esc_html_e( 'Plugin Auto-Updates', 'boldgrid-backup' ); ?>
		 			<span class="dashicons dashicons-editor-help" data-id="plugin-autoupdate"></span>

		 			<p class="help" data-id="plugin-autoupdate">
						<?php esc_html_e( 'Automatically perform all plugin updates when available.', 'boldgrid-backup' ); ?>
					</p>
		 		</th>
		 		<td>
		 			<input id="plugin-autoupdate-enabled" type="radio" name="plugin_autoupdate" value="1"
					<?php
					if ( isset( $settings['plugin_autoupdate'] ) &&
						 1 === $settings['plugin_autoupdate'] ) {
							?> checked<?php
					}
					?> /> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id="plugin-autoupdate-disabled" type="radio" name="plugin_autoupdate" value="0"
					<?php
					if ( ! isset( $settings['plugin_autoupdate'] ) ||
						! $settings['plugin_autoupdate'] ) {
							?> checked<?php
					}
					?> /> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
		 		</td>
		 	</tr>

		 	<tr>
		 		<th>
		 			<?php esc_html_e( 'Theme Auto-Updates', 'boldgrid-backup' ); ?>
		 			<span class="dashicons dashicons-editor-help" data-id="theme-autoupdate"></span>

		 			<p class="help" data-id="theme-autoupdate">
						<?php esc_html_e( 'Automatically perform all theme updates when available.', 'boldgrid-backup' ); ?>
					<p>
		 		</th>
		 		<td>
			 		<input id="theme-autoupdate-enabled" type="radio" name="theme_autoupdate" value="1"
					<?php
					if ( isset( $settings['theme_autoupdate'] ) &&
						 1 === $settings['theme_autoupdate'] ) {
							?> checked<?php
					}
					?> /> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id="theme-autoupdate-disabled" type="radio" name="theme_autoupdate" value="0"
					<?php
					if ( ! isset( $settings['theme_autoupdate'] ) ||
						! $settings['theme_autoupdate'] ) {
							?> checked<?php
					}
					?> /> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
		 		</td>
		 	</tr>

		 	<tr>
		 		<th>
		 			<?php echo __( 'Auto Backup<br />Before Updates', 'boldgrid-backup' ); ?>
		 			<span class='dashicons dashicons-editor-help' data-id='auto-backup'></span>

		 			<p class='help' data-id='auto-backup'>
						<?php esc_html_e( 'Automatically perform a backup before WordPress updates.', 'boldgrid-backup' ); ?>
					<p>
		 		</th>
		 		<td>
			 		<input id='auto-backup-enabled' type='radio' name='auto_backup'
					value='1'
					<?php
					if ( ! isset( $settings['auto_backup'] ) ||
						 1 === $settings['auto_backup'] ) {
						echo ' checked';
					}
					?> /> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='auto-backup-disabled' type='radio' name='auto_backup' value='0'
					<?php
					if ( isset( $settings['auto_backup'] ) && 0 === $settings['auto_backup'] ) {
						echo ' checked';
					}
					?> /> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
		 		</td>
		 	</tr>

		 	<tr>
		 		<th>
		 			<?php esc_html_e( 'Auto Rollback', 'boldgrid-backup' ); ?><span class='dashicons dashicons-editor-help' data-id='auto-rollback'></span>

		 			<p class='help' data-id='auto-rollback'>
						<?php
						esc_html_e(
							'If something goes wrong while peforming WordPress updates, automatically restore the site using a backup made before updating WordPress.',
							'boldgrid-backup'
						);
						?>
					</p>
		 		</th>
		 		<td>
			 		<input id='auto-rollback-enabled' type='radio' name='auto_rollback'
					value='1'
					<?php
					if ( ! isset( $settings['auto_rollback'] ) ||
						 1 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?> /> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='auto-rollback-disabled' type='radio' name='auto_rollback'
					value='0'
					<?php
					if ( isset( $settings['auto_rollback'] ) && 0 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?> /> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
		 		</td>
		 	</tr>
		</table>

		<?php

			include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/notifications.php';

			include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/backup-directory.php';

			/**
			 * Allow the display of compressor options.
			 *
			 * @since 1.5.1
			 */
			$show_compressor_options = apply_filters( 'boldgrid_backup_show_compressor_options', false );

			if( $show_compressor_options ) {
				include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/compressor.php';
			}

		// This is a temporary section.
		$is_premium = get_option( 'boldgrid_backup_is_premium', 'false' );
		$premium_checked = ( 'true' === $is_premium ? 'checked' : '' );
		$free_checked = ( 'true' !== $is_premium ? 'checked' : '' );
		?>
		<h2>Plugin version</h2>
		<p>
			This is a <strong>temporary</strong> section of the settings page, and provides an easy
			way to toggle whether you're using the free or premium version of the plugin.<br />
			<input type="radio" name="is_premium" value="true" <?php echo $premium_checked; ?> /> Premium version<br />
			<input type="radio" name="is_premium" value="false" <?php echo $free_checked; ?> /> Free version
		</p>

		<div id='boldgrid-settings-submit-div'>
			<p>
				<input id='boldgrid-settings-submit' class='button button-primary'
					type='submit' name='submit'
					value='<?php esc_html_e( 'Save Changes', 'boldgrid-backup' ); ?>' />
			</p>
		</div>
	</form>
</div>
