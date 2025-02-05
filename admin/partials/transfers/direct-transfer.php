<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$option_names = $this->core->configs['direct_transfer']['option_names'];

if ( isset( $_GET['_wpnonce'] ) &&
	wp_verify_nonce( sanitize_text_field( wp_unslash($_GET['_wpnonce'] ) ), 'boldgrid_backup_direct_transfer_auth' ) &&
	isset( $_GET['user_login'] ) ) {
		$site_url = sanitize_url( wp_unslash( $_GET['site_url'] ) );
		$user     = sanitize_text_field( wp_unslash( $_GET['user_login'] ) );
		$pass     = sanitize_text_field( wp_unslash( $_GET['password'] ) );
	$authd_sites = get_option( $option_names['authd_sites'], array() );
	$authd_sites[ $site_url ] = array(
		'user' => $user,
		'pass' => Boldgrid_Backup_Admin_Crypt::crypt( $pass, 'e' )
	);

	update_option( $option_names['authd_sites'], $authd_sites, false );
}

$authd_sites = get_option( $option_names['authd_sites'], array() );
$transfers   = get_option( $option_names['transfers'], array() );

$table = sprintf( '<table class="wp-list-table widefat fixed striped pages bgbkup-transfers-sites-table">
	<thead>
		<tr>
			<th>%1$s</th>
			<th>%2$s</th>
			<th>%3$s</th>
		</tr>
	</thead>
	<tbody>',
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

	$table .= sprintf(
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
$table .= '</tbody></table>';

$transfer_table = sprintf( '<table class="wp-list-table widefat fixed striped pages bgbkup-transfers-tx-table">
		<thead>
			<tr>
				<th class="transfer_id">%1$s</th>
				<th class="source_url">%2$s</th>
				<th class="status">%3$s</th>
				<th class="elapsed_time">%4$s</th>
				<th class="actions">%5$s</th>
			</tr>
		</thead>
	<tbody>',
	esc_html__( 'Transfer ID', 'boldgrid-backup' ),
	esc_html__( 'Source URL', 'boldgrid-backup' ),
	esc_html__( 'Transfer Status', 'boldgrid-backup' ),
	esc_html__( 'Elapsed Time', 'boldgrid-backup' ),
	esc_html__( 'Actions', 'boldgrid-backup' )
);

if ( empty( $transfers ) ) {
	$transfer_table .= sprintf(
		'<tr class="bgbkup-transfers-none-found"><td colspan="5" style="text-align:center">%1$s</td></tr>',
		esc_html__( 'No transfers found.', 'boldgrid-backup' )
	);
} else {
	foreach ( $transfers as $transfer ) {
		$time_elapsed   = $transfer['time_elapsed'];
		$minutes        = floor( $time_elapsed / 60 );
		$seconds        = $time_elapsed % 60;
		$status         = '';
		$action_buttons = '';
		if ( 'completed' === $transfer['status'] ) {
			$status         = 'completed';
			$action_buttons = sprintf(
				'<button class="restore-site button-primary" data-transfer-id="%1$s">%2$s</button>
				<button class="delete-transfer button-secondary" data-transfer-id="%1$s">%3$s</button>
				<button class="resync-database button-secondary" data-transfer-id="%1$s">%4$s</button>',
				esc_attr( $transfer['transfer_id'] ),
				esc_html__( 'Restore', 'boldgrid-backup' ),
				esc_html__( 'Delete', 'boldgrid-backup' ),
				esc_html__( 'Resync Database', 'boldgrid-backup' )
			);
		} else if ( 'canceled' === $transfer['status'] ) {
			$status         = 'canceled';
			$action_buttons = sprintf(
				'<button class="delete-transfer button-secondary" data-transfer-id="%1$s">%2$s</button>',
				esc_attr( $transfer['transfer_id'] ),
				esc_html__( 'Delete', 'boldgrid-backup' )
			);
		} else if ( 'restore-completed' === $transfer['status'] ) {
			$status         = 'Restore Completed';
			$action_buttons = sprintf(
				'<button class="delete-transfer button-secondary" data-transfer-id="%1$s">%2$s</button>',
				esc_attr( $transfer['transfer_id'] ),
				esc_html__( 'Delete', 'boldgrid-backup' )
			);
		} else {
			$status         = 'transferring';
			$action_buttons = sprintf(
				'<button class="cancel-transfer button-secondary" data-transfer-id="%1$s">%2$s</button>',
				esc_attr( $transfer['transfer_id'] ),
				esc_html__( 'Cancel', 'boldgrid-backup' )
			);
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

$escaped_headings = sprintf(
	'<h2>%1$s</h2>
	<p>%2$s</p>
	<p>%3$s</p>',
	esc_html__( 'Direct Transfer', 'boldgrid-backup' ),
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

return sprintf(
	'<div class="bgbkup-transfers-rx">
		%1$s
		%2$s
		%3$s
		%4$s
	</div>',
	$escaped_headings,
	$escaped_auth_input,
	$table,
	$transfer_table
);
