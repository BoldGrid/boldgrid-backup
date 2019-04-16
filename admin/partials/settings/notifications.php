<?php
/**
 * File: notifications.php
 *
 * Show "Notifications" on settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.1
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
		<?php esc_html_e( 'Notifications', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Notification email address', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='notification-email' type='text' size='40' name='notification_email' value='<?php echo esc_attr( $settings['notification_email'] ); ?>'></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Send an email when a backup completes', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='notification-backup' type='checkbox' name='notify_backup' value='1'
						<?php
						if ( ! isset( $settings['notifications']['backup'] ) ||
								0 !== $settings['notifications']['backup'] ) {
							echo ' checked'; // Default.
						}
						?>
						/>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Send an email when a restoration is performed', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='notification-restore' type='checkbox' name='notify_restore' value='1'
						<?php
						if ( ! isset( $settings['notifications']['restore'] ) ||
								0 !== $settings['notifications']['restore'] ) {
							echo ' checked'; // Default.
						}
						?>
						/>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Send an email when there is a failed site check', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='notification-site-check' type='checkbox' name='notify_site_check' value='1'
						<?php
						if ( $settings['notifications']['site_check'] ) {
							echo ' checked'; // Default.
						}
						?>
						/>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
