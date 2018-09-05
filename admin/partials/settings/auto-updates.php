<?php
/**
 * File: auto-updates.php
 *
 * Show "Auto Updates" on settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

ob_start();
?>

<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Auto Updates and Rollback', 'boldgrid-inspirations' ); ?>
	</div>
	<div class="bg-box-bottom">

		<table class="form-table">
			<tr>
				<th>
					<?php esc_html_e( 'Plugin Auto-Updates', 'boldgrid-backup' ); ?>
					<span class="dashicons dashicons-editor-help" data-id="plugin-autoupdate"></span>

					<p class="help" data-id="plugin-autoupdate">
						<?php
						printf(
							// translators: 1: URL address.
							esc_html__(
								'Automatically perform all plugin updates when available. Enabling this feature adds the %1$s, which enables automatic plugin updates when an update is available.',
								'boldgrid-backup'
							),
							sprintf(
								'<a target="_blank" href="https://codex.wordpress.org/Configuring_Automatic_Background_Updates#Plugin_.26_Theme_Updates_via_Filter">auto_update_plugin %1$s</a>',
								esc_html__( 'filter', 'boldgrid-backup' )
							)
						);
						?>
					</p>
				</th>
				<td>
					<input id="plugin-autoupdate-enabled" type="radio" name="plugin_autoupdate"
						value="1"
					<?php
					if ( isset( $settings['plugin_autoupdate'] ) &&
						1 === $settings['plugin_autoupdate'] ) {
						?>
							checked
							<?php
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id="plugin-autoupdate-disabled" type="radio" name="plugin_autoupdate" value="0"
					<?php
					if ( ! isset( $settings['plugin_autoupdate'] ) ||
						! $settings['plugin_autoupdate'] ) {
						?>
							checked
							<?php
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>

			<tr>
				<th>
						<?php esc_html_e( 'Theme Auto-Updates', 'boldgrid-backup' ); ?>
					<span class="dashicons dashicons-editor-help" data-id="theme-autoupdate"></span>

					<p class="help" data-id="theme-autoupdate">
						<?php
						printf(
							// translators: 1: URL address.
							esc_html__(
								'Automatically perform all theme updates when available. Enabling this feature adds the %1$s, which enables automatic theme updates when an update is available.',
								'boldgrid-backup'
							),
							sprintf(
								'<a target="_blank" href="https://codex.wordpress.org/Configuring_Automatic_Background_Updates#Plugin_.26_Theme_Updates_via_Filter">auto_update_theme %1$s</a>',
								esc_html__( 'filter', 'boldgrid-backup' )
							)
						);
						?>
					<p>
				</th>
				<td>
					<input id="theme-autoupdate-enabled" type="radio" name="theme_autoupdate" value="1"
					<?php
					if ( isset( $settings['theme_autoupdate'] ) &&
						1 === $settings['theme_autoupdate'] ) {
						?>
							checked
							<?php
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id="theme-autoupdate-disabled" type="radio" name="theme_autoupdate" value="0"
					<?php
					if ( ! isset( $settings['theme_autoupdate'] ) ||
						! $settings['theme_autoupdate'] ) {
						?>
							checked
							<?php
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>

			<tr>
				<th>
						<?php esc_html_e( 'Auto Backup', 'boldgrid-backup' ); ?>
					<span class='dashicons dashicons-editor-help' data-id='auto-backup'></span>

					<p class='help' data-id='auto-backup'>
						<?php
						printf(
							// translators: 1: URL address.
							esc_html__(
								'Automatically perform a backup before WordPress updates. When this feature is enabled, a full backup will be made during the %1$s.',
								'boldgrid-backup'
							),
							sprintf(
								'<a target="_blank" href="https://developer.wordpress.org/reference/hooks/pre_auto_update/">pre_auto_update %1$s</a>',
								esc_html__( 'action', 'boldgrid-backup' )
							)
						);
						?>
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
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='auto-backup-disabled' type='radio' name='auto_backup' value='0'
					<?php
					if ( isset( $settings['auto_backup'] ) && 0 === $settings['auto_backup'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>

			<tr>
				<th>
						<?php esc_html_e( 'Auto Rollback', 'boldgrid-backup' ); ?><span class='dashicons dashicons-editor-help' data-id='auto-rollback'></span>

					<p class='help' data-id='auto-rollback'>
						<?php
						esc_html_e(
							'If something goes wrong while performing WordPress updates, automatically restore the site using a backup made before updating WordPress. This feature does not apply to auto updates.',
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
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='auto-rollback-disabled' type='radio' name='auto_rollback'
					value='0'
					<?php
					if ( isset( $settings['auto_rollback'] ) && 0 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
