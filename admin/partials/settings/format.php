<?php
/**
 * File: format.php
 *
 * Format on settings page.
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

$core = apply_filters( 'boldgrid_backup_get_core', null );

$format_setting = $core->settings->get_setting( 'format' );
$one_selected   = 'one' === $format_setting ? 'selected' : '';
$many_selected  = 'many' === $format_setting ? 'selected' : '';

$select_format = '
	<select name="format">
		<option value="one" ' . $one_selected . '>One zip file</option>
		<option value="many" ' . $many_selected . '>Several zip files</option>
	</select>
';

ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Format', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='format'></span>
	</div>
	<div class="bg-box-bottom">
		<p class="help" data-id="format">
			<?php
			esc_html_e( 'Todo. More info needed', 'boldgrid-backup' );
			?>
		</p>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Format', 'boldgrid-backup' ); ?>:</th>
				<td>
					<?php echo $select_format; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
