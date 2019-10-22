<?php
/**
 * File: backup-security.php
 *
 * Show "Backup Security" on settings page.
 *
 * @since      1.x.0
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
			esc_html_e(
				'Manage security features to help protect backup archives.',
				'boldgrid-backup'
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
					?>
					/> <label for="encrypt-db-enabled"><?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?></label>
					&nbsp; <input id="encrypt-db-disabled" type="radio" name="encrypt_db" value="0"
					<?php
					if ( ! $settings['encrypt_db'] ) {
							echo ' checked';
					}
					?>
					/> <label for="encrypt-db-disabled"><?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?></label>
				</td>
			</tr>
		</table>
	</div>
</div>
<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
