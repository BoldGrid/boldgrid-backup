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
?>
<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup and Restore Settings', 'boldgrid-backup' ); ?></h1>
	<p>
		<?php
		/*
		 * Print this text:
		 *
		 * The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without
		 * being afraid it will do something you cannot easily undo. We preform a Preflight Check to see
		 * if the needed support is available on your web hosting account.
		 */
		$url = admin_url( 'admin.php?page=boldgrid-backup-test' );
		$link = sprintf(
			wp_kses(
				__( 'The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without being afraid it will do something you cannot easily undo. We preform a <a href="%s">Preflight Check</a> to see if the needed support is available on your web hosting account.', 'boldgrid-backup' ),
				array(  'a' => array( 'href' => array() ) )
			),
			esc_url( $url )
		);
		echo $link;
		?>
	</p>

	<?php include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/premium-message.php'; ?>

	<div id='size-data'>
		<?php
		wp_nonce_field( 'boldgrid_backup_sizes', 'sizes_auth' );
		printf( '<p><span class="spinner" style="float:none; visibility:visible; margin-top: -10px; margin-left:0px;"></span>%s</p>',
			esc_html__( 'Calculating disk space...' )
		);
		?>
	</div>

	<form id='schedule-form' method='post'>
	<?php wp_nonce_field( 'boldgrid-backup-settings', 'settings_auth' ); ?>
		<input type='hidden' name='save_time' value='<?php echo time(); ?>' />
		<h2><?php esc_html_e( 'Days of the Week', 'boldgrid-backup' ); ?></h2>
		<div class='schedule-dow'>
			<table id='schedule-dow-table'>
				<tbody>
					<tr>
						<td class='schedule-dow'><input id='dow-sunday' type='checkbox'
							name='dow_sunday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_sunday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Sunday', 'boldgrid-backup' ); ?></td>
						<td class='schedule-dow'><input id='dow-monday' type='checkbox'
							name='dow_monday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_monday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Monday', 'boldgrid-backup' ); ?></td>
						<td class='schedule-dow'><input id='dow-tuesday' type='checkbox'
							name='dow_tuesday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_tuesday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Tuesday', 'boldgrid-backup' ); ?></td>
						<td class='schedule-dow'><input id='dow-wednesday' type='checkbox'
							name='dow_wednesday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_wednesday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Wednesday', 'boldgrid-backup' ); ?></td>
						<td class='schedule-dow'><input id='dow-thursday' type='checkbox'
							name='dow_thursday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_thursday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Thursday', 'boldgrid-backup' ); ?></td>
						<td class='schedule-dow'><input id='dow-friday' type='checkbox'
							name='dow_friday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_friday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Friday', 'boldgrid-backup' ); ?></td>
						<td class='schedule-dow'><input id='dow-saturday' type='checkbox'
							name='dow_saturday' value='1'
							<?php
							if ( ! empty( $settings['schedule']['dow_saturday'] ) ) {
								echo ' checked';
							}
							?> /><?php esc_html_e( 'Saturday', 'boldgrid-backup' ); ?></td>
					</tr>
				</tbody>
			</table>
			<div class='hidden' id='no-backup-days'>
				<p>* <?php
				esc_html_e( 'Backup will not occur if no days are selected.', 'boldgrid-backup' );
				?></p>
			</div>
			<div class='hidden' id='free-dow-limit'>
				<p>* <?php
				esc_html_e( 'Free Backup License supports only scheduling two days a week.', 'boldgrid-backup' );
				?></p>
			</div>
			<?php
			$url = 'https://www.boldgrid.com';
			$link = sprintf(
				wp_kses(
					__( 'Note: Backups use resources and <a href="%s" target="_blank">must pause your site</a> momentarily. Use sparingly.', 'boldgrid-backup' ),
					array(  'a' => array( 'href' => array() ) )
				),
				esc_url( $url )
			);
			printf( '<div id="use-sparingly"><p>* %s</p></div>', $link );
			?>




		</div>
		<h2><?php esc_html_e( 'Time of Day', 'boldgrid-backup' ); ?></h2>
		<div class='schedule-tod'>
			<table id='schedule-tod-table'>
				<tbody>
					<tr>
						<td><select id='tod-h' name='tod_h'>
						<?php
						for ( $x = 1; $x <= 12; $x ++ ) {
							?>
							<option value='<?php echo $x;?>'
									<?php
									if ( ! empty( $settings['schedule']['tod_h'] ) &&
									$x === $settings['schedule']['tod_h'] ) {
										echo ' selected';
									}
							?>><?php echo $x;?></option>
						<?php
						}
						?>
						</select></td>
						<td>:</td>
						<td><select id='tod-m' name='tod_m'>
						<?php
						for ( $x = 0; $x <= 59; $x ++ ) {
							// Convert $x to a padded string.
							$x = str_pad( $x, 2, '0', STR_PAD_LEFT );
							?>
							<option value='<?php echo $x;?>'
									<?php
									if ( ! empty( $settings['schedule']['tod_m'] ) &&
									$x == $settings['schedule']['tod_m'] ) {
										echo ' selected';
									}
							?>><?php echo $x;?></option>
						<?php
						}
						?>
						</select></td>
						<td></td>
						<td><select id='tod-a' name='tod_a'>
								<option value='AM'
									<?php
									if ( ! isset( $settings['schedule']['tod_a'] ) ||
										 'PM' !== $settings['schedule']['tod_a'] ) {
										echo ' selected';
									}
									?>>AM</option>
								<option value='PM'
									<?php
									if ( isset( $settings['schedule']['tod_a'] ) &&
										 'PM' === $settings['schedule']['tod_a'] ) {
										echo ' selected';
									}
									?>>PM</option>
						</select></td>
					</tr>
				</tbody>
			</table>
		</div>
		<h2><?php esc_html_e( 'Retention', 'boldgrid-backup' ); ?></h2>
		<div class='retention-settings'>
			<table id='retention-settings-table'>
				<tbody>
					<tr>
						<td><?php
						esc_html_e( 'Number of backup archives to retain', 'boldgrid-backup' );
						?></td>
						<td><select id='retention-count' name='retention_count'>
							<?php
							$is_retention_set = ( isset( $settings['retention_count'] ) );

							for ( $x = 1; $x <= 10; $x ++ ) {
								?>
								<option value='<?php echo $x; ?>'
									<?php
									// If set, select the number, or use 5 as a default.
									if ( ( $is_retention_set && $x === $settings['retention_count'] ) ||
									( ! $is_retention_set && 5 === $x ) ) {
										echo ' selected';
									}
								?>><?php echo $x; ?></option>
								<?php
							}
							?>
							</select></td>
					</tr>
				</tbody>
			</table>
		</div>
		<h2><?php esc_html_e( 'Auto Backup Before Updates', 'boldgrid-backup' ); ?>
		 <span class='dashicons dashicons-editor-help' title='<?php
				esc_html_e(
					'Automatically perform a backup before WordPress updates.',
					'boldgrid-backup'
				);
		?>'></span></h2>
		<p>
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
		</p>
		<h2><?php esc_html_e( 'Auto Rollback', 'boldgrid-backup' ); ?>
		 <span class='dashicons dashicons-editor-help' title='<?php
				esc_html_e(
					'If something goes wrong while peforming WordPress updates, automatically restore the site using a backup made before updating WordPress.',
					'boldgrid-backup'
				);
		?>'></span></h2>
		<p>
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
		</p>
		<h2><?php esc_html_e( 'Notifications', 'boldgrid-backup' ); ?></h2>
		<div class='notification-settings'>
			<table id='notification-settings-table'>
				<tbody>
					<tr>
						<td><?php
						esc_html_e( 'Notification email address', 'boldgrid-backup' );
						?></td>
						<td><input id='notification-email' type='text' size='40'
						name='notification_email' value='<?php
						echo $settings['notification_email'];
						?>'></td>
					</tr>
					<tr>
						<td><?php
						esc_html_e( 'Send an email when a backup completes', 'boldgrid-backup' );
						?></td>
						<td><input id='notification-backup' type='checkbox'
							name='notify_backup' value='1'
							<?php
							if ( ! isset( $settings['notifications']['backup'] ) ||
							0 !== $settings['notifications']['backup'] ) {
								echo ' checked';
							}
							?> /></td>
					</tr>
					<tr>
						<td><?php
						esc_html_e(
							'Send an email when a restoration is performed',
							'boldgrid-backup'
						);
						?></td>
						<td><input id='notification-restore' type='checkbox'
							name='notify_restore' value='1'
							<?php
							if ( ! isset( $settings['notifications']['restore'] ) ||
							0 !== $settings['notifications']['restore'] ) {
								echo ' checked';
							}
							?> /></td>
					</tr>
				</tbody>
			</table>
		</div>
		<h2><?php esc_html_e( 'Backup Directory', 'boldgrid-backup' ); ?></h2>
		<div class='backup-directory'>
			<?php esc_html_e( 'Directory to store backup archives', 'boldgrid-backup' ); ?>:
			<input id='backup-directory-path' type='text' size='50' name='backup_directory'
			value='<?php echo $settings['backup_directory']; ?>'>
		</div>
		<div id='boldgrid-settings-submit-div'>
			<p>
				<input id='boldgrid-settings-submit' class='button button-primary'
					type='submit' name='submit'
					value='<?php esc_html_e( 'Save Changes', 'boldgrid-backup' ); ?>' />
			</p>
		</div>
	</form>
</div>
