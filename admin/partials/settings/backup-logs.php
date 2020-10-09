<?php
/**
 * File: backup-logs.php
 *
 * Backup logs on settings page.
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$checked = Boldgrid_Backup_Admin_Filelist_Analyzer::is_enabled() ? 'checked' : '';

ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Backup Logs', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">

		<table class="form-table">
			<tr>
				<th>
					<strong><?php esc_html_e( 'Filelist Analysis', 'boldgrid-backup' ); ?></strong>
					<p style="font-weight: normal;">
						<?php esc_html_e( 'Include a filelist analysis log file with each backup. This log file will show you the largest files and directories that were added to your backup, and can be useful for troubleshooting failed backups.', 'boldgrid-backup' ); ?>
					</p>
				</th>
				<td>
					<input type="checkbox" name="<?php echo esc_attr( Boldgrid_Backup_Admin_Filelist_Analyzer::$settings_key ); ?>" value="1" <?php echo esc_attr( $checked ); ?>>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
