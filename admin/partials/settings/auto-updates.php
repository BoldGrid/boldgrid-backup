<?php
/**
 * Show "Auto Updates" on settings page.
 *
 * @since 1.5.4
 */

defined( 'WPINC' ) ? : die;

ob_start();
?>

<div class="bg-box">
	<div class="bg-box-top">
		<?php echo __( 'Auto Updates and Rollback', 'boldgrid-inspirations' ); ?>
	</div>
	<div class="bg-box-bottom">

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
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
?>