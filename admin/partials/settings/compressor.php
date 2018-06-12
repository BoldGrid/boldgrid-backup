<?php
/**
 * File: compressor.php
 *
 * Compressor on settings page.
 * This page is only shown for testers who have appropriate hooks in place.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

ob_start();

$selected = 'selected="selected"';

$available_compressors = array( 'php_zip', 'pcl_zip' );

// Settings for compressor.
$pcl_zip_selected = empty( $settings['compressor'] ) || 'pcl_zip' === $settings['compressor'] ?
	$selected : '';

$php_zip_selected = ! empty( $settings['compressor'] ) && 'php_zip' === $settings['compressor'] ?
	$selected : '';

$php_zip_option = ! in_array( 'php_zip', $available_compressors, true ) ?
	'' : sprintf( '<option value="php_zip" %1$s>ZipArchive</option>', $php_zip_selected );

// Settings for extractor.
$extractor_pcl_zip_selected = ! empty( $settings['extractor'] ) && 'pcl_zip' === $settings['extractor'] ?
	$selected : '';

$extractor_php_zip_selected = empty( $settings['extractor'] ) || 'php_zip' === $settings['extractor'] ?
	$selected : '';

$extractor_php_zip_option = ! in_array( 'php_zip', $available_compressors, true ) ?
	'' : sprintf( '<option value="php_zip" %1$s>ZipArchive</option>', $extractor_php_zip_selected );

?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Compressor & Extractor', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='compressor'></span>
	</div>
	<div class="bg-box-bottom">

		<p class="help" data-id="compressor">
			<?php
			esc_html_e( 'These are advanced settings. You do not need to configure this setting.', 'boldgrid-backup' );
			?>
		</p>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Compressor', 'boldgrid-backup' ); ?>:</th>
				<td>
					<select name="compressor">
						<option value='pcl_zip' <?php echo esc_attr( $pcl_zip_selected ); ?> >PclZip</option>
						<?php echo $php_zip_option; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Extractor', 'boldgrid-backup' ); ?>:</th>
				<td>
					<select name="extractor">
						<option value='pcl_zip' <?php echo esc_attr( $extractor_pcl_zip_selected ); ?> >PclZip</option>
						<?php echo $extractor_php_zip_option; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
					</select>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
