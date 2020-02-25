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
		</p>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Compressor', 'boldgrid-backup' ); ?>:</th>
				<td>
					<?php echo $select_compressor; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
