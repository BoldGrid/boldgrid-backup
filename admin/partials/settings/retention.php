<?php
/**
 * File: retention.php
 *
 * Show the retention settings section of the settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.3.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

// Get retention count setting.  Limit 1-99, default is from config.
$retention_count = isset( $settings['retention_count'] ) && 99 >= $settings['retention_count'] ?
	$settings['retention_count'] : $this->core->config->get_default_retention();

if ( $retention_count > 99 ) {
	$retention_count = 99;
}

ob_start();
?>

<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Retention', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">
<table class='form-table'>
	<tr>
		<th><label for="retention_count">
		<?php esc_html_e( 'Number of backup archives to retain', 'boldgrid-backup' ); ?> (1 - 99):
		</label></th>
		<td><input type="number" id='retention-count' name='retention_count' min="1" max="99"
			value="<?php echo esc_attr( $retention_count ); ?>" required /></td>
	</tr>
</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
