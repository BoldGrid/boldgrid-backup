<?php
/**
 * Show "Notifications" on settings page.
 *
 * @since 1.5.1
 */

defined( 'WPINC' ) ? : die;
?>

<hr />

<h2><?php esc_html_e( 'Notifications', 'boldgrid-backup' ); ?></h2>

<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'Notification email address', 'boldgrid-backup' ); ?></th>
		<td>
			<input id='notification-email' type='text' size='40' name='notification_email' value='<?php echo $settings['notification_email']; ?>'></td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Send an email when a backup completes', 'boldgrid-backup' ); ?></th>
		<td>
			<input id='notification-backup' type='checkbox' name='notify_backup' value='1'
				<?php
					if ( ! isset( $settings['notifications']['backup'] ) ||
						0 !== $settings['notifications']['backup'] ) {
						echo ' checked';
					}
				?> />
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Send an email when a restoration is performed', 'boldgrid-backup' ); ?></th>
		<td>
			<input id='notification-restore' type='checkbox' name='notify_restore' value='1'
				<?php
					if ( ! isset( $settings['notifications']['restore'] ) ||
						0 !== $settings['notifications']['restore'] ) {
						echo ' checked';
					}
				?> />
		</td>
	</tr>
</table>