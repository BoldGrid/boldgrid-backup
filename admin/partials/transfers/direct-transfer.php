<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$option_names = $this->core->configs['direct_transfer']['option_names'];

$this->core->migrate->util->handle_new_auth();

$authd_sites = get_option( $option_names['authd_sites'], array() );
$transfers   = get_option( $option_names['transfers'], array() );

$escaped_sites_table = sprintf( '<h2>%1$s</h2>
	<p>%2$s</p>
<table class="wp-list-table widefat fixed striped pages bgbkup-transfers-sites-table">
	<thead>
		<tr>
			<th>%3$s</th>
			<th>%4$s</th>
			<th>%5$s</th>
		</tr>
	</thead>
	<tbody>',
	esc_html__( 'Authenticated Sites', 'boldgrid-backup' ),
	esc_html__(
		'The following sites have been authenticated for direct transfer. Click the "Start Transfer" button to begin the transfer process.',
		'boldgrid-backup'
	),
	esc_html__( 'Site URL', 'boldgrid-backup' ),
	esc_html__( 'Username', 'boldgrid-backup' ),
	esc_html__( 'Actions', 'boldgrid-backup' )
);

foreach ( $authd_sites as $site => $creds ) {
	$status      = '';
	$disabled    = '';
	$button_text = 'Start Transfer';

	foreach ( $transfers as $transfer ) {
		if ( $site === $transfer['dest_site_url'] && 'completed' !== $transfer['status'] ) {
			$disabled    = 'disabled';
			$button_text = 'Transferring';
			break;
		}
	}

	$escaped_sites_table .= sprintf(
		'<tr>
			<td>%1$s</td>
			<td>%2$s</td>
			<td><button class="start-transfer button-primary %3$s" data-url="%1$s">%4$s</button></td>
		</tr>
		<tr class="errors-row" data-url="%1$s">
			<td colspan="3">
				<div class="errors"></div>
			</td>
		'
		,
		esc_html( $site ),
		esc_html( $creds['user'] ),
		esc_attr( $disabled ),
		esc_html( $button_text )
	);
}

if ( empty( $authd_sites ) ) {
	$escaped_sites_table .= sprintf(
		'<tr class="bgbkup-transfers-none-found"><td colspan="3" style="text-align:center">%1$s</td></tr>',
		esc_html__( 'No authenticated sites. You must first authenticate the source site using the input above', 'boldgrid-backup' )
	);
}
$escaped_sites_table .= '</tbody></table>';

$escaped_transfer_table = sprintf( '<h2>%1$s</h2>
	<table class="wp-list-table widefat fixed striped pages bgbkup-transfers-tx-table">
		<thead>
			<tr>
				<th class="transfer_id">%2$s</th>
				<th class="source_url">%3$s</th>
				<th class="status">%4$s</th>
				<th class="elapsed_time">%5$s</th>
				<th class="actions">%6$s</th>
			</tr>
		</thead>
	<tbody>',
	esc_html__( 'Transfers', 'boldgrid-backup' ),
	esc_html__( 'Transfer ID', 'boldgrid-backup' ),
	esc_html__( 'Source URL', 'boldgrid-backup' ),
	esc_html__( 'Transfer Status', 'boldgrid-backup' ),
	esc_html__( 'Elapsed Time', 'boldgrid-backup' ),
	esc_html__( 'Actions', 'boldgrid-backup' )
);

