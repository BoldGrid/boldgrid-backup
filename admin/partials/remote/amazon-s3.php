<?php
/**
 * BoldGrid Backup - Amazon S3 Settings page.
 *
 * The file handles the rendering of the settings page.
 *
 * @param string $key       Access Key ID.
 * @param string $secret    Secret Access Key.
 * @param string $bucket_id Bucket ID.
 */

?>

<form method="post">

	<h1><?php echo __( 'BoldGrid Backup - Amazon S3 Settings', 'boldgrid-backup' )?></h1>

	<table class="form-table">
		<tr>
			<th><?php echo __( 'Access Key ID', 'boldgrid-backup' ); ?></th>
			<td><input type="text" name="key" value="<?php echo $key; ?>" /></td>
		</tr>
		<tr>
			<th><?php echo __( 'Secret Access Key', 'boldgrid-backup' ); ?></th>
			<td><input type="text" name="secret" value="<?php echo $secret; ?>" /></td>
		</tr>
		<tr>
			<th><?php echo __( 'Bucket ID', 'boldgrid-backup' ); ?></th>
			<td><input type="text" name="bucket_id" value="<?php echo $bucket_id; ?>" /></td>
		</tr>
		<tr>
			<th><?php echo __( 'Retention (Number of backup archives to retain)', 'boldgrid-backup') ?></th>
			<td><input type="text" name="retention_count" value="<?php echo $retention_count; ?>" /></td>
		</tr>
	</table>

	<input class="button button-primary" type="submit" name="submit" value="<?php echo __( 'Save changes', 'boldgrid-backup' );?>" />
	<input class="button" type="submit" name="submit" value="<?php echo __( 'Delete settings', 'boldgrid-backup' );?>" />

</form>
