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
	<h1><?php esc_html_e( 'BoldGrid Backup Dashboard', 'boldgrid-backup' ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	include BOLDGRID_BACKUP_PATH . '/admin/partials/banners/dashboard.php';

	$cards = [
		new \Boldgrid\Backup\Admin\Card\Backups(),
		new \Boldgrid\Backup\Admin\Card\Updates(),
	];

	echo '<div class="bglib-card-container">';

	foreach ( $cards as $card ) {
		$card->init();
		$card->print();
	}

	echo '</div>';

	include BOLDGRID_BACKUP_PATH . '/admin/partials/banners/support.php';
	?>
</div>
