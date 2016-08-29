<?php
/**
 * Provide a admin area view for the plugin functionality test report
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

?>
<div class='functionality-test-section wrap'>
<h1><?php esc_html_e( 'BoldGrid Backup', 'boldgrid-backup' ); ?></h1>
<h2><?php esc_html_e( 'Functionality Test Report', 'boldgrid-backup' ); ?></h2>
<p><?php
printf(
	esc_html__( 'Home directory: %s (%s)', 'boldgrid-backup' ),
	$home_dir,
	$home_dir_mode
);
?></p>
<p><?php
	esc_html_e(
		'Home directory writable? ' .
		( $home_dir_writable ? 'Yes' : 'No' ),
		'boldgrid-backup'
	);
?></p>
<p><?php
	esc_html_e(
		'WordPress directory writable? ' .
		( $this->test->get_is_abspath_writable() ? 'Yes' : 'No' ),
		'boldgrid-backup'
	);
?></p>
<p><?php
	esc_html_e(
		'Backup directory exists? ' .
		( empty( $backup_directory ) ? 'No' : 'Yes' ),
		'boldgrid-backup'
	);
?></p>
<p><?php
	esc_html_e(
		'PHP ZipArchive available? ' .
		( $this->config->is_compressor_available( 'php_zip' ) ? 'Yes' : 'No' ),
		'boldgrid-backup'
	);
?></p>
<p><?php
esc_html_e(
	'PHP Bzip2 available? ' .
	( $this->config->is_compressor_available( 'php_bz2' ) ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'PHP Zlib available? ' .
	( $this->config->is_compressor_available( 'php_zlib' ) ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'PHP LZF available? ' .
	( $this->config->is_compressor_available( 'php_lzf' ) ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'System TAR available? ' .
	( $this->config->is_compressor_available( 'system_tar' ) ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'System ZIP available? ' .
	( $this->config->is_compressor_available( 'system_zip' ) ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'PHP in safe mode? ' .
	( $this->test->is_php_safemode() ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'System mysqldump available? ' .
	( $this->test->is_mysqldump_available() ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
esc_html_e(
	'System crontab available? ' .
	( $this->test->is_crontab_available() ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<p><?php
printf(
	esc_html__(
		'WordPress version: %s',
		'boldgrid-backup'
	),
	$wp_version
);
?></p>
<p><?php
esc_html_e(
	'Is WP-CRON enabled? ' .
	( $this->test->wp_cron_enabled() ? 'Yes' : 'No' ),
	'boldgrid-backup'
);
?></p>
<?php
if ( $is_functional ) {
?>
<p><?php
printf(
	esc_html__( 'Disk total space: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[0] )
);
?></p>
<p><?php
printf(
	esc_html__( 'Disk used space: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[1] )
);
?></p>
<p><?php
printf(
	esc_html__( 'Disk free space: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[2] )
);
?></p>
<?php
if ( ! empty( $disk_space[3] ) ) {
?>
<p><?php
printf(
	esc_html__( 'WordPress directory size: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[3] )
);
?></p>
<?php
}
?>
<p><?php
printf(
	esc_html__( 'Database size: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $db_size )
);
?></p>
<p><?php
printf(
	esc_html__( 'WordPress database charset: %s', 'boldgrid-backup' ),
	$db_charset
);
?></p>
<p><?php
printf(
	esc_html__( 'WordPress database collate: %s', 'boldgrid-backup' ),
	$db_collate
);
?></p>
<?php
if ( ! empty( $archive_info['error'] ) ) {
?>
<p><?php
	printf(
		esc_html__( 'Archive error: %s', 'boldgrid-backup' ),
		$archive_info['error']
	);
?></p>
<?php
if ( ! empty( $archive_info['error_code'] ) ) {
?>
<p><?php
		printf(
			esc_html__( 'Compressor error code: %s', 'boldgrid-backup' ),
			$archive_info['error_code']
		);
?></p>
<?php
if ( ! empty( $archive_info['error_message'] ) ) {
?>
<p><?php
			printf(
				esc_html__( 'Compressor error message: %s', 'boldgrid-backup' ),
				$archive_info['error_message']
			);
?></p>
<?php
}
}
}

if ( ! empty( $archive_info['total_size'] ) ) {
?>
<p><?php
	printf(
		esc_html__( 'Backup archive size: %s', 'boldgrid-backup' ),
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] )
	);
?></p>
<?php
	// Calculate possible disk free space after a backup, using the entire WP directory size.
	$disk_free_post = $disk_space[2] - $archive_info['total_size'] - $db_size;
} else {
	// Calculate possible disk free space after a backup, using the entire WP directory size.
	$disk_free_post = $disk_space[2] - $disk_space[3] - $db_size;
}

if ( $disk_free_post > 0 ) {
?>
<p><?php
printf(
	esc_html__( 'Estimated free space after backup: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_free_post )
);
?></p>
<?php
} else {
?>
<p><?php esc_html_e( 'THERE IS NOT ENOUGH SPACE TO PERFORM A BACKUP!', 'boldgrid-backup' ); ?></p>
<?php
}
}
?>
<p><?php
esc_html_e( 'Functionality test status: ' . ( $this->test->run_functionality_tests() ? 'PASS' : 'FAIL' ) );
?></p>
</div>
