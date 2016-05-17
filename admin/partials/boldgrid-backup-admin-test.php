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
<?php echo __( 'WordPress directory writable? ' ) . ( true === $this->is_abspath_writable ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'Backup directory exists? ' ) . ( true === empty( $this->backup_directory ) ? 'No' : 'Yes' ); ?><br />
<?php echo __( 'Available compressors:' ); ?><br />
<?php echo __( 'PHP ZipArchive? ' ) . ( true === $this->is_compressor_available( 'php_zip' ) ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'PHP Bzip2? ' ) . ( true === $this->is_compressor_available( 'php_bz2' ) ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'PHP Zlib? ' ) . ( true === $this->is_compressor_available( 'php_zlib' ) ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'PHP LZF? ' ) . ( true === $this->is_compressor_available( 'php_lzf' ) ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'System TAR? ' ) . ( true === $this->is_compressor_available( 'system_tar' ) ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'System ZIP? ' ) . ( true === $this->is_compressor_available( 'system_zip' ) ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'PHP in safe mode? ' ) . ( true === $this->is_php_safemode ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'System mysqldump available? ' ) . ( true === $this->mysqldump_available ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'System crontab available? ' ) . ( true === $this->is_crontab_available ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'WordPress version: ' ) . $wp_version; ?><br />
<?php echo __( 'Is WP-CRON enabled? ' ) . ( $this->wp_cron_enabled ? 'Yes' : 'No' ); ?><br />
<?php echo __( 'Functionality test status: ' ) . ( $this->is_functional ? 'PASS' : 'FAIL' ); ?><br />
<?php echo __( 'Disk total space: ' ) . $this->bytes_to_human( $disk_space[0] ); ?><br />
<?php echo __( 'Disk used space: ' ) . $this->bytes_to_human( $disk_space[1] ); ?><br />
<?php echo __( 'Disk free space: ' ) . $this->bytes_to_human( $disk_space[2] ); ?><br />
<?php echo __( 'WordPress directory size: ' ) . $this->bytes_to_human( $disk_space[3] ); ?><br />
<?php echo __( 'Database size: ' ) . $this->bytes_to_human( $db_size ); ?><br />
<?php echo __( 'WordPress database charset: ' ) . $db_charset ?>;<br />
<?php echo __( 'WordPress database collate: ' ) . $db_collate; ?><br />
<?php
if ( false === empty( $archive_info['error'] ) ) {
	echo __( 'Compressor error: ' ) . $archive_info['error'] . '<br />';

	if ( false === empty( $archive_info['error_code'] ) ) {
		echo __( 'Compressor error code: ' ) . $archive_info['error_code'] . '<br />';

		if ( false === empty( $archive_info['error_message'] ) ) {
			echo __( 'Compressor error message: ' ) . $archive_info['error_message'] . '<br />';
		}
	}
}

if ( false === empty( $archive_info['total_size'] ) ) {
	echo __( 'Backup archive size: ' ) . $this->bytes_to_human( $archive_info['total_size'] ) .
		 '<br />';

	// Calculate possible disk free space after a backup, using the entire WP directory size.
	$disk_free_post = $disk_space[2] - $archive_info['total_size'] - $db_size;
} else {
	// Calculate possible disk free space after a backup, using the entire WP directory size.
	$disk_free_post = $disk_space[2] - $disk_space[3] - $db_size;
}

if ( $disk_free_post > 0 ) {
	echo __( 'Estimated free space after backup: ' ) . $this->bytes_to_human( $disk_free_post );
} else {
	echo __( 'THERE IS NOT ENOUGH SPACE TO PERFORM A BACKUP!' );
}

echo '<br />' . __( 'End of functionality test report.' ) . '<br />';

?>
