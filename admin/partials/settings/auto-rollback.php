<?php
/**
 * File: auto-rollback.php
 *
 * Show "Auto Rollback" on settings page.
 *
 * @since      1.7.0
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @link       https://www.boldgrid.com
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;
ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Auto Rollback', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='auto-rollback'></span>
	</div>
	<div class="bg-box-bottom">
		<p class='help' data-id='auto-rollback'>
			<?php
			esc_html_e(
				'If something goes wrong while performing WordPress updates, automatically restore the site using a backup made before updating WordPress. This feature does not apply to auto updates.',
				'boldgrid-backup'
			);
			?>
		</p>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Auto Rollback', 'boldgrid-backup' ); ?></th>
				<td>
					<input id='auto-rollback-enabled' type='radio' name='auto_rollback' value='1'
					<?php
					if ( ! isset( $settings['auto_rollback'] ) ||
						1 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp; <input
					id='auto-rollback-disabled' type='radio' name='auto_rollback'
					value='0'
					<?php
					if ( isset( $settings['auto_rollback'] ) && 0 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>
		</table>
	</div>
</div>
<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
