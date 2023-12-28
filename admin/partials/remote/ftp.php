<?php
/**
 * File: ftp.php
 *
 * This file handles the rendering of the remote FTP/SFTP options on the settings page.
 *
 * The $data array on this page used to fill in the form fields is generated in
 * Boldgrid_Backup_Admin_Ftp_Page->settings().
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

$selected       = 'selected="selected"';
$ftp_selected   = 'ftp' === $data['type'] ? $selected : '';
$ftpes_selected = 'ftpes' === $data['type'] ? $selected : '';
$sftp_selected  = 'sftp' === $data['type'] ? $selected : '';

// FTP mode options.
$active_selected  = 'active' === $data['ftp_mode'] ? $selected : '';
$passive_selected = 'passive' === $data['ftp_mode'] ? $selected : '';
$auto_selected    = empty( $active_selected ) && empty( $passive_selected ) ? $selected : '';
?>
<form method="post">
	<?php wp_nonce_field( 'bgb-settings-ftp', 'ftp_auth' ); ?>
	<h1><?php echo esc_html( BOLDGRID_BACKUP_TITLE . ' - ' . __( 'FTP Settings', 'boldgrid-backup' ) ); ?></h1>
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
						esc_html__( 'FTP Host', 'boldgrid-backup' ),
						esc_attr( $data['host'] ),
						esc_attr__( 'FTP host should be in the format of: example.com', 'boldgrid-backup' )
					);
					?>
			</td>
			<td>
				<?php esc_html_e( 'FTP Port', 'boldgrid-backup' ); ?><br />
				<input type="number" name="port" value="<?php echo esc_attr( $data['port'] ); ?>" min="1" required />
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'FTP Type', 'boldgrid-backup' ); ?><br />
				<select name="type">
					<option value='ftp' <?php echo esc_attr( $ftp_selected ); ?>>FTP</option>
					<option value='ftpes' <?php echo esc_attr( $ftpes_selected ); ?>>FTPES</option>
					<option value='sftp' <?php echo esc_attr( $sftp_selected ); ?>>SFTP</option>
				</select>
			</td>
			<td>
				<?php esc_html_e( 'FTP Mode', 'boldgrid-backup' ); ?><br />
				<select name="ftp_mode">
					<option value='auto' <?php echo esc_attr( $auto_selected ); ?>><?php esc_html_e( 'Auto', 'boldgrid-backup' ); ?></option>
					<option value='active' <?php echo esc_attr( $active_selected ); ?>><?php esc_html_e( 'Active', 'boldgrid-backup' ); ?></option>
					<option value='passive' <?php echo esc_attr( $passive_selected ); ?>><?php esc_html_e( 'Passive', 'boldgrid-backup' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'FTP Username', 'boldgrid-backup' ); ?><br />
				<input type="text" name="user" value="<?php echo esc_attr( $data['user'] ); ?>" required />
			</td>
			<td>
				<?php esc_html_e( 'FTP Password', 'boldgrid-backup' ); ?><br />
				<input type="password" name="pass" value="<?php echo esc_attr( $data['pass'] ); ?>" required />
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php esc_html_e( 'Folder name', 'boldgrid-backup' ); ?><br />
				<?php esc_html_e( 'A folder in your FTP/SFTP server to store your backups, will be created if it doesn\'t exist. Please only use letters, numbers, dashes, and underscores.', 'boldgrid-backup' ); ?><br />
				<input type="text" name="folder_name" value="<?php echo esc_attr( $data['folder_name'] ); ?>" min="1" required pattern="[A-Za-z0-9\-_]+">
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'Retention (Number of backup archives to retain)', 'boldgrid-backup' ); ?><br />
				<input type="number" name="retention_count" value="<?php echo esc_attr( $data['retention_count'] ); ?>" min="1" required/>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'Nickname (If you would like to refer to this account as something other than FTP)', 'boldgrid-backup' ); ?><br />
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
		<input class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'boldgrid-backup' ); ?>" />
		<button class="button button-secondary"><?php esc_html_e( 'Delete settings', 'boldgrid-backup' ); ?></button>
		<span class="spinner inline middle hidden"></span>
	</p>
</form>
