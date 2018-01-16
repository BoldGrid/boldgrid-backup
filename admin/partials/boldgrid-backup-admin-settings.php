<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

defined( 'WPINC' ) ? : die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
$scheduler = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/scheduler.php';
$folders_include = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/folders.php';
$db = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/db.php';
$retention = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/retention.php';
$auto_updates = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/auto-updates.php';
$notifications = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/notifications.php';
$backup_directory = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/backup-directory.php';
$connect_key = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/connect-key.php';

$days_of_week = '';
$time_of_day = '';
$storage = '';
if( $this->core->scheduler->is_available( 'cron' ) || $this->core->scheduler->is_available( 'wp-cron' ) ) {
	$days_of_week = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/days-of-week.php';
	$time_of_day = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/time-of-day.php';
	$storage = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage.php';
}

$sections = array(
	'sections' => array(
		array(
			'id' => 'section_schedule',
			'title' => __( 'Scheduled Backups', 'boldgrid-backup' ),
			'content' => $scheduler . $days_of_week . $time_of_day . $storage . $folders_include . $db,
		),
		array(
			'id' => 'connect_key',
			'title' => __( 'BoldGrid Connect Key', 'boldgrid-bacup' ),
			'content' => $connect_key,
		),
		array(
			'id' => 'section_retention',
			'title' => __( 'Retention', 'boldgrid-backup' ),
			'content' => $retention,
		),
		array(
			'id' => 'section_updates',
			'title' => __( 'Auto Updates & Rollback', 'boldgrid-backup' ),
			'content' => $auto_updates,
		),
		array(
			'id' => 'section_notifications',
			'title' => __( 'Notifications', 'boldgrid-backup' ),
			'content' => $notifications,
		),
		array(
			'id' => 'section_directory',
			'title' => __( 'Backup Directory', 'boldgrid-backup' ),
			'content' => $backup_directory,
		),
	),
	'post_col_right' => sprintf( '
		<div id="boldgrid-settings-submit-div">
			<p>
				<input id="boldgrid-settings-submit" class="button button-primary" type="submit" name="submit" value="%1$s" />
			</p>
		</div>',
		__( 'Save Changes', 'boldgrid-backup' )
	),
);

/**
 * Allow other plugins to modify the sections of the settings page.
 *
 * @since 1.5.4
 *
 * @param array $sections
 */
$sections = apply_filters( 'boldgrid_backup_settings_sections', $sections );

/**
 * Render the $sections into displayable markup.
 *
 * @since 1.5.4
 *
 * @param array $sections
 */
$col_container = apply_filters( 'Boldgrid\Library\Ui\render_col_container', $sections );

// Check if settings are available, show an error notice if not.
if ( empty( $settings ) ) {
	add_action( 'admin_footer',
		array(
			$this,
			'notice_settings_retrieval',
		)
	);
}

wp_nonce_field( 'boldgrid_backup_settings' );

?>
<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup and Restore Settings', 'boldgrid-backup' ); ?></h1>

	<?php
	echo $nav;

	/*
	 * Print this text:
	 *
	 * The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without
	 * being afraid it will do something you cannot easily undo. We perform a Preflight Check to see
	 * if the needed support is available on your web hosting account.
	 */
	$url = admin_url( 'admin.php?page=boldgrid-backup-test' );
	$link = sprintf(
		wp_kses(
			__( 'The BoldGrid Backup and Restore system allows you to upgrade your themes and plugins without being afraid it will do something you cannot easily undo. We perform a <a href="%s">Preflight Check</a> to see if the needed support is available on your web hosting account.', 'boldgrid-backup' ),
			array(  'a' => array( 'href' => array() ) )
		),
		esc_url( $url )
	);
	echo '<p>' . $link . '</p>';

	include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/premium-message.php';

	echo( include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-size-data.php' );
	?>

	<hr />

	<form id='schedule-form' method='post'>
	<?php
		echo $col_container;
		wp_nonce_field( 'boldgrid-backup-settings', 'settings_auth' );
		printf( '<input type="hidden" name="save_time" value="%1$s" />', time() );
	?>
	</form>

</div>
