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
<h1><?php echo __( 'BoldGrid Backup' ); ?></h1>
<h2><?php echo __( 'Functionality Test Report' ); ?></h2>
<p><?php echo __( 'WordPress directory writable? ' . ( true === $this->test->get_is_abspath_writable() ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'Backup directory exists? ' . ( true === empty( $backup_directory ) ? 'No' : 'Yes' ) ); ?></p>
<p><?php echo __( 'PHP ZipArchive available? ' . ( true === $this->config->is_compressor_available( 'php_zip' ) ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'PHP Bzip2 available? ' . ( true === $this->config->is_compressor_available( 'php_bz2' ) ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'PHP Zlib available? ' . ( true === $this->config->is_compressor_available( 'php_zlib' ) ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'PHP LZF available? ' . ( true === $this->config->is_compressor_available( 'php_lzf' ) ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'System TAR available? ' . ( true === $this->config->is_compressor_available( 'system_tar' ) ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'System ZIP available? ' . ( true === $this->config->is_compressor_available( 'system_zip' ) ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'PHP in safe mode? ' . ( true === $this->test->is_php_safemode() ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'System mysqldump available? ' . ( true === $this->test->is_mysqldump_available() ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'System crontab available? ' . ( true === $this->test->is_crontab_available() ? 'Yes' : 'No' ) ); ?></p>
<p><?php echo __( 'WordPress version: ' ) . $wp_version; ?></p>
<p><?php echo __( 'Is WP-CRON enabled? ' . ( $this->test->wp_cron_enabled() ? 'Yes' : 'No' ) ); ?></p>
<?php
if ( true === $is_functional ) {
?>
<p><?php echo __( 'Disk total space: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[0] ); ?></p>
<p><?php echo __( 'Disk used space: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[1] ); ?></p>
<p><?php echo __( 'Disk free space: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[2] ); ?></p>
<?php
	if ( false === empty( $disk_space[3] ) ) {
?>
<p><?php echo __( 'WordPress directory size: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[3] ); ?></p>
<?php
	}
?>
<p><?php echo __( 'Database size: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $db_size ); ?></p>
<p><?php echo __( 'WordPress database charset: ' ) . $db_charset; ?></p>
<p><?php echo __( 'WordPress database collate: ' ) . $db_collate; ?></p>
<?php
	if ( false === empty( $archive_info['error'] ) ) {
?>
<p><?php echo __( 'Archive error: ' . $archive_info['error'] ); ?></p>
<?php
		if ( false === empty( $archive_info['error_code'] ) ) {
?>
<p><?php echo __( 'Compressor error code: ' ) . $archive_info['error_code']; ?></p>
<?php
			if ( false === empty( $archive_info['error_message'] ) ) {
?>
<p><?php echo __( 'Compressor error message: ' . $archive_info['error_message'] ); ?></p>
<?php
			}
		}
	}

	if ( false === empty( $archive_info['total_size'] ) ) {
?>
<p><?php echo __( 'Backup archive size: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ); ?></p>
<?php
		// Calculate possible disk free space after a backup, using the entire WP directory size.
		$disk_free_post = $disk_space[2] - $archive_info['total_size'] - $db_size;
	} else {
		// Calculate possible disk free space after a backup, using the entire WP directory size.
		$disk_free_post = $disk_space[2] - $disk_space[3] - $db_size;
	}

	if ( $disk_free_post > 0 ) {
?>
<p><?php echo __( 'Estimated free space after backup: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_free_post ); ?></p>
<?php
	} else {
?>
<p><?php echo __( 'THERE IS NOT ENOUGH SPACE TO PERFORM A BACKUP!' ); ?></p>
<?php
	}
}
?>
<p><?php echo __( 'Functionality test status: ' . ( $this->test->get_is_functional() ? 'PASS' : 'FAIL' ) ); ?></p>
</div>
