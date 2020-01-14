<?php
/**
 * File: boldgrid-backup-admin-premium.php
 *
 * This file is used to markup premium cards page.
 *
 * @link https://www.boldgrid.com
 * @since 1.12.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$premium = new \Boldgrid\Library\Library\Ui\Premium();

$premium->enqueueScripts();

$premium->cards = $this->get_cards();
?>

<div class='wrap'>
	<h1><?php echo esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Premium', 'boldgrid-backup' ) ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	$premium->printCards();
	?>
</div>
