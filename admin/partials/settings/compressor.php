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

$core = apply_filters( 'boldgrid_backup_get_core', null );

// An array of compressors we will let the user choose from.
$compressors = [
	[
		'id'   => 'pcl_zip',
		'name' => 'PclZip',
	],
	[
		'id'   => 'php_zip',
		'name' => 'ZipArchive',
	],
	[
		'id'   => 'system_zip',
		'name' => 'System zip',
	],
];

// Create the Compressor options.
$select_compressor = '<select name="compressor">';
foreach ( $compressors as $compressor ) {
	$object = $core->compressors->get_object( $compressor['id'] );

	if ( ! $object->is_available() ) {
		continue;
	}

	$select_compressor .= sprintf(
		'<option value="%1$s" %2$s>%3$s</option>',
		esc_attr( $compressor['id'] ),
		$object->maybe_selected_compressor() ? 'selected="selected"' : '',
		esc_html( $compressor['name'] )
	);
}
$select_compressor .= '</select>';

// Create the Compression level options.
$default_compression_level  = (int) $core->compressors->get_object( 'system_zip' )->default_compression_level;
$selected_compression_level = isset( $settings['compression_level'] ) ? (int) $settings['compression_level'] : $default_compression_level;
$select_compression_level   = '<select name="compression_level">';
foreach ( range( 0, 9 ) as $level ) {
	$is_default  = $default_compression_level === $level;
	$is_selected = $selected_compression_level === $level;

	$select_compression_level .= sprintf(
		'<option value="%1$s" %2$s>%3$s</option>',
		esc_attr( $level ),
		$is_selected ? 'selected="selected"' : '',
		$is_default ? $default_compression_level . ' ( ' . __( 'default' ) . ' )' : esc_html( $level )
	);
}

$compression_level_info = __(
	'The compression level defines how compacted the files will be within the backup file.
	Higher compression levels result in smaller backup files, but take longer to create and use more system resources.
	If you are having trouble getting backups to complete successfully, try using a lower compression level.',
	'boldgrid-backup'
);

ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Compressor', 'boldgrid-backup' ); ?>
		<span class="bgb-unbold">(<?php esc_html_e( 'Advanced', 'boldgrid-bacup' ); ?>)</span>
		<span class='dashicons dashicons-editor-help' data-id='compressor'></span>
	</div>
	<div class="bg-box-bottom">

		<p class="help" data-id="compressor">
			<?php
			esc_html_e( 'These are advanced settings. You do not need to configure this setting.', 'boldgrid-backup' );
			?>
			<span class="compression-level hidden">
				<br/><?php echo $compression_level_info; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
			</span>
		</p>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Compressor', 'boldgrid-backup' ); ?>:</th>
				<td>
					<?php echo $select_compressor; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
			<tr class="compression-level hidden">
				<th><?php esc_html_e( 'Compression Level', 'boldgrid-backup' ); ?>:</th>
				<td>
					<?php echo $select_compression_level; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
