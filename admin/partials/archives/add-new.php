<?php
/**
 * File: add-new.php
 *
 * This file contains the markup necessary to upload a new backup archive.
 * It follows the same structure as the "Upload Plugin" section of plugins.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archives
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$max_file_size = Boldgrid_Backup_Admin_Utility::get_upload_limit();

$size_limit = __( 'File size limit', 'boldgrid-backup' ) . ': ' .
	Boldgrid_Backup_Admin_Utility::bytes_to_human(
		Boldgrid_Backup_Admin_Utility::get_upload_limit()
	);

$size_info = __(
	'To change the limit, you may be able to modify your server\'s php.ini or .htaccess file.  Please ask your web hosting provider if you need assistance.',
	'boldgrid-backup'
);

$upload_info = __(
	'You can upload a backup file that was created with BoldGrid Backup.
			If you choose to restore an uploaded file from a different web location (URL), then we will try to ensure that references to URL address are updated.
			There may be times when some items may need to be updated manually.',
	'boldgrid-backup'
);

$backup_id_notice = sprintf(
	// translators: 1: HTML tag, 2: Filename part, 3: Backup identifier, 4: File extension, 5: Archive filename, 6: Backup directory path.
	__(
		'Your BoldGrid Backup id is %3$s. This backup id is used to determine if a backup archive file is associated with this WordPress installation.
		Manually uploaded archive files must have filenames starting with "%2$s", contain the BoldGrid Backup id "%3$s", and end with "%4$s", to be recognized.%1$s%1$s
		For example: %5$s%1$s%1$s
		Manually uploaded archive files should be uploaded to: %6$s',
		'boldgrid-backup'
	),
	'<br />',
	'boldgrid-backup-',
	$backup_identifier,
	'.zip',
	'boldgrid-backup-' . $backup_identifier . '-my_backup_file.zip',
	$settings['backup_directory']
);

?>

<div id="add_new" class="upload-plugin">

	<p class="install-help">
		<?php esc_html_e( 'Upload a Backup Archive', 'boldgrid-backup' ); ?>
		<span class="dashicons dashicons-editor-help" data-id="upload-backup"></span>
	</p>

	<p class="help wp-upload-form" data-id="upload-backup">
<?php
printf(
	'%1$s<br />%2$s<br /><br />%3$s',
	esc_html( $size_limit ),
	esc_html( $size_info ),
	esc_html( $upload_info )
);
?>
	</p>

	<div id="upload-archive-section" class="wp-upload-form">
		<form id="upload-archive-form" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo esc_attr( $max_file_size ); ?>" />
			<input type="hidden" name="uploading" value="1" />
			<?php wp_nonce_field( 'upload_archive_file' ); ?>
			<input name="file" type="file" />
			<input class="button" type="submit" value="Upload" />
			<span class='spinner'></span>
		</form>

		<p id="file_too_large" class="hidden">
			<span class="dashicons dashicons-warning yellow"></span> <?php echo esc_html__( 'The file you selected is too large.', 'boldgrid-bacup' ); ?>
		</p>

		<p id="bad_extension" class="hidden">
			<span class="dashicons dashicons-warning yellow"></span> <?php echo esc_html__( 'Invalid file format. Please choose a .zip file.', 'boldgrid-bacup' ); ?>
		</p>
	</div>

	<p class="install-help">
		<?php esc_html_e( 'Have a large site or want to FTP?', 'boldgrid-backup' ); ?>
		<span class="dashicons dashicons-editor-help" data-id="backup-id"></span>
	</p>

	<div class="help wp-upload-form" data-id="backup-id">
		<?php echo $backup_id_notice; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
	</div>

</div>
