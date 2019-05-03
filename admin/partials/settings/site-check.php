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
		<p class='help' data-id='site_check'>
			<?php
			esc_html_e(
				'If something goes wrong with your WordPress site, information can be logged, emailed to you, and your site automatically restore the last full backup archive.',
				'boldgrid-backup'
			);
			?>
		</p>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Site Checker', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='site_check-enabled' type='radio' name='site_check' value='1'
					<?php
					if ( $settings['site_check']['enabled'] ) {
							echo ' checked'; // Default.
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='site_check-disabled' type='radio' name='site_check'
					value='0'
					<?php
					if ( ! $settings['site_check']['enabled'] ) {
							echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Check Interval (in minutes)', 'boldgrid-backup' ); ?></th>
				<td>
					<select id='site-check-interval' name='site_check_interval'>
						<?php
						for ( $x = 5; $x <= 59; $x ++ ) {
							?>
						<option value='<?php echo esc_attr( $x ); ?>'
							<?php
							if ( $x === $settings['site_check']['interval'] ) {
								echo ' selected';
							}
							?>
						><?php echo esc_html( $x ); ?></option>
							<?php
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Logger', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='site-check-logger-enabled' type='radio' name='site_check_logger' value='1'
					<?php
					if ( $settings['site_check']['logger'] ) {
							echo ' checked'; // Default.
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='site-check-logger-disabled' type='radio' name='site_check_logger'
					value='0'
					<?php
					if ( ! $settings['site_check']['logger'] ) {
							echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>
			<tr id='auto-recovery-tr'>
				<th><?php esc_html_e( 'Auto Recovery', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='auto-recovery-enabled' type='radio' name='auto_recovery' value='1'
					<?php
					if ( $settings['site_check']['auto_recovery'] ) {
							echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='auto-recovery-disabled' type='radio' name='auto_recovery'
					value='0'
					<?php
					if ( ! $settings['site_check']['auto_recovery'] ) {
						echo ' checked'; // Default.
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
