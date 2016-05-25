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

echo __( 'BoldGrid Backup - Functionality test report:' );
?><br />
<?php echo __( 'WordPress directory writable? ' . ( true === $this->test->get_is_abspath_writable() ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'Backup directory exists? ' . ( true === empty( $backup_directory ) ? 'No' : 'Yes' ) ); ?><br />
<?php echo __( 'PHP ZipArchive available? ' . ( true === $this->config->is_compressor_available( 'php_zip' ) ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'PHP Bzip2 available? ' . ( true === $this->config->is_compressor_available( 'php_bz2' ) ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'PHP Zlib available? ' . ( true === $this->config->is_compressor_available( 'php_zlib' ) ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'PHP LZF available? ' . ( true === $this->config->is_compressor_available( 'php_lzf' ) ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'System TAR available? ' . ( true === $this->config->is_compressor_available( 'system_tar' ) ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'System ZIP available? ' . ( true === $this->config->is_compressor_available( 'system_zip' ) ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'PHP in safe mode? ' . ( true === $this->test->is_php_safemode() ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'System mysqldump available? ' . ( true === $this->test->is_mysqldump_available() ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'System crontab available? ' . ( true === $this->test->is_crontab_available() ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'WordPress version: ' ) . $wp_version; ?><br />
<?php echo __( 'Is WP-CRON enabled? ' . ( $this->test->wp_cron_enabled() ? 'Yes' : 'No' ) ); ?><br />
<?php echo __( 'Functionality test status: ' . ( $this->test->get_is_functional() ? 'PASS' : 'FAIL' ) ); ?><br />
<?php
if ( true === $is_functional ) {
?>
<?php echo __( 'Disk total space: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[0] ); ?><br />
<?php echo __( 'Disk used space: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[1] ); ?><br />
<?php echo __( 'Disk free space: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[2] ); ?><br />
<?php
	if ( false === empty( $disk_space[3] ) ) {
		echo __( 'WordPress directory size: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_space[3] )
			. '<br />';
	}
?>
<?php echo __( 'Database size: ' ) . Boldgrid_Backup_Admin_Utility::bytes_to_human( $db_size ); ?><br />
<?php echo __( 'WordPress database charset: ' ) . $db_charset ?>;<br />
<?php echo __( 'WordPress database collate: ' ) . $db_collate; ?><br />
<?php
	if ( false === empty( $archive_info['error'] ) ) {
		echo __( 'Archive error: ' . $archive_info['error'] ) . '<br />';

		if ( false === empty( $archive_info['error_code'] ) ) {
			echo __( 'Compressor error code: ' ) . $archive_info['error_code'] . '<br />';

			if ( false === empty( $archive_info['error_message'] ) ) {
				echo __( 'Compressor error message: ' . $archive_info['error_message'] ) . '<br />';
			}
		}
	}

	if ( false === empty( $archive_info['total_size'] ) ) {
		echo __( 'Backup archive size: ' ) .
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ) . '<br />';

		// Calculate possible disk free space after a backup, using the entire WP directory size.
		$disk_free_post = $disk_space[2] - $archive_info['total_size'] - $db_size;
	} else {
		// Calculate possible disk free space after a backup, using the entire WP directory size.
		$disk_free_post = $disk_space[2] - $disk_space[3] - $db_size;
	}

	if ( $disk_free_post > 0 ) {
		echo __( 'Estimated free space after backup: ' ) .
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $disk_free_post ) . '<br />';
	} else {
		echo __( 'THERE IS NOT ENOUGH SPACE TO PERFORM A BACKUP!' ) . '<br />';
	}

}

echo __( 'End of functionality test report.' ) . '<br />';

?>
