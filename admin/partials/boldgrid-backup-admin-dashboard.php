<?php
/**
 * File: boldgrid-backup-admin-dashboard.php
 *
 * This file is used to markup dashboard page.
 *
 * @link https://www.boldgrid.com
 * @since xxx
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
?>

<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup and Restore Settings', 'boldgrid-backup' ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	?>

	<p></p>

	<div class="bg-box">
		<div class="bg-box-top">
			<?php esc_html_e( 'Full Site Protection', 'boldgrid-backup' ); ?>
		</div>
		<div class="bg-box-bottom">
			<?php
				echo ( include BOLDGRID_BACKUP_PATH . '/admin/partials/tools/full-protection.php' );
			?>
		</div>
	</div>
</div>
