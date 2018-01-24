<?php
/**
 * BoldGrid Backup - FTP Settings page.
 *
 * The file handles the rendering of the settings page.
 */

$selected = 'selected="selected"';
$ftp_selected  = 'ftp'  === $data['type'] ? $selected : '';
$sftp_selected = 'sftp' === $data['type'] ? $selected : '';

?>

<form method="post">

	<h1><?php echo __( 'BoldGrid Backup - FTP Settings', 'boldgrid-backup' )?></h1>

	<hr />

	<table class="widefat fixed striped">
		<tr>
			<td>
				<?php
					printf( '
						<label for="host">%1$s</label>
						<input type="text" name="host" value="%2$s" minlength="5" title="%3$s" required />
						',
						__( 'FTP Host', 'boldgrid-backup' ),
						$data['host'],
						__( 'FTP host should be in the format of: example.com', 'boldgrid-backup')
					);
				?>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>
				<?php echo __( 'FTP / SFTP', 'boldgrid-backup' ); ?><br />
				<select name="type">
					<option value='ftp'  <?php echo esc_attr( $ftp_selected );  ?>">FTP</option>
					<option value='sftp' <?php echo esc_attr( $sftp_selected ); ?>">SFTP</option>
				</select>
			</td>
			<td>
				<?php echo __( 'FTP Port', 'boldgrid-backup' ); ?><br />
				<input type="number" name="port" value="<?php echo $data['port']; ?>" min="1" required />
			</td>
		</tr>
		<tr>
			<td>
				<?php echo __( 'FTP Username', 'boldgrid-backup' ); ?><br />
				<input type="text" name="user" value="<?php echo $data['user']; ?>" required />
			</td>
			<td>
				<?php echo __( 'FTP Password', 'boldgrid-backup' ); ?><br />
				<input type="password" name="pass" value="<?php echo $data['pass']; ?>" required />
			</td>
		</tr>
		<tr>
			<td>
				<?php echo __( 'Retention (Number of backup archives to retain)', 'boldgrid-backup') ?><br />
				<input type="number" name="retention_count" value="<?php echo $data['retention_count']; ?>" min="1" required/>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>
				<?php echo __( 'Nickname (If you would like to refer to this account as something other than FTP)', 'boldgrid-backup') ?><br />
				<input type="text" name="nickname" value="<?php echo esc_attr( $data['nickname'] ); ?>" />
			</td>
			<td></td>
		</tr>
	</table>

	<p>
		<input type="hidden" name="action" />
		<input class="button button-primary"   type="submit" name="submit" value="<?php echo __( 'Save changes', 'boldgrid-backup' );    ?>" />
		<input class="button button-secondary" type="submit" name="submit" value="<?php echo __( 'Delete settings', 'boldgrid-backup' ); ?>" />
		<span class="spinner inline middle hidden"></span>
	</p>

</form>
