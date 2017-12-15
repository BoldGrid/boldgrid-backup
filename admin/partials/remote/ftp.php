<?php
/**
 * BoldGrid Backup - FTP Settings page.
 *
 * The file handles the rendering of the settings page.
 */

?>

<form method="post">

	<h1><?php echo __( 'BoldGrid Backup - FTP Settings', 'boldgrid-backup' )?></h1>

	<table class="form-table">
		<tr>
			<th><?php echo __( 'FTP Host', 'boldgrid-backup' ); ?></th>
			<td><input type="text" name="host" value="<?php echo $host; ?>" /></td>
		</tr>
		<tr>
			<th><?php echo __( 'FTP Username', 'boldgrid-backup' ); ?></th>
			<td><input type="text" name="user" value="<?php echo $user; ?>" /></td>
		</tr>
		<tr>
			<th><?php echo __( 'FTP Password', 'boldgrid-backup' ); ?></th>
			<td><input type="password" name="pass" value="<?php echo $pass; ?>" /></td>
		</tr>
		<tr>
			<th><?php echo __( 'Retention (Number of backup archives to retain)', 'boldgrid-backup') ?></th>
			<td><input type="text" name="retention_count" value="<?php echo $retention_count; ?>" /></td>
		</tr>
	</table>

	<input class="button button-primary" type="submit" name="submit" value="<?php echo __( 'Save changes', 'boldgrid-backup' );?>" />
	<input class="button" type="submit" name="submit" value="<?php echo __( 'Delete settings', 'boldgrid-backup' );?>" />

</form>
