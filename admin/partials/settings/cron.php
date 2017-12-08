<?php
/**
 * Show Cron on settings page.
 *
 * @since 1.5.1
 */

defined( 'WPINC' ) ? : die;
?>

<tr class="schedule-dow">
	<th>
		<?php esc_html_e( 'Days of the Week', 'boldgrid-backup' ); ?>
	</th>
	<td>
		<input id='dow-sunday' type='checkbox' name='dow_sunday' value='1' <?php if ( ! empty( $settings['schedule']['dow_sunday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Sunday', 'boldgrid-backup' ); ?><br />
		<input id='dow-monday' type='checkbox' name='dow_monday' value='1' <?php if ( ! empty( $settings['schedule']['dow_monday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Monday', 'boldgrid-backup' ); ?><br />
		<input id='dow-tuesday' type='checkbox' name='dow_tuesday' value='1' <?php if ( ! empty( $settings['schedule']['dow_tuesday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Tuesday', 'boldgrid-backup' ); ?><br />
		<input id='dow-wednesday' type='checkbox' name='dow_wednesday' value='1' <?php if ( ! empty( $settings['schedule']['dow_wednesday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Wednesday', 'boldgrid-backup' ); ?><br />
		<input id='dow-thursday' type='checkbox' name='dow_thursday' value='1' <?php if ( ! empty( $settings['schedule']['dow_thursday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Thursday', 'boldgrid-backup' ); ?><br />
		<input id='dow-friday' type='checkbox' name='dow_friday' value='1' <?php if ( ! empty( $settings['schedule']['dow_friday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Friday', 'boldgrid-backup' ); ?><br />
		<input id='dow-saturday' type='checkbox' name='dow_saturday' value='1' <?php if ( ! empty( $settings['schedule']['dow_saturday'] ) ) { echo ' checked'; } ?> /><?php esc_html_e( 'Saturday', 'boldgrid-backup' ); ?>

		<br /><br />

		<div class='hidden' id='no-backup-days'>
			<p><span class="dashicons dashicons-warning yellow"></span> <?php
				esc_html_e( 'Backup will not occur if no days are selected.', 'boldgrid-backup' );
			?></p>
		</div>

		<div class='hidden' id='free-dow-limit'>
			<p><span class="dashicons dashicons-warning yellow"></span> <?php
				esc_html_e( 'Free Backup License supports only scheduling two days a week.', 'boldgrid-backup' );
			?></p>
		</div>

		<?php
			$url = $this->core->configs['urls']['resource_usage'];
			$link = sprintf(
				wp_kses(
					__( 'Backups use resources and <a href="%s" target="_blank">must pause your site</a> momentarily. Use sparingly.', 'boldgrid-backup' ),
					array(  'a' => array( 'href' => array(), 'target' => array(), ) )
				),
				esc_url( $url )
			);
			printf( '<div id="use-sparingly" class="hidden"><p><span class="dashicons dashicons-warning yellow"></span> %s</p></div>', $link );
		?>
	</td>
</tr>

<tr>
	<th>
		<?php esc_html_e( 'Time of Day', 'boldgrid-backup' ); ?>
	</th>
	<td>
		<select id='tod-h' name='tod_h'>
			<?php
				for ( $x = 1; $x <= 12; $x ++ ) {
			?>
			<option value='<?php echo $x;?>'
			<?php
				if ( ! empty( $settings['schedule']['tod_h'] ) && $x === $settings['schedule']['tod_h'] ) {
					echo ' selected';
				}
			?>><?php echo $x;?></option>
			<?php
				}
			?>
		</select>

		<select id='tod-m' name='tod_m'>
			<?php
				for ( $x = 0; $x <= 59; $x ++ ) {
					// Convert $x to a padded string.
					$x = str_pad( $x, 2, '0', STR_PAD_LEFT );
			?>
			<option value='<?php echo $x;?>'
			<?php
				if ( ! empty( $settings['schedule']['tod_m'] ) && $x == $settings['schedule']['tod_m'] ) {
					echo ' selected';
				}
			?>><?php echo $x;?></option>
			<?php
				}
			?>
		</select>

		<select id='tod-a' name='tod_a'>
			<option value='AM'
				<?php
					if ( ! isset( $settings['schedule']['tod_a'] ) || 'PM' !== $settings['schedule']['tod_a'] ) {
						echo ' selected';
					}
				?>>AM</option>
			<option value='PM'
				<?php
					if ( isset( $settings['schedule']['tod_a'] ) && 'PM' === $settings['schedule']['tod_a'] ) {
						echo ' selected';
					}
				?>>PM</option>
		</select>

		<p class="wp-cron-notice hidden"><em>WP Cron runs on GMT time, which is currently <?php echo date( 'l g:i a e')?>.</em></p>
	</td>
</tr>
