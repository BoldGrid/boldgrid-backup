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

$size_limit = __( 'File upload size limit', 'boldgrid-backup' ) . ': ' .
	Boldgrid_Backup_Admin_Utility::bytes_to_human(
		Boldgrid_Backup_Admin_Utility::get_upload_limit()
	);

$size_info = __(
	'Files uploaded from your computer are limited to a maximum size.  To change the limit, you may be able to modify your server\'s php.ini or .htaccess file.  Please ask your web hosting provider if you need assistance.  Uploads from URL addresses do not have a size limit.',
	'boldgrid-backup'
);

$upload_info = sprintf(
	// translators: 1: Plugin title.
	__(
		'You can upload a backup file that was created with %1$s.
			If you choose to restore an uploaded file from a different web location (URL), then we will try to ensure that references to URL address are updated.
			There may be times when some items may need to be updated manually.',
		'boldgrid-backup'
	),
	BOLDGRID_BACKUP_TITLE
);

$core              = apply_filters( 'boldgrid_backup_get_core', null );
$backup_identifier = $core->get_backup_identifier();

$backup_id_notice = sprintf(
	// translators: 1: HTML tag, 2: Filename part, 3: Backup identifier, 4: File extension, 5: Archive filename, 6: Backup directory path, 7: Plugin title.
	__(
		'Your %7$s id is %3$s. This backup id is used to determine if a backup archive file is associated with this WordPress installation.
		Manually uploaded archive files must have filenames starting with "%2$s", contain the %7$s id "%3$s", and end with "%4$s", to be recognized.%1$s%1$s
		For example: %5$s%1$s%1$s
		Manually uploaded archive files should be uploaded to: %6$s',
		'boldgrid-backup'
	),
	'<br />',
	'boldgrid-backup-',
	$backup_identifier,
	'.zip',
	'boldgrid-backup-' . $backup_identifier . '-my_backup_file.zip',
	$settings['backup_directory'],
	BOLDGRID_BACKUP_TITLE
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

	<div id="url-import-section" class="wp-upload-form">
		<p><?php esc_html_e( 'Import from a download link:', 'boldgrid-backup' ); ?></p>
		<input type="text" name="url"
			placeholder="<?php esc_attr_e( 'Download URL address', 'boldgrid-backup' ); ?>" size="30" />
		<?php wp_nonce_field( 'upload_archive_file' ); ?>
		<?php wp_nonce_field( 'boldgrid_backup_restore_archive', '_wpnonce_restore' ); ?>
		<input class="button" type="submit" value="<?php esc_attr_e( 'Upload', 'boldgrid-backup' ); ?>" />
		<span class='spinner'></span>
		<div id="url-import-notice" class="notice notice-success inline"></div>
	</div>

	<div id="upload-archive-section" class="wp-upload-form">
		<p><?php esc_html_e( 'From a file on your computer:', 'boldgrid-backup' ); ?></p>
		<form id="upload-archive-form" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo esc_attr( $max_file_size ); ?>" />
			<input type="hidden" name="uploading" value="1" />
			<?php wp_nonce_field( 'upload_archive_file' ); ?>
			<input name="file" type="file" />
			<input class="button" type="submit" value="Upload" />
			<span class='spinner'></span>
		</form>

		<div class="error_messages">

			<p id="file_too_large" class="hidden">
				<span class="dashicons dashicons-warning yellow"></span> <?php esc_html_e( 'The file you selected is too large.', 'boldgrid-bacup' ); ?>
			</p>

			<p id="bad_filename" class="hidden">
				<span class="dashicons dashicons-warning yellow"></span> <?php esc_html_e( 'Invalid file name. Please choose a valid backup file.', 'boldgrid-bacup' ); ?>
			</p>

			<p id="bad_extension" class="hidden">
				<span class="dashicons dashicons-warning yellow"></span> <?php esc_html_e( 'Invalid file format. Please choose a .zip file.', 'boldgrid-bacup' ); ?>
			</p>
		</div>
	</div>

	<p class="install-help">
		<?php esc_html_e( 'Have a large file to upload or want to use FTP?', 'boldgrid-backup' ); ?>
		<span class="dashicons dashicons-editor-help" data-id="backup-id"></span>
	</p>

	<div class="help wp-upload-form" data-id="backup-id">
		<?php echo $backup_id_notice; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
	</div>

</div>
