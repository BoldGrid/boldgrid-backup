<?php
/**
 * File: local.php
 *
 * The file handles the rendering of the local web server options on the settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.7.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/remote
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.XSS.EscapeOutput
 */

?>

<form method="post">
<?php
	wp_nonce_field( 'bgb-settings-webserver', 'webserver_auth' );

	echo require BOLDGRID_BACKUP_PATH . '/admin/partials/settings/backup-directory.php';
	echo require BOLDGRID_BACKUP_PATH . '/admin/partials/settings/retention.php';
?>
	<p>
		<input type="hidden" name="action" value="save" />
		<input class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'boldgrid-backup' ); ?>" />
		<span class="spinner inline middle hidden"></span>
	</p>
</form>
