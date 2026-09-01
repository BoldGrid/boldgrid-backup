<?php
/**
 * File: backup-security.php
 *
 * Show "Backup Security" on settings page.
 *
 * @since      1.12.0
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @link       https://www.boldgrid.com
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;
ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Backup Security', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='backup_security'></span>
	</div>
	<div class="bg-box-bottom">
		<p class="help" data-id="backup_security">
<?php
printf(
	// translators: 1: HTML break tag, 2: HTML strong open tag, 3: HTML strong closing tag.
	esc_html__(
		'Manage security features to help protect backup archives.%1$s%1$s%2$sEncrypt Database%3$s%1$s This premium feature will encrypt the database dump file in backup archives in order to protect sensitive information.',
		'boldgrid-backup'
	),
	'<br />',
	'<strong>',
	'</strong>'
);
?>
		</p>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Encrypt Database', 'boldgrid-backup' ); ?></th>
				<td>
					<input id="encrypt-db-enabled" type="radio" name="encrypt_db" value="1"
<?php
if ( $settings['encrypt_db'] ) {
	echo ' checked'; // Default.
}

if ( ! $is_premium || ! $is_premium_installed || ! $is_premium_active ) {
	echo ' disabled="disabled"';
}
?>
					/> <label for="encrypt-db-enabled"><?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?></label>
					&nbsp; <input id="encrypt-db-disabled" type="radio" name="encrypt_db" value="0"
<?php
if ( ! $settings['encrypt_db'] ) {
	echo ' checked';
}

if ( ! $is_premium || ! $is_premium_installed || ! $is_premium_active ) {
	echo ' disabled="disabled"';
}
?>
					/> <label for="encrypt-db-disabled"><?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?></label>
				</td>
			</tr>
<?php
if ( ! $is_premium ) {
	?>
			<tr><td colspan="2">
				<div class="bg-box-bottom premium wp-clearfix">
	<?php
	$get_premium_url = $this->core->go_pro->get_premium_url( 'bgbkup-settings-security' );
	printf(
		// translators: 1: Get premium button/link, 2: Opening <a> tag, 3: Closing </a> tag.
		esc_html__(
			'%1$sDatabase Encryption provides another level of protection by preventing unauthorized access to your database backup archives.
			%2$sLearn More%3$s',
			'boldgrid-backup'
		),
		$this->core->go_pro->get_premium_button( $get_premium_url, __( 'Get Premium', 'boldgrid-backup' ) ), // phpcs:ignore
		'<a target="_blank" href="https://www.boldgrid.com/support/total-upkeep/encrypt-database-backups/?source=encrypt-database-backups">',
		'</a>'
	);
	?>
				</div>
			</td></tr>
	<?php
} elseif ( ! $is_premium_installed ) {
	?>
			<tr><td colspan="2">
				<div class="bg-box-bottom premium wp-clearfix">
	<?php
	$get_plugins_url = 'https://www.boldgrid.com/central/plugins?source=bgbkup-settings-security';
	printf(
		// translators: 1: Unlock Feature button/link, 2: Premium plugin title.
		esc_html__( '%1$sThe %2$s plugin is required for encryption features.', 'boldgrid-backup' ),
		$this->core->go_pro->get_premium_button( $get_plugins_url, __( 'Unlock Feature', 'boldgrid-backup' ) ), // phpcs:ignore
		esc_html( BOLDGRID_BACKUP_TITLE . ' Premium' )
	);
	?>
				</div>
			</td></tr>
	<?php
} elseif ( ! $is_premium_active ) {
	?>
			<tr><td colspan="2">
	<?php
	printf(
		// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag, 3: Premium plugin title.
		esc_html__( 'The %3$s plugin is not active.  Encryption features are not available.  Please go to the %1$sPlugins%2$s page to activate it.', 'boldgrid-backup' ),
		'<a href="' .
			esc_url( admin_url( 'plugins.php?s=Total%20Upkeep%20Premium&plugin_status=inactive' ) ) .
			'">',
		'</a>',
		esc_html( BOLDGRID_BACKUP_TITLE . ' Premium' )
	);
	?>
		</td></tr>
	<?php
}
?>
		</table>
	</div>
</div>
<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
