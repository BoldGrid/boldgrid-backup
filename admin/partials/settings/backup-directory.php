<?php
/**
 * Show "Backup Directory" on settings page.
 *
 * @since 1.3.1
 */

defined( 'WPINC' ) ? : die;

ob_start();
?>

<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Backup Directory', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='backup-dir'></span>
	</div>
	<div class="bg-box-bottom">

		<p class="help" data-id="backup-dir">
			<?php
			/*
			 * Print this text:
			 *
			 * For security purposes, please do not set this to a publicly available directory. Once you set
			 * this, it is not recommended that you change it again. You can find more help with setting
			 * your backup directory <a>here</a>.
			 */
			$link = sprintf(
				wp_kses(
					__( 'For security purposes, please do not set this to a publicly available directory. Once you set this, it is not recommended that you change it again. You can find more help with setting your backup directory <a href="%s" target="_blank">here</a>.', 'boldgrid-backup' ),
					array( 'a' => array( 'href' => array(), 'target' => array() ) )
				),
				esc_url( $this->core->configs['urls']['setting_directory'] )
			);
			echo $link;
			?>
		</p>

		<table class='backup-directory form-table'>
			<tr>
				<th><?php esc_html_e( 'Directory to store backup archives', 'boldgrid-backup' ); ?>:</th>
				<td><input id='backup-directory-path' type='text' size='40' name='backup_directory' value='<?php echo $settings['backup_directory']; ?>'></td>
			</tr>
			<tr id="move-backups" class="hidden">
				<th><?php esc_html_e( 'If you change this directory, current backups will not show in the list. Would you like us to move the backups to the new directory?', 'boldgrid-backup' ); ?></th>
				<td><input type='checkbox' name='move-backups' checked /></td>
			</tr>
		</table>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
?>
