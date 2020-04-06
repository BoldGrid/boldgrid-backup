<?php
/**
 * File: auto-backup.php
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
<h1>
<?php
esc_html_e( 'Auto Updates', 'boldgrid-backup' );
?>
</h1>
<p>
	<?php esc_html_e( 'WordPress has the ability to auto update both itself and your plugins and themes. Keeping your software up to date is very important, and this automation helps you more easily do that.', 'boldgrid-backup' ); ?>
</p>
<p>
<?php esc_html_e( 'Total Upkeep adds on top of this functionality by giving you the ability to have backups made before any auto update, and by making it easier to control what is updated and when.', 'boldgrid-backup' ); ?>
</p>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Backup Before Updating', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">
		<p>
		<?php esc_html_e( 'Sometimes updating your software can break your site. It\'s recommended to make a backup before updates, so in the event something goes wrong, you can reasily restore your site.', 'boldgrid-backup' ); ?>
		</p>
		<table class="form-table div-table-body auto-update-settings"><tbody class="div-table-body">
			<tr>
				<th>
					<?php esc_html_e( 'Auto Backup Before Update', 'boldgrid-backup' ); ?>
					<span class='dashicons dashicons-editor-help' data-id='auto-backup'></span>
				</th>
				<td>
					<input id='auto-backup-enabled' type='radio' name='auto_backup' value='1'
					<?php
					if ( ! isset( $settings['auto_backup'] ) ||
						1 === $settings['auto_backup'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Enabled (Recommended)', 'boldgrid-backup' ); ?> &nbsp;
					<input id='auto-backup-disabled' type='radio' name='auto_backup' value='0'
					<?php
					if ( isset( $settings['auto_backup'] ) && 0 === $settings['auto_backup'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>
			<tr class='table-help hide-help' data-id='auto-backup'>
				<td colspan='4'>
					<p>
						<?php
						printf(
							// translators: 1: HTML anchor open tag, 2: HTML anchor close tag.
							esc_html__(
								'Auto Backup Before Update: Automatically perform a backup before WordPress updates. When this feature is enabled, a full backup will be made during the %1$spre_auto_update action%2$s.',
								'boldgrid-backup'
							),
							'<a target="_blank" href="https://developer.wordpress.org/reference/hooks/pre_auto_update/">',
							'</a>'
						);
						?>
					</p>
				</td>
		</tr>
			<tr>
				<th><?php esc_html_e( 'Auto Rollback', 'boldgrid-backup' ); ?><span class='dashicons dashicons-editor-help' data-id='auto-rollback'></span>
				</th>
				<td>
					<input id='auto-rollback-enabled' type='radio' name='auto_rollback' value='1'
					<?php
					if ( ! isset( $settings['auto_rollback'] ) ||
						1 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?> &nbsp;
					<input id='auto-rollback-disabled' type='radio' name='auto_rollback' value='0'
					<?php
					if ( isset( $settings['auto_rollback'] ) && 0 === $settings['auto_rollback'] ) {
						echo ' checked';
					}
					?>
					/> <?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?>
				</td>
			</tr>
			<tr class='table-help hide-help' data-id='auto-rollback'>
				<td colspan='4'>
					<p>
						<?php
						esc_html_e(
							'Auto Rollback: If something goes wrong while performing WordPress updates, automatically restore the site using a backup made before updating WordPress. This feature does not apply to auto updates.',
							'boldgrid-backup'
						);
						?>
					</p>
				</td>
			</tr>
		</tbody></table>
	</div>
</div>
<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
