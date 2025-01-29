<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$option_names = $this->core->configs['direct_transfer']['option_names'];

if ( isset( $_GET['user_login'] ) ) {
	$authd_sites = get_option( $option_names['authd_sites'], array() );
	$authd_sites[ $_GET['site_url'] ] = array(
		'user' => $_GET['user_login'],
		'pass' => Boldgrid_Backup_Admin_Crypt::crypt( $_GET['password'], 'e' )
	);

	update_option( $option_names['authd_sites'], $authd_sites, false );
}

$authd_sites = get_option( $option_names['authd_sites'], array() );
$transfers   = get_option( $option_names['transfers'], array() );

$table = '<table class="wp-list-table widefat fixed striped pages bgbkup-transfers-sites-table">
	<thead>
		<tr>
			<th>Site URL</th>
			<th>Username</th>
			<th></th>
		</tr>
	</thead>
	<tbody>';


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

	$table .= sprintf(
		'<tr>
			<td>%1$s</td>
			<td>%2$s</td>
			<td><button class="start-transfer button-primary %3$s" data-url="%1$s">%4$s</button></td>
		</tr>'
		,
		esc_html( $site ),
		esc_html( $creds['user'] ),
		esc_attr( $disabled ),
		esc_html( $button_text )
	);
}
$table .= '</tbody></table>';

$transfer_table = '<table class="wp-list-table widefat fixed striped pages bgbkup-transfers-tx-table">
	<thead>
		<tr>
			<th class="transfer_id">Transfer ID</th>
			<th class="source_url">Source URL</th>
			<th class="status">Transfer Status</th>
			<th class="elapsed_time">Elapsed Time</th>
			<th class="actions">Actions</th>
		</tr>
	</thead>
<tbody>';

if ( empty( $transfers ) ) {
	$transfer_table .= '<tr class="bgbkup-transfers-none-found"><td colspan="5" style="text-align:center">No transfers found.</td></tr>';
} else {
	foreach ( $transfers as $transfer ) {
		$time_elapsed = $transfer['time_elapsed'];
		$minutes	  = floor( $time_elapsed / 60 );
		$seconds	  = $time_elapsed % 60;
		$status = '';
		$action_buttons = '';
		if ( 'completed' === $transfer['status'] ) {
			$status = 'completed';
			$action_buttons  = '<button class="restore-site button-primary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Restore</button>';
			$action_buttons .= '<button class="delete-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Delete</button>';
			$action_buttons .= '<button class="resync-database button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Resync Database</button>';
		} else if ( 'canceled' === $transfer['status'] ) {
			$status         = 'canceled';
			$action_buttons = '<button class="delete-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Delete</button>';
		} else if ( 'restore-completed' === $transfer['status'] ) {
			$status = 'Restore Completed';
			$action_buttons  = '<button class="delete-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Delete</button>';
		} else {
			$status = 'transferring';
			$action_buttons  = '<button class="cancel-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Cancel</button>';
		}
		$transfer_table .= sprintf(
			'<tr class="transfer-info %7$s" data-transfer-id="%1$s">
				<td class="transfer_id">%1$s</td>
				<td class="source_url">%2$s</td>
				<td class="status">%3$s</td>
				<td class="time_elapsed">%4$s:%5$02d</td>
				<td class="actions">%6$s</td>
			</tr>',
			esc_attr( $transfer['transfer_id'] ),
			esc_html( $transfer['source_site_url'] ),
			esc_html( ucfirst( $status ) ),
			esc_html( $minutes ),
			esc_html( $seconds ),
			wp_kses_post( $action_buttons ),
			esc_attr( $status )
		);

		$hidden_statuses = array( 'completed', 'canceled', 'restore-completed' );

		$transfer_table .= sprintf(
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

$transfer_table .= '</tbody></table>';

return sprintf(
	'<div class="bgbkup-transfers-rx">
		<h2>%1$s</h2>
		<div class="boldgrid-transfer-input-fields">
			<input id="auth_admin_url" name="auth_admin_url" type="text" placeholder="http://example.com/wp-admin/" />
			<input id="app_uuid" name="app_uuid" type="hidden" value"%2$s" />
			<button id="auth_transfer" class="button-primary">Authenticate</button>
		</div>
		%3$s
		%4$s
	</div>',
	esc_html__( 'Use this section if you want to select this WordPress installation as the destination.', 'boldgrid_backup' ),
	esc_attr( wp_generate_uuid4() ),
	$table,
	$transfer_table
);