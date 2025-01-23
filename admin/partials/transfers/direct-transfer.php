<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_GET['user_login'] ) ) {
	$authd_sites = get_option( 'boldgrid_transfer_authd_sites', array() );
	$authd_sites[ $_GET['site_url'] ] = array(
		'user' => $_GET['user_login'],
		'pass' => Boldgrid_Backup_Admin_Crypt::crypt( $_GET['password'], 'e' )
	);

	update_option( 'boldgrid_transfer_authd_sites', $authd_sites );
}

$authd_sites = get_option( 'boldgrid_transfer_authd_sites', array() );
$transfers   = get_option( 'boldgrid_transfer_xfers', array() );

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
			<th>Transfer ID</th>
			<th>Source URL</th>
			<th>Transfer Status</th>
			<th>Elapsed Time</th>
			<th>Actions</th>
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
			$action_buttons  = '<button class="migrate-site button-primary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Migrate</button>';
			$action_buttons .= '<button class="delete-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Delete</button>';
		} else if ( 'canceled' === $transfer['status'] ) {
			$status         = 'canceled';
			$action_buttons = '<button class="delete-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Delete</button>';
		} else {
			$status = 'transferring';
			$action_buttons  = '<button class="cancel-transfer button-secondary" data-transfer-id="' . esc_attr( $transfer['transfer_id'] ) . '">Cancel</button>';
		}
		$transfer_table .= sprintf(
			'<tr class="transfer-info %7$s" data-transfer-id="%1$s">
				<td class="transfer_id">%1$s</td>
				<td class="dest_url">%2$s</td>
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
			'completed' === $transfer['status'] || 'canceled' === $transfer['status'] ? 'hidden' : '',
			esc_attr( $transfer['transfer_id'] )
		);
	}
}

$transfer_table .= '</tbody></table>';

$start_transfer_nonce_field  = wp_nonce_field( 'boldgrid_transfer_start_rx', 'transfer_start_nonce' );
$auth_transfer_nonce_field   = wp_nonce_field( 'boldgrid_transfer_auth_tx', 'transfer_auth_nonce' );
$transfer_check_nonce_field  = wp_nonce_field( 'boldgrid_transfer_check_status', 'transfer_check_nonce' );
$verify_files_nonce_field    = wp_nonce_field( 'boldgrid_transfer_verify_files', 'verify_files_nonce' );
$migrate_site_nonce_field    = wp_nonce_field( 'boldgrid_transfer_migrate_site', 'migrate_site_nonce' );
$cancel_transfer_nonce_field = wp_nonce_field( 'boldgrid_transfer_cancel_transfer', 'cancel_transfer_nonce' );
$delete_transfer_nonce_field = wp_nonce_field( 'boldgrid_transfer_delete_transfer', 'delete_transfer_nonce' );

$test_results_modal = '<div id="test-results-modal" style="display: none;">
	<div class="modal-panel">
		<div class="modal-header">
			<h2>Pre-Flight Tests</h2>
				<span id="test-results-modal-close" class="dashicons dashicons-dismiss"></span>
		</div>
		<div id="test-results-modal-content" class="modal-body"></div>
	</div>
</div>';

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
		%5$s
		%6$s
		%7$s
		%8$s
		%9$s
		%10$s
		%11$s
		%12$s
	</div>',
	esc_html__( 'Use this section if you want to select this WordPress installation as the destination.', 'boldgrid_backup' ),
	esc_attr( wp_generate_uuid4() ),
	$table,
	$transfer_table,
	$start_transfer_nonce_field,
	$auth_transfer_nonce_field,
	$transfer_check_nonce_field,
	$verify_files_nonce_field,
	$migrate_site_nonce_field,
	$cancel_transfer_nonce_field,
	$delete_transfer_nonce_field,
	$test_results_modal
);