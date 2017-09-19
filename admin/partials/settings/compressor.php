<?php
/**
 * Compressor on settings page.
 *
 * @since 1.5.1
 */

/**
 * Compressor on settings page.
 *
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 */

$selected = 'selected="selected"';

// Settings for compressor.
$pcl_zip_selected = empty( $settings['compressor'] ) || 'pcl_zip' === $settings['compressor'] ? $selected : '';
$php_zip_selected = ! empty( $settings['compressor'] ) && 'php_zip' === $settings['compressor'] ? $selected : '';
$php_zip_option = ! in_array( 'php_zip', $available_compressors ) ? '' : sprintf( '<option value="php_zip" %1$s>ZipArchive</option>', $php_zip_selected );

// Settings for extractor.
$extractor_pcl_zip_selected = ! empty( $settings['extractor'] ) && 'pcl_zip' === $settings['extractor'] ? $selected : '';
$extractor_php_zip_selected = empty( $settings['extractor'] ) || 'php_zip' === $settings['extractor'] ? $selected : '';
$extractor_php_zip_option = ! in_array( 'php_zip', $available_compressors ) ? '' : sprintf( '<option value="php_zip" %1$s>ZipArchive</option>', $extractor_php_zip_selected );

?>
<h2>
	<?php esc_html_e( 'Compressor & Extractor', 'boldgrid-backup' ); ?>
	<span class='dashicons dashicons-editor-help' data-id='compressor'></span>
</h2>

<p class="help" data-id="compressor">
	<?php
	echo __( 'These are advanced settings. You do not need to configure this setting.', 'boldgrid-backup' );
	?>
</p>

<table class="form-table">
	<tr>
		<th><?php echo __( 'Compressor', 'boldgrid-backup' );?>:</th>
		<td>
			<select name="compressor">
				<option value='pcl_zip' <?php echo $pcl_zip_selected; ?> >PclZip</option>
				<?php echo $php_zip_option; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo __( 'Extractor', 'boldgrid-backup' ) ?>:</th>
		<td>
			<select name="extractor">
				<option value='pcl_zip' <?php echo $extractor_pcl_zip_selected; ?> >PclZip</option>
				<?php echo $extractor_php_zip_option; ?>
			</select>
		</td>
	</tr>
</table>
