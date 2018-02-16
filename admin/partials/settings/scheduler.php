<?php
/**
 * Show "Scheduler" on settings page.
 *
 * @since 1.5.1
 */

defined( 'WPINC' ) ? : die;

$schedulers_available = $this->core->scheduler->get_available();

$schedulers_count = count( $schedulers_available );

$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : false;

// No need to show the user any options if there is only 1 scheduler available.
if( 1 === $schedulers_count ) {
	return sprintf( '<input type="hidden" name="scheduler" value="%1$s" />', key( $schedulers_available ) );
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

return sprintf( '
	<div class="bg-box">
		<div class="bg-box-top">
			%1$s
		</div>
		<div class="bg-box-bottom">
			%2$s
			%3$s
		</div>
	</div>',
	__( 'Scheduler', 'boldgrid-backup' ),
	$scheduler_select,
	$wp_cron_warning
);

?>