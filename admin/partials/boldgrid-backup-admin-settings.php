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
if ( true === empty( $settings ) ) {
	add_action( 'admin_footer', array(
		$this,
		'notice_settings_retrieval',
	) );
}
?>
<div class='wrap'>
	<h1><?php echo __( 'BoldGrid Backup and Restore Settings' ); ?></h1>
	<p><?php echo __( 'The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without being afraid it will do something you cannot easily undo. We preform a “Preflight Check” to see if the needed support is available on your web hosting account.' ); ?></p>
	<form id='schedule-form' method='post'>
	<?php wp_nonce_field( 'boldgrid-backup-settings', 'settings_auth' ); ?>
		<input type='hidden' name='save_time' value='<?php echo time(); ?>' />
		<h2><?php echo __( 'Days of the Week' ); ?></h2>
		<div class='schedule-dow'>
			<table id='schedule-dow-table'>
				<tbody>
					<tr>
						<td class='schedule-dow'><input id='dow-sunday' type='checkbox'
							name='dow_sunday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_sunday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Sunday' ); ?></td>
						<td class='schedule-dow'><input id='dow-monday' type='checkbox'
							name='dow_monday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_monday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Monday' ); ?></td>
						<td class='schedule-dow'><input id='dow-tuesday' type='checkbox'
							name='dow_tuesday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_tuesday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Tuesday' ); ?></td>
						<td class='schedule-dow'><input id='dow-wednesday' type='checkbox'
							name='dow_wednesday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_wednesday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Wednesday' ); ?></td>
						<td class='schedule-dow'><input id='dow-thursday' type='checkbox'
							name='dow_thursday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_thursday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Thursday' ); ?></td>
						<td class='schedule-dow'><input id='dow-friday' type='checkbox'
							name='dow_friday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_friday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Friday' ); ?></td>
						<td class='schedule-dow'><input id='dow-saturday' type='checkbox'
							name='dow_saturday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_saturday'] ) ) {
								echo ' checked';
							}
							?> /><?php echo __( 'Saturday' ); ?></td>
					</tr>
				</tbody>
			</table>
			<div class='hidden' id='no-backup-days'>
				<p>* <?php echo __( 'Backup will not occur if no days are selected.' ); ?></p>
			</div>
		</div>
		<h2><?php echo __( 'Time of Day' ); ?></h2>
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
									if ( false === empty( $settings['schedule']['tod_h'] ) &&
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
									if ( false === empty( $settings['schedule']['tod_m'] ) &&
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
									if ( false === isset( $settings['schedule']['tod_a'] ) ||
										 'PM' !== $settings['schedule']['tod_a'] ) {
										echo ' selected';
									}
									?>>AM</option>
								<option value='PM'
									<?php
									if ( true === isset( $settings['schedule']['tod_a'] ) &&
										 'PM' === $settings['schedule']['tod_a'] ) {
										echo ' selected';
									}
									?>>PM</option>
						</select></td>
					</tr>
				</tbody>
			</table>
		</div>
		<h2><?php echo __( 'Retention' ); ?></h2>
		<div class='retention-settings'>
			<table id='retention-settings-table'>
				<tbody>
					<tr>
						<td><?php echo __( 'Number of backup archives to retain' ); ?></td>
						<td><select id='retention-count' name='retention_count'>
							<?php
							for ( $x = 1; $x <= 10; $x ++ ) {
								?>
								<option value='<?php echo $x; ?>'
									<?php
									if ( ( true === isset( $settings['retention_count'] ) &&
									 $x === $settings['retention_count'] ) || ( false ===
									 isset( $settings['retention_count'] ) && 5 === $x ) ) {
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
		<h2><?php echo __( 'Auto Backup Before Updates' ); ?>
		 <span class='dashicons dashicons-editor-help' title='<?php
				echo __( 'Automatically perform a backup before WordPress updates.' );
		?>'></span></h2>
		<p>
			<input id='auto-backup-enabled' type='radio' name='auto_backup'
				value='1'
				<?php
				if ( false === isset( $settings['auto_backup'] ) ||
					 1 === $settings['auto_backup'] ) {
					echo ' checked';
				}
				?> /> <?php echo __( 'Enabled' ); ?> &nbsp; <input
				id='auto-backup-disabled' type='radio' name='auto_backup' value='0'
				<?php
				if ( true === isset( $settings['auto_backup'] ) && 0 === $settings['auto_backup'] ) {
					echo ' checked';
				}
				?> /> <?php echo __( 'Disabled' ); ?>
		</p>
		<h2><?php echo __( 'Auto Rollback' ); ?>
		 <span class='dashicons dashicons-editor-help' title='<?php
				echo __(
					'If something goes wrong while peforming WordPress updates, automatically restore the site using a backup made before updating WordPress.'
				);
		?>'></span></h2>
		<p>
			<input id='auto-rollback-enabled' type='radio' name='auto_rollback'
				value='1'
				<?php
				if ( false === isset( $settings['auto_rollback'] ) ||
					 1 === $settings['auto_rollback'] ) {
					echo ' checked';
				}
				?> /> <?php echo __( 'Enabled' ); ?> &nbsp; <input
				id='auto-rollback-disabled' type='radio' name='auto_rollback'
				value='0'
				<?php
				if ( true === isset( $settings['auto_rollback'] ) && 0 === $settings['auto_rollback'] ) {
					echo ' checked';
				}
				?> /> <?php echo __( 'Disabled' ); ?>
		</p>
		<h2><?php echo __( 'Notifications' ); ?></h2>
		<div class='notification-settings'>
			<table id='notification-settings-table'>
				<tbody>
					<tr>
						<td><?php echo __( 'Send an email when a backup completes' ); ?></td>
						<td><input id='notification-backup' type='checkbox'
							name='notify_backup' value='1'
							<?php
							if ( false === isset( $settings['notifications']['backup'] ) ||
								 0 !== $settings['notifications']['backup'] ) {
								echo ' checked';
							}
							?> /></td>
					</tr>
					<tr>
						<td><?php echo __( 'Send an email when a restoration is performed' ); ?></td>
						<td><input id='notification-restore' type='checkbox'
							name='notify_restore' value='1'
							<?php
							if ( false === isset( $settings['notifications']['restore'] ) ||
								 0 !== $settings['notifications']['restore'] ) {
								echo ' checked';
							}
							?> /></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id='boldgrid-settings-submit-div'>
			<p>
				<input id='boldgrid-settings-submit' class='button button-primary'
					type='submit' name='submit'
					value='<?php echo __( 'Save Changes' ); ?>' />
			</p>
		</div>
	</form>
</div>
