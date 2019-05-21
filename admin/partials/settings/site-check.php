<?php
/**
 * File: site-check.php
 *
 * Show "Site Check" on settings page.
 *
 * @since      1.10.0
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @link       https://www.boldgrid.com
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;
ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Site Check', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='site_check'></span>
	</div>
	<div class="bg-box-bottom">
		<p class="help" data-id="site_check">
			<?php
			printf(
				wp_kses(
					/* translators: 1: Log file path, 2: Wiki page URL address. */
					__(
						'Site Check is a feature that can be enabled to periodically check your site for errors. If an error is found, it can be logged and an email alert can be sent to you. If logging is enabled, then activity is logged to a file "%1$s". More information on Site Check and the commands used can be found in the <a target="_blank" href="%2$s">wiki</a>.',
						'boldgrid-backup'
					),
					[
						'a' => [
							'target' => [],
							'href'   => [],
						],
					]
				),
				esc_url( BOLDGRID_BACKUP_PATH . '/cli/bgbkup-cli.log' ),
				esc_url( 'https://github.com/BoldGrid/boldgrid-backup/wiki/CLI-Commands' )
			);
			?>
		</p>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Site Checker', 'boldgrid-backup' ); ?></th>
				<td>
					<input id="site-check-enabled" type="radio" name="site_check" value="1"
					<?php
					if ( $settings['site_check']['enabled'] ) {
							echo ' checked'; // Default.
					}
					?>
					/> <label for="site-check-enabled"><?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?></label>
					&nbsp; <input id="site-check-disabled" type="radio" name="site_check" value="0"
					<?php
					if ( ! $settings['site_check']['enabled'] ) {
							echo ' checked';
					}
					?>
					/> <label for="site-check-disabled"><?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Check Interval (in minutes)', 'boldgrid-backup' ); ?></th>
				<td>
					<input id="site-check-interval" name="site_check_interval" type="number"
						min="5" max="59" value="<?php echo esc_attr( $settings['site_check']['interval'] ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Logger', 'boldgrid-backup' ); ?></th>
				<td>
					<input id="site-check-logger-enabled" type="radio" name="site_check_logger" value="1"
					<?php
					if ( $settings['site_check']['logger'] ) {
							echo ' checked'; // Default.
					}
					?>
					/> <label for="site-check-logger-enabled"><?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?></label>
					&nbsp; <input id="site-check-logger-disabled" type="radio" name="site_check_logger" value="0"
					<?php
					if ( ! $settings['site_check']['logger'] ) {
							echo ' checked';
					}
					?>
					/> <label for="site-check-logger-disabled"><?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?></label>
				</td>
			</tr>
			<tr id="auto-recovery-tr">
				<th><?php esc_html_e( 'Auto Recovery', 'boldgrid-backup' ); ?></th>
				<td>
					<input id="auto-recovery-enabled" type="radio" name="auto_recovery" value="1"
					<?php
					if ( $settings['site_check']['auto_recovery'] ) {
							echo ' checked';
					}
					?>
					/> <label for="auto-recovery-enabled"><?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?></label>
					&nbsp; <input id="auto-recovery-disabled" type="radio" name="auto_recovery" value="0"
					<?php
					if ( ! $settings['site_check']['auto_recovery'] ) {
						echo ' checked'; // Default.
					}
					?>
					/> <label for="auto-recovery-enabled"><?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?></label>
				</td>
			</tr>
		</table>
	</div>
</div>
<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
