<?php
/**
 * File: ftp.php
 *
 * The file handles the rendering of the remove FTP/SFTP options on the settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/remote
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

$selected      = 'selected="selected"';
$ftp_selected  = 'ftp' === $data['type'] ? $selected : '';
$sftp_selected = 'sftp' === $data['type'] ? $selected : '';

?>

<form method="post">

	<h1><?php echo esc_attr__( 'BoldGrid Backup - FTP Settings', 'boldgrid-backup' ); ?></h1>

	<hr />

	<table class="widefat fixed striped">
		<tr>
			<td>
				<?php
					printf(
						'
						<label for="host">%1$s</label>
						<input type="text" name="host" value="%2$s" minlength="5" title="%3$s" required />
						',
						esc_attr__( 'FTP Host', 'boldgrid-backup' ),
						esc_attr( $data['host'] ),
						esc_attr__( 'FTP host should be in the format of: example.com', 'boldgrid-backup' )
					);
					?>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>
				<?php echo esc_html__( 'FTP / SFTP', 'boldgrid-backup' ); ?><br />
				<select name="type">
					<option value='ftp' <?php echo esc_attr( $ftp_selected ); ?> >FTP</option>
					<option value='sftp' <?php echo esc_attr( $sftp_selected ); ?> >SFTP</option>
				</select>
			</td>
			<td>
				<?php echo esc_html__( 'FTP Port', 'boldgrid-backup' ); ?><br />
				<input type="number" name="port" value="<?php echo esc_attr( $data['port'] ); ?>" min="1" required />
			</td>
		</tr>
		<tr>
			<td>
				<?php echo esc_html__( 'FTP Username', 'boldgrid-backup' ); ?><br />
				<input type="text" name="user" value="<?php echo esc_attr( $data['user'] ); ?>" required />
			</td>
			<td>
				<?php echo esc_html__( 'FTP Password', 'boldgrid-backup' ); ?><br />
				<input type="password" name="pass" value="<?php echo esc_attr( $data['pass'] ); ?>" required />
			</td>
		</tr>
		<tr>
			<td>
				<?php echo esc_html__( 'Retention (Number of backup archives to retain)', 'boldgrid-backup' ); ?><br />
				<input type="number" name="retention_count" value="<?php echo esc_attr( $data['retention_count'] ); ?>" min="1" required/>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>
				<?php echo esc_html__( 'Nickname (If you would like to refer to this account as something other than FTP)', 'boldgrid-backup' ); ?><br />
				<input type="text" name="nickname" value="<?php echo esc_attr( $data['nickname'] ); ?>" />
			</td>
			<td></td>
		</tr>
	</table>

	<p>
	<?php
	echo $this->core->lang['icon_warning']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	esc_html_e(
		'With automated FTP your credentials must be stored here in your WordPress. They will be encrypted in the database and this protects them significantly, but they could be decrypted in the unlikely event of a compromise. We recommended you use a separate FTP user and password specifically for backups.',
		'boldgrid-backup'
	);
	?>
	</p>

	<p>
		<input type="hidden" name="action" value="save" />
		<input class="button button-primary" type="submit" value="<?php echo esc_attr__( 'Save changes', 'boldgrid-backup' ); ?>" />
		<button class="button button-secondary"><?php echo esc_attr__( 'Delete settings', 'boldgrid-backup' ); ?></button>
		<span class="spinner inline middle hidden"></span>
	</p>

</form>
