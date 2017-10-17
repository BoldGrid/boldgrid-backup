<?php
/**
 * Show "Scheduler" on settings page.
 *
 * @since 1.5.1
 */

$schedulers_available = $this->core->scheduler->get_available();

$schedulers_count = count( $schedulers_available );

$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : false;

// No need to show the user any options if there is only 1 scheduler available.
if( 1 === $schedulers_count ) {
	printf( '<input type="hidden" name="scheduler" value="%1$s" />', key( $schedulers_available ) );
	return;
}

$wp_cron_warning = sprintf(
	'<p class="wp-cron-notice hidden"><span class="dashicons dashicons-warning yellow"></span> %1$s</p>',
	__( 'When using WP Cron, we cannot guarantee that backups will be created at the times you specify. Cron is the recommended scheduler.', 'boldgrid-backup' )
);

$scheduler_options = '';
foreach( $schedulers_available as $key => $scheduler_data ) {
	$scheduler_options .= sprintf(
		'<option value="%1$s" %3$s>%2$s</option>',
		$key,
		$scheduler_data['title'],
		$key === $scheduler ? 'selected="selected"' : ''
	);
}

$scheduler_select = sprintf( '<select name="scheduler" id="scheduler">%1$s</select>', $scheduler_options );

printf( '
	<tr>
		<th>%1$s</th>
		<td>
			%2$s
			%3$s
		</td>
	</tr>',
	__( 'Scheduler', 'boldgrid-backup' ),
	$scheduler_select,
	$wp_cron_warning
);

?>