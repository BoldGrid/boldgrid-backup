<?php
/**
 * File: boldgrid-backup-admin-transfers.php
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
?>

<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup Transfers', 'boldgrid-backup' ); ?></h1>
	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	?>
	<div class="bgbkup-transfers-page">
	<?php
	printf(
		'<div class="bgbkup-transfers-source bg-box">
		<div class="bg-box-top">
		%1$s
		</div>
		<div class="bg-box-bottom">
		%2$s
		</div>
		</div>',
		esc_html__( 'Source', 'boldgrid-backup' ),
		$this->core->archives->get_table() // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	);

	printf(
		'<div class="bgbkup-transfers-destination bg-box">
		<div class="bg-box-top">
		%1$s
		</div>
		<div class="bg-box-bottom">
			<div id="url-import-section" class="wp-upload-form">
				%2$s <input type="text" name="url" placeholder="%3$s" size="30" />
				<input class="button" type="submit" value="%4$s" />
				<span class="spinner"></span>
				<div id="url-import-notice" class="notice notice-success inline"></div>
			</div>
		</div>
		</div>',
		esc_html__( 'Destination', 'boldgrid-backup' ),
		esc_html__( 'From a URL address:', 'boldgrid-backup' ),
		esc_attr__( 'Download URL address', 'boldgrid-backup' ),
		esc_attr__( 'Upload', 'boldgrid-backup' )
	);
	?>
	</div>
</div>
