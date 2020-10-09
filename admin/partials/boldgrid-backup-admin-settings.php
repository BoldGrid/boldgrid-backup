<?php
/**
 * File: boldgrid-backup-admin-settings.php
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav             = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
$scheduler       = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/scheduler.php';
$compressor      = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/compressor.php';
$backup_logs     = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/backup-logs.php';
$folders_include = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/folders.php';
$db              = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/db.php';
$auto_backup     = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/auto-backup.php';
$auto_updates    = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/auto-update.php';
$notifications   = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/notifications.php';
$connect_key     = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/connect-key.php';
$site_check      = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/site-check.php';
$backup_security = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/backup-security.php';
$days_of_week    = '';
$time_of_day     = '';
$storage         = '';

if ( $this->core->scheduler->is_available( 'cron' ) || $this->core->scheduler->is_available( 'wp-cron' ) ) {
	$days_of_week = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/days-of-week.php';
	$time_of_day  = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/time-of-day.php';
	$storage      = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage.php';
}

$sections = [
	'sections'       => [
		[
			'id'      => 'section_schedule',
			'title'   => __( 'Backup Schedule', 'boldgrid-backup' ),
			'content' => $days_of_week . $time_of_day . $scheduler,
		],
		[
			'id'      => 'section_storage',
			'title'   => __( 'Backup Storage', 'boldgrid-backup' ),
			'content' => $storage . $folders_include . $db,
		],
		[
			'id'      => 'section_security',
			'title'   => __( 'Backup Security', 'boldgrid-backup' ),
			'content' => $backup_security,
		],
		[
			'id'      => 'section_process',
			'title'   => __( 'Backup Process', 'boldgrid-backup' ),
			'content' => $compressor . $backup_logs,
		],
		[
			'id'      => 'connect_key',
			'title'   => __( 'BoldGrid Connect Key', 'boldgrid-backup' ),
			'content' => $connect_key,
		],
		[
			'id'      => 'section_auto_updates',
			'title'   => __( 'Auto Updates', 'boldgrid-backup' ),
			'content' => $auto_backup . $auto_updates,
		],
		[
			'id'      => 'section_site_check',
			'title'   => __( 'Site Check', 'boldgrid-backup' ),
			'content' => $site_check,
		],
		[
			'id'      => 'section_notifications',
			'title'   => __( 'Notifications', 'boldgrid-backup' ),
			'content' => $notifications,
		],
	],
	'post_col_right' => sprintf(
		'
		<div id="boldgrid-settings-submit-div">
			<p>
				<input id="boldgrid-settings-submit" class="button button-primary" type="submit" name="submit" value="%1$s" />
			</p>
		</div>',
		__( 'Save Changes', 'boldgrid-backup' )
	),
];

/**
 * Allow other plugins to modify the sections of the settings page.
 *
 * @since 1.6.0
 *
 * @param array $sections
 */
$sections = apply_filters( 'boldgrid_backup_settings_sections', $sections );

/**
 * Render the $sections into displayable markup.
 *
 * @since 1.6.0
 *
 * @param array $sections
 *
 * phpcs:disable WordPress.NamingConventions.ValidHookName
 */
$col_container = apply_filters( 'Boldgrid\Library\Ui\render_col_container', $sections );
if ( is_array( $col_container ) ) {
	$col_container = $this->core->lang['icon_warning'] . ' ' . __( 'Unable to display settings page. Unknown BoldGrid Library error.', 'boldgrid-backup' );
}

// Check if settings are available, show an error notice if not.
if ( empty( $settings ) ) {
	add_action(
		'admin_footer',
		[
			$this,
			'notice_settings_retrieval',
		]
	);
}

/*
 * This nonce is used for various things on the settings page, such as refreshing "configure" links
 * for a remote storage provider after a user updates settings.
 */
wp_nonce_field( 'boldgrid_backup_settings', 'bgbkup_settings_nonce' );

	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

	/*
	 * Print this text:
	 *
	 * The BOLDGRID_BACKUP_TITLE and Restore system allows you to upgrade your themes and plugins without
	 * being afraid it will do something you cannot easily undo. We perform a Preflight Check to see
	 * if the needed support is available on your web hosting account.
	 */
?>

<p>
<?php
printf(
	wp_kses(
		// translators: 1: Plugin title, 2:URL address.
		__(
			'The %1$s Backup and Restore system allows you to upgrade your themes and plugins without being afraid it will do something you cannot easily undo. We perform a <a href="%2$s">Preflight Check</a> to see if the needed support is available on your web hosting account.',
			'boldgrid-backup'
		),
		[ 'a' => [ 'href' => [] ] ]
	),
	esc_html( BOLDGRID_BACKUP_TITLE ),
	esc_url( admin_url( 'admin.php?page=boldgrid-backup-test' ) )
);

$show_section = ! empty( $_REQUEST['section'] ) ? sanitize_key( $_REQUEST['section'] ) : ''; // phpcs:ignore WordPress
?>
</p>

<hr />

<form id='bgb-settings-form' method='post'>
<?php
	echo $col_container; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	wp_nonce_field( 'boldgrid-backup-settings', 'settings_auth' );
?>
	<input type="hidden" name="save_time" value="<?php echo esc_attr( time() ); ?>" />
</form>
