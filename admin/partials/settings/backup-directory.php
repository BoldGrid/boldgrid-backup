<?php
/**
 * Show "Backup Directory" on settings page.
 *
 * @since 1.3.1
 */
?>
<h2>
	<?php esc_html_e( 'Backup Directory', 'boldgrid-backup' ); ?>
	<span class='dashicons dashicons-editor-help' data-id='backup-dir'></span>
</h2>

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
			array(  'a' => array( 'href' => array(), 'target' => array() ) )
		),
		esc_url( 'https://www.boldgrid.com/support' )
	);
	echo $link;
	?>
</p>

<table>
	<tr>
		<td><?php esc_html_e( 'Directory to store backup archives', 'boldgrid-backup' ); ?>:</td>
		<td><input id='backup-directory-path' type='text' size='50' name='backup_directory' value='<?php echo $settings['backup_directory']; ?>'></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Move backup archives to new directroy', 'boldgrid-backup' ); ?>:</td>
		<td><input type='checkbox' name='move-backups' checked /></td>
	</tr>
</table>