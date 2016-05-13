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
	<h1>BoldGrid Backup and Restore Settings</h1>
	<h2>Backup Schedule</h2>
	<p>The BoldGrid Backup and Restore system allows you to upgrade your
		themes and plugins without being afraid it will do something you
		cannot easily undo. We preform a “Preflight Check” to see if the
		needed support is available on your web hosting account.</p>
	<form id='schedule-form' method='post'>
	<?php wp_nonce_field( 'boldgrid-backup-settings', 'settings_auth' ); ?>
		<input type='hidden' name='save_time' value='<?php echo time(); ?>' />
		<h3>Days of the Week</h3>
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
							?> />Sunday</td>
						<td class='schedule-dow'><input id='dow-monday' type='checkbox'
							name='dow_monday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_monday'] ) ) {
								echo ' checked';
							}
							?> />Monday</td>
						<td class='schedule-dow'><input id='dow-tuesday' type='checkbox'
							name='dow_tuesday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_tuesday'] ) ) {
								echo ' checked';
							}
							?> />Tuesday</td>
						<td class='schedule-dow'><input id='dow-wednesday' type='checkbox'
							name='dow_wednesday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_wednesday'] ) ) {
								echo ' checked';
							}
							?> />Wednesday</td>
						<td class='schedule-dow'><input id='dow-thursday' type='checkbox'
							name='dow_thursday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_thursday'] ) ) {
								echo ' checked';
							}
							?> />Thursday</td>
						<td class='schedule-dow'><input id='dow-friday' type='checkbox'
							name='dow_friday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_friday'] ) ) {
								echo ' checked';
							}
							?> />Friday</td>
						<td class='schedule-dow'><input id='dow-saturday' type='checkbox'
							name='dow_saturday' value='1'
							<?php
							if ( false === empty( $settings['schedule']['dow_saturday'] ) ) {
								echo ' checked';
							}
							?> />Saturday</td>
					</tr>
				</tbody>
			</table>
			<div class='hidden' id='no-backup-days'>
				<p>* Backup will not occur if no days are selected.</p>
			</div>
		</div>
		<h3>Notifications</h3>
		<div class='notification-settings'>
			<table id='notification-settings-table'>
				<tbody>
					<tr>
						<td>Send an email when a backup completes</td>
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
						<td>Send an email when a restoration is performed</td>
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
		<h3>Time of Day</h3>
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
		<h2>Post-Update Actions</h2>
		<h3>Auto Rollback</h3>
		<p>
			<input id='auto-rollback-enabled' type='radio' name='auto_rollback'
				value='1'
				<?php
				if ( false === isset( $settings['auto_rollback'] ) ||
					 1 === $settings['auto_rollback'] ) {
					echo ' checked';
				}
				?> /> Enabled &nbsp; <input id='auto-rollback-disabled' type='radio'
				name='auto_rollback' value='0'
				<?php
				if ( true === isset( $settings['auto_rollback'] ) && 0 === $settings['auto_rollback'] ) {
					echo ' checked';
				}
				?> /> Disabled
		</p>
		<div id='boldgrid-settings-submit-div'>
			<p>
				<input id='boldgrid-settings-submit' class='button button-primary'
					type='submit' name='submit' value='Save Changes' />
			</p>
		</div>
	</form>
</div>
