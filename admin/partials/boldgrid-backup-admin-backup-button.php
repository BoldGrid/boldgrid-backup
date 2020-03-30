<?php
/**
 * File: boldgrid-backup-admin-backup-button.php
 *
 * Display a "Backup Site Now" button.
 *
 * @link https://www.boldgrid.com
 * @since 1.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP, WordPress.CSRF.NonceVerification.NoNonceVerification

defined( 'WPINC' ) || die;

$page          = empty( $_GET['page'] ) ? '' : $_GET['page'];
$backup_button = '
	<div id="backup-site-now-section">
		<form action="#" id="backup-site-now-form" method="POST">' .
			wp_nonce_field( 'boldgrid_backup_now', 'backup_auth', true, false );

/*
 * Create the "beef" of the backup button.
 *
 * The backup button is displayed in two ways:
 * 1. Within the "Backup Site Now" modal
 * 2. In an admin notice for Update protection.
 *
 * If the page is 'boldgrid-backup', then you're in the modal. The buttons will generally be the same,
 * but the formatting will be slightly different, hence the conditional below.
 */
if ( false !== strpos( $page, 'boldgrid-backup' ) ) {
	// The first div in the grid is needed so the grid fills out properly.
	$backup_button .= '
			<div style="display:grid; grid-gap:2em; grid-template-columns: 5fr 2fr;">
				<div>
					<p id="you_may_leave" class="hidden">' .
					esc_html__( 'Your backup is starting. This page will refresh and display the progress of the backup.', 'boldgrid-backup' ) .
					'</p>
				</div>
				<p style="text-align:right;">
					<a id="backup-site-now" class="button button-primary">' .
						esc_html__( 'Backup Site Now', 'boldgrid-backup' ) .
					'</a>
					<span class="spinner"></span>
				</p>
			</div>';
} else {
	$backup_button .= '
			<p id="you_may_leave" class="hidden">' .
				__( 'You may leave this page, doing so will not stop your backup.', 'boldgrid-backup' ) .
			'</p>
			<p>
				<a id="backup-site-now" class="button button-primary" data-updating="true" >' .
					esc_html__( 'Backup Site Now', 'boldgrid-backup' ) .
				'</a>
				<span class="spinner"></span>
			</p>';
}

$backup_button .= '
		</form>
	</div>
	<div id="backup-site-now-results"></div>';

return $backup_button;
