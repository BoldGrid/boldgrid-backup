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

<table class='backup-directory'>
	<tr>
		<td><?php esc_html_e( 'Directory to store backup archives', 'boldgrid-backup' ); ?>:</td>
		<td><input id='backup-directory-path' type='text' size='40' name='backup_directory' value='<?php echo $settings['backup_directory']; ?>'></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'If you change this directory, current backups will not show in the list. Would you like us to move the backups to the new directory?', 'boldgrid-backup' ); ?></td>
		<td><input type='checkbox' name='move-backups' checked /></td>
	</tr>
</table>