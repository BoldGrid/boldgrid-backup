<?php
/**
 * File: scheduler.php
 *
 * Show "Scheduler" on settings page.
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

$schedulers_available = $this->core->scheduler->get_available();
$schedulers_count     = count( $schedulers_available );
$scheduler            = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : false;
$scheduler_options    = '';

// No need to show the user any options if there is only 1 scheduler available.
if ( 1 === $schedulers_count ) {
	return sprintf( '<input type="hidden" name="scheduler" value="%1$s" />', key( $schedulers_available ) );
}

$wp_cron_warning = sprintf(
	'<p class="wp-cron-notice hidden"><span class="dashicons dashicons-warning yellow"></span> %1$s</p>',
	__( 'When using WP Cron, we cannot guarantee that backups will be created at the times you specify. Cron is the recommended scheduler.', 'boldgrid-backup' )
);

foreach ( $schedulers_available as $key => $scheduler_data ) {
	$scheduler_data['title'] = $scheduler_data['title'] . ( 'cron' === $key ?
		' (' . esc_html__( 'Recommended', 'boldgrid-backup' ) . ')' : '' );

	$scheduler_options .= sprintf(
		'<option value="%1$s" %3$s>%2$s</option>',
		$key,
		$scheduler_data['title'],
		$key === $scheduler ? 'selected="selected"' : ''
	);
}

$scheduler_select = sprintf( '<select name="scheduler" id="scheduler">%1$s</select>', $scheduler_options );

$intervals = array(
	'*/5 * * * *'  => esc_html__( 'Every 5 Minutes', 'boldgrid-backup' ),
	'*/10 * * * *' => esc_html__( 'Every 10 Minutes', 'boldgrid-backup' ),
	'*/30 * * * *' => esc_html__( 'Every 30 Minutes', 'boldgrid-backup' ),
	'0 * * * *'    => esc_html__( 'Once Every Hour', 'boldgrid-backup' ),
);

$selected_interval = ! empty( $settings['cron_interval'] ) ? $settings['cron_interval'] : '*/10 * * * *';

$cron_interval_options = '';

foreach ( $intervals as $key => $interval ) {
	$cron_interval_options .= sprintf(
		'<option value="%1$s" %3$s>%2$s</option>',
		$key,
		$interval,
		$key === $selected_interval ? 'selected="selected"' : ''
	);
}

$cron_interval_select = sprintf( '<select name="cron_interval" id="cron_interval">%1$s</select>', $cron_interval_options );

return sprintf(
	'
	<div class="bg-box">
		<div class="bg-box-top">
			%1$s <span class="bgb-unbold">(%4$s)</span>
		</div>
		<div class="bg-box-bottom">
			%2$s
			%3$s
			%5$s
		</div>
	</div>',
	__( 'Scheduler', 'boldgrid-backup' ),
	$scheduler_select,
	$wp_cron_warning,
	__( 'Advanced', 'boldgrid-backup' ),
	$cron_interval_select
);