if ( empty( $transfers ) ) {
	$escaped_transfer_table .= sprintf(
		'<tr class="bgbkup-transfers-none-found"><td colspan="5" style="text-align:center">%1$s</td></tr>',
		esc_html__( 'No transfers found. You must first authenticate a site, and start the transfer in the table above', 'boldgrid-backup' )
	);
} else {
	foreach ( $transfers as $transfer ) {
		$time_elapsed   = $this->core->migrate->util->get_elapsed_time( $transfer['transfer_id'], true );
		$status         = str_replace( '-', ' ', $transfer['status'] );
		$action_buttons = $this->core->migrate->util->transfer_action_buttons( $transfer['status'], $transfer['transfer_id'] );
	
		$escaped_transfer_table .= sprintf(
			'<tr class="transfer-info %6$s" data-transfer-id="%1$s">
				<td class="transfer_id">%1$s</td>
				<td class="source_url">%2$s</td>
				<td class="status">%3$s</td>
				<td class="time_elapsed">%4$s</td>
				<td class="actions">%5$s</td>
			</tr>',
			esc_attr( $transfer['transfer_id'] ),
			esc_html( $transfer['source_site_url'] ),
			esc_html( ucwords( $status ) ),
			esc_html( $time_elapsed ),
			wp_kses_post( $action_buttons ),
			esc_attr( $status )
		);

		$hidden_statuses = array( 'completed', 'canceled', 'restore-completed' );

		$escaped_transfer_table .= sprintf(
			'<tr class="progress-row %1$s" data-transfer-id="%2$s">
				<td colspan="5">
					<div class="progress">
						<div class="progress-bar" role="progressbar">
							<div class="progress-bar-text"></div>
							<div class="progress-bar-fill"></div>
						</div>
					</div>
				</td>
			</tr>',
			in_array( $transfer['status'], $hidden_statuses ) ? 'hidden' : '',
			esc_attr( $transfer['transfer_id'] )
		);
	}
}

$escaped_transfer_table .= '</tbody></table>';

$escaped_headings = sprintf(
	'<h2>%1$s <span class="boldgrid-backup-beta-span>%2$s!</span></h2>
	<p>%3$s</p>
	<p>%4$s</p>',
	esc_html__( 'Direct Transfer', 'boldgrid-backup' ),
	esc_html__( 'Beta', 'boldgrid-backup' ),
	esc_html__(
		'The direct transfer feature allows you to transfer files from another website to this site without having to make a backup first.',
		'boldgrid-backup'
	),
	esc_html__( 'The Total Upkeep plugin must be installed on both this site AND the source site.','boldgrid-backup' )
);

$escaped_auth_input = sprintf(
	'<h3>%1$s <span class="dashicons dashicons-editor-help" data-id="direct-transfer-auth"></span></h3>
	<p class="help" data-id="direct-transfer-auth">%2$s %3$s</p>
	<div class="boldgrid-transfer-input-fields">
		<input id="auth_admin_url" name="auth_admin_url" type="text" placeholder="https://example.com" />
		<input id="app_uuid" name="app_uuid" type="hidden" value="%4$s" />
		<input id="auth_nonce" name="auth_nonce" type="hidden" value="%5$s" />
		<button id="auth_transfer" class="button-primary">%6$s</button>
	</div>
	<p class="authentication-error notice notice-error"></p>',
	esc_html__( 'Enter the source site\'s URL below in order to generate an application password. ', 'boldgrid-backup' ),
	esc_html__(
		'When you click the authenticate button, you will be redirected to the source site to create an application password.',
		'boldgrid-backup'
	),
	esc_html__(
		'This password will be used to authenticate the transfer process. You will then be redirected back here.',
		'boldgrid-backup'
	),
	esc_attr( wp_generate_uuid4() ),
	wp_create_nonce( 'boldgrid_backup_direct_transfer_auth' ),
	esc_html__( 'Authenticate', 'boldgrid-backup' ),

);

$escaped_modals = include BOLDGRID_BACKUP_PATH . '/admin/partials/transfers/direct-transfer-modals.php';

return sprintf(
	'<div class="bgbkup-transfers-rx">
		%1$s
		%2$s
		%3$s
		%4$s
		%5$s
	</div>',
	$escaped_headings,
	$escaped_auth_input,
	$escaped_sites_table,
	$escaped_transfer_table,
	$escaped_modals
);
