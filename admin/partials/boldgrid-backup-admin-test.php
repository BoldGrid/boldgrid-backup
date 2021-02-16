<?php
/**
 * File: boldgrid-backup-admin-test.php
 *
 * Provide a admin area view for the plugin functionality test report.
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

// phpcs:disable WordPress.VIP

defined( 'WPINC' ) || die;

// Setup our lang.
$lang = array(
	'yes'                  => __( 'Yes', 'boldgrid-backup' ),
	'no'                   => __( 'No', 'boldgrid-backup' ),
	'none'                 => __( 'None', 'boldgrid-backup' ),
	'untested'             => __( 'untested', 'boldgrid-backup' ),
	'PASS'                 => __( 'PASS', 'boldgrid-backup' ),
	'FAIL'                 => __( 'FAIL', 'boldgrid-backup' ),
	'not_set'              => __( 'not set', 'boldgrid-backup' ),
	'before_test_compress' => __( 'Before any compressors are tested, please be sure your backup directory is created and has proper permissions set.', 'boldgrid-backup' ),
	'ensure_dir_perms'     => __( 'Please be sure that your backup directory exists. If it does, also ensure it has read, write, and modify permissions.', 'boldgrid-backup' ),
);

$lang['dir_of_dir'] = $lang['ensure_dir_perms'];
if ( $this->test->is_windows() ) {
	$lang['dir_of_dir'] = __( 'Please review directory permissions. If you are on a Windows server, your user may need to be able to read BOTH the backup directory and its parent directory.', 'boldgrid-backup' );
}

$error_span   = '<span class="error">%1$s</span><br />%2$s';
$warning_span = '<span class="warning">%1$s</span><br />%2$s';
$success_span = '<span class="success">%1$s</span>';

$allowed_tags = array(
	'span'   => array(
		'class'   => array(
			'error',
			'dashicons',
			'dashicons-editor-help',
			'spinner',
			'inline',
		),
		'data-id' => array(
			'cron-time-zone',
		),
	),
	'br'     => array(),
	'p'      => array(
		'class'   => array(
			'help',
		),
		'data-id' => array(
			'cron-time-zone',
		),
	),
	'pre'    => array(),
	'form'   => array(
		'method' => array(),
		'action' => array(
			'admin.php?page=boldgrid-backup-test',
		),
	),
	'input'  => array(
		'type'  => array(),
		'name'  => array(
			'cron_timezone_test',
		),
		'value' => array(),
		'class' => array(
			'button',
		),
		'style' => array(),
	),
	'strong' => array(),
);

$backup_dir_perms = $this->test->extensive_dir_test( $backup_directory );

$php_zip = new Boldgrid_Backup_Admin_Compressor_Php_Zip( $this );

$pcl_zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this );

$filesystem_method = get_filesystem_method();

$execution_functions = Boldgrid_Backup_Admin_Cli::get_execution_functions();

$valid_backup_dir = $backup_dir_perms['exists'] && $backup_dir_perms['read'] && $backup_dir_perms['write'] && $backup_dir_perms['rename'] && $backup_dir_perms['delete'] && $backup_dir_perms['dirlist'];

$timezone = $this->time->get_server_timezone();
$timezone = false === $timezone ? 'UTC' . $this->time->get_server_offset() : $timezone->getName() . ' / ' . $this->time->get_server_offset();

// Run our tests.
$tests = array(
	array(
		'id' => 'pass',
		'k'  => __( 'Functionality test status:', 'boldgrid-backup' ),
		'v'  => ( $this->test->run_functionality_tests() ? sprintf( $success_span, $lang['PASS'] ) : sprintf( $error_span, $lang['FAIL'], '' ) ),
	),
	array(
		'heading' => __( 'General tests', 'boldgrid-backup' ),
	),
	array(
		'k' => __( 'User home directory:', 'boldgrid-backup' ),
		'v' => $home_dir . ' (' . $home_dir_mode . ')',
	),
	array(
		'k' => __( 'User home directory writable?', 'boldgrid-backup' ),
		'v' => ( $home_dir_writable ? $lang['yes'] : $lang['no'] ),
	),
	array(
		'k' => __( 'WordPress directory:', 'boldgrid-backup' ),
		'v' => ABSPATH,
	),
	array(
		'k' => __( 'WordPress directory writable?', 'boldgrid-backup' ),
		'v' => ( $this->test->get_is_abspath_writable() ? $lang['yes'] : sprintf( $error_span, $lang['no'], '' ) ),
	),
	array(
		'k' => __( 'Document root:', 'boldgrid-backup' ),
		'v' => str_replace( '\\\\', '\\', $_SERVER['DOCUMENT_ROOT'] ),
	),
	array(
		'k' => __( 'Current user:', 'boldgrid-backup' ),
		'v' => get_current_user(),
	),
	array(
		'k' => __( 'PHP in safe mode?', 'boldgrid-backup' ),
		'v' => $this->test->is_php_safemode() ? sprintf( $error_span, $lang['yes'], '' ) : $lang['no'],
	),
	array(
		'k' => __( 'Filesystem Method', 'boldgrid-backup' ),
		'v' => $this->test->is_filesystem_supported() ?
			$filesystem_method : sprintf(
				$error_span,
				$filesystem_method, __( 'Only "direct" filesystem supported.', 'boldgrid-backup' )
			),
	),
	array(
		'k' => __( 'Execution functions available', 'boldgrid-backup' ),
		'v' => empty( $execution_functions ) ? sprintf( $error_span, $lang['none'], '' ) : implode( ', ', $execution_functions ),
	),
	array(
		'k' => __( 'WordPress version:', 'boldgrid-backup' ),
		'v' => $wp_version,
	),
	array(
		'k' => __( 'Server time zone:', 'boldgrid-backup' ),
		'v' => $timezone,
	),
	array(
		'k' => __( 'Server date:', 'boldgrid-backup' ),
		'v' => $this->time->get_server_date(),
	),
);

$tests[] = array(
	'heading' => __( 'Backup directory & permissions:', 'boldgrid-backup' ),
);

$tests[] = array(
	'k' => __( 'Possible backup directory parents:', 'boldgrid-backup' ),
	'v' => implode( '<br />', $possible_backup_dirs ),
);

$tests[] = array(
	'k' => __( 'Backup directory:', 'boldgrid-backup' ),
	'v' => ! empty( $backup_directory ) ? $backup_directory : sprintf( $error_span, $lang['not_set'], '' ),
);

// As set of tests only to run if a backup directory is found.
if ( ! empty( $backup_directory ) ) {
	$tests[] = array(
		'k' => __( 'Backup directory without ABSPATH:', 'boldgrid-backup' ),
		'v' => $this->backup_dir->without_abspath,
	);

	$tests[] = array(
		'k' => __( 'Backup directory exists?', 'boldgrid-backup' ),
		'v' => $backup_dir_perms['exists'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], $lang['ensure_dir_perms'] ),
	);

	if ( $backup_dir_perms['exists'] ) {
		$tests[] = array(
			'k' => __( 'Backup directory has read permission?', 'boldgrid-backup' ),
			'v' => $backup_dir_perms['read'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], $lang['ensure_dir_perms'] ),
		);

		$tests[] = array(
			'k' => __( 'Directory listing of backup directory can be fetched?', 'boldgrid-backup' ),
			'v' => $backup_dir_perms['dirlist'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], $lang['dir_of_dir'] ),
		);

		$tests[] = array(
			'k' => __( 'Backup directory has write permission?', 'boldgrid-backup' ),
			'v' => $backup_dir_perms['write'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], $lang['ensure_dir_perms'] ),
		);

		$tests[] = array(
			'k' => __( 'Backup directory has modify permission?', 'boldgrid-backup' ),
			'v' => $backup_dir_perms['rename'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], $lang['ensure_dir_perms'] ),
		);

		$tests[] = array(
			'k' => __( 'Backup directory has delete permission?', 'boldgrid-backup' ),
			'v' => $backup_dir_perms['delete'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], $lang['ensure_dir_perms'] ),
		);
	}
}

$tests[] = array(
	'heading' => 'Available compressors',
);

$tests[] = array(
	'k' => __( 'PHP ZipArchive available?', 'boldgrid-backup' ),
	'v' => ( $this->config->is_compressor_available( 'php_zip' ) ? 'Yes' : 'No' ),
);

if ( 'php_zip' === $this->compressors->get() ) {
	if ( ! $valid_backup_dir ) {
		$status = sprintf( $warning_span, $lang['untested'], $lang['before_test_compress'] );
	} elseif ( $php_zip->test() ) {
		$status = $lang['yes'];
	} else {
		$status = sprintf( $error_span, $lang['no'], '' );
	}

	$tests[] = array(
		'k' => __( 'PHP ZipArchive test passed?', 'boldgrid-backup' ),
		'v' => $status,
	);
}

$tests[] = array(
	'k' => __( 'PclZip available?', 'boldgrid-backup' ),
	'v' => ( $this->config->is_compressor_available( 'pcl_zip' ) ? 'Yes' : 'No' ),
);

if ( 'pcl_zip' === $this->compressors->get() ) {
	if ( ! $valid_backup_dir ) {
		$status = sprintf( $warning_span, $lang['untested'], $lang['before_test_compress'] );
	} elseif ( $pcl_zip->test() ) {
		$status = $lang['yes'];
	} else {
		$status = sprintf( $error_span, $lang['no'], '' );
	}

	$tests[] = array(
		'k' => __( 'PclZip test passed?', 'boldgrid-backup' ),
		'v' => $status,
	);
}

$tests[] = array(
	'k' => __( 'PHP Bzip2 available?', 'boldgrid-backup' ),
	'v' => ( $this->config->is_compressor_available( 'php_bz2' ) ? 'Yes' : 'No' ),
);

$tests[] = array(
	'k' => __( 'PHP Zlib available?', 'boldgrid-backup' ),
	'v' => ( $this->config->is_compressor_available( 'php_zlib' ) ? 'Yes' : 'No' ),
);

$tests[] = array(
	'k' => __( 'PHP LZF available?', 'boldgrid-backup' ),
	'v' => ( $this->config->is_compressor_available( 'php_lzf' ) ? 'Yes' : 'No' ),
);

$tests[] = array(
	'k' => __( 'System TAR available?', 'boldgrid-backup' ),
	'v' => ( $this->config->is_compressor_available( 'system_tar' ) ? 'Yes' : 'No' ),
);

$system_zip_test = new Boldgrid_Backup_Admin_Compressor_System_Zip_Test( $this );
$tests[]         = array(
	'k' => __( 'System ZIP available?', 'boldgrid-backup' ),
	'v' => $system_zip_test->run() ?
		'Yes' :
		sprintf( $warning_span, $lang['no'], $system_zip_test->get_error() ),
);

$tests[] = array(
	'heading' => __( 'Cron', 'boldgrid-backup' ),
);

$tests[] = array(
	'k' => __( 'System crontab available?', 'boldgrid-backup' ),
	'v' => ( $this->test->is_crontab_available() ? $lang['yes'] : sprintf( $warning_span, $lang['no'], '' ) ),
);

$tests[] = array(
	'k' => __( 'PHP allow_url_fopen enabled?', 'boldgrid-backup' ),
	'v' => true === $cli_support['has_url_fopen'] ? $lang['yes'] : sprintf( $warning_span, $lang['no'], '' ),
);

$tests[] = array(
	'k' => __( 'Curl SSL enabled?', 'boldgrid-backup' ),
	'v' => true === $cli_support['has_curl_ssl'] ? $lang['yes'] : sprintf( $warning_span, $lang['no'], '' ),
);

$tests[] = array(
	'k' => __( 'Can fetch a remote url via CLI?', 'boldgrid-backup' ),
	'v' => true === $cli_support['can_remote_get'] ? $lang['yes'] : sprintf( $error_span, $lang['no'], '' ),
);

$tests[] = array(
	'k' => __( 'Cron time zone matches server time zone? <span class="dashicons dashicons-editor-help" data-id="cron-time-zone"></span>', 'boldgrid-backup' ),
	'v' => $this->cron_test->get_preflight_markup(),
);

$tests[] = array(
	'k' => __( 'Cron jobs:', 'boldgrid-backup' ),
	'v' => '<pre>' . implode( '<br /><br />', $our_crons ) . '</pre>',
);

$tests[] = array(
	'k' => __( 'Method used to read cron:', 'boldgrid-backup' ),
	'v' => $this->backup_dir->can_exec_write() ? __( 'Crontab output to file.', 'boldgrid-backup' ) : __( 'Crontab read via exec function.', 'boldgrid-backup' ),
);

$tests[] = array(
	'k' => __( 'WP Cron enabled?', 'boldgrid-backup' ),
	'v' => ( $this->test->wp_cron_enabled() ? 'Yes' : 'No' ),
);

$tests[] = array(
	'k' => __( 'WP Cron jobs:', 'boldgrid-backup' ),
	'v' => '<pre>' . implode( '<br /><br />', $our_wp_crons ) . '</pre>',
);

// Run only these tests if the server is compatible.
if ( $is_functional ) {
	$tests[] = array(
		'heading' => __( 'Disk space', 'boldgrid-backup' ),
	);

	$tests[] = array(
		'k' => __( 'Directory used to calculate disk space:', 'boldgrid-backup' ),
		'v' => $this->home_dir->get_for_disk(),
	);

	$tests[] = array(
		'k' => __( 'Disk total space:', 'boldgrid-backup' ),
		'v' => Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[0] ),
	);

	$tests[] = array(
		'k' => __( 'Disk used space:', 'boldgrid-backup' ),
		'v' => Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[1] ),
	);

	$tests[] = array(
		'k' => __( 'Disk free space:', 'boldgrid-backup' ),
		'v' => Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[2] ),
	);

	if ( ! empty( $disk_space[3] ) ) {
		$tests[] = array(
			'k' => __( 'WordPress directory size:', 'boldgrid-backup' ),
			'v' => Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[3] ),
		);
	}

	// Calculate possible disk free space after a backup, using the entire WP directory size.
	$disk_free_post = $disk_space[2] - $disk_space[3] - $db_size;

	if ( $disk_free_post > 0 ) {
		$tests[] = array(
			'k' => __( 'Estimated free space after backup:', 'boldgrid-backup' ),
			'v' => Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_free_post ),
		);
	} else {
		$tests[] = array(
			'v' => __( 'THERE IS NOT ENOUGH SPACE TO PERFORM A BACKUP!', 'boldgrid-backup' ),
		);
	}

	$tests[] = array(
		'heading' => __( 'Database', 'boldgrid-backup' ),
	);

	$tests[] = array(
		'k' => __( 'Database size:', 'boldgrid-backup' ),
		'v' => Boldgrid_Backup_Admin_Utility::bytes_to_human( $db_size ),
	);

	$tests[] = array(
		'k' => __( 'WordPress database charset:', 'boldgrid-backup' ),
		'v' => $db_charset,
	);

	if ( ! empty( $db_collate ) ) {
		$tests[] = array(
			'k' => __( 'WordPress database collate:', 'boldgrid-backup' ),
			'v' => $db_collate,
		);
	}
}

// If server is not compatible, create fail message.
if ( $is_functional ) {
	$fail_tips = '';
} else {
	$fail_tips = sprintf(
		'
		<p>
			%1$s<br />
			<a href="%3$s" target="_blank" />%2$s</a>
		</p>',
		esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'is not compatible with your hosting account. For further help please see:', 'boldgrid-backup' ) ),
		esc_html( __( 'Making your web hosting account compatible with', 'boldgrid-backup' ) . ' ' . BOLDGRID_BACKUP_TITLE ),
		esc_url( $this->configs['urls']['compatibility'] )
	);
}

// Create the table that will contain the tests data.
$table = '<table class="wp-list-table fixed striped">';
foreach ( $tests as $test ) {
	if ( ! empty( $test['heading'] ) ) {
		$table .= sprintf( '<tr class="heading"><td colspan="2"><h2>%1$s</h2></td></tr>', esc_html( $test['heading'] ) );
	} elseif ( isset( $test['id'] ) && 'pass' === $test['id'] ) {
		$table .= sprintf(
			'<tr><td>%1$s</td><td><strong>%2$s</strong></td></tr>',
			esc_html( $test['k'] ),
			wp_kses( $test['v'], $allowed_tags )
		);
	} elseif ( isset( $test['k'] ) ) {
		$table .= sprintf(
			'<tr><td>%1$s</td><td><em>%2$s</em></td></tr>',
			wp_kses( $test['k'], $allowed_tags ),
			wp_kses( $test['v'], $allowed_tags )
		);
	} else {
		$table .= sprintf(
			'<tr><td colspan="2">%1$s</td></tr>',
			esc_html( $test['v'] )
		);
	}
}
$table .= '</table>';

?>
<div class="functionality-test-section">

	<?php
	$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

	echo $fail_tips; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	echo $table; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	?>

</div>
