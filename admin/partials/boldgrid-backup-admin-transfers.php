<?php
/**
 * File: boldgrid-backup-admin-transfers.php
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
?>

<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup Transfers', 'boldgrid-backup' ); ?></h1>
	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	?>
</div>
