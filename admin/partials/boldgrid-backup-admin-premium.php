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

Boldgrid\Library\Library\NoticeCounts::set_read('boldgrid-backup-premium-features');

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$premium = new \Boldgrid\Library\Library\Ui\Premium();

$premium->enqueueScripts();

$premium->cards = $this->get_cards();

$premium_box = $this->core->config->is_premium_done ? '' : sprintf(
	'
	<div class="bg-box-bottom premium">
		<p>
			%1$s
			%2$s
		</p>
		<p>
			%3$s
	</div>',
	/* 1 */ __( 'Total Upkeep Premium', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button( $premium_url ),
	/* 3 */ __( 'Upgrade to Total Upkeep Premium to take advantage of these additional features')
);

?>

<div class='wrap'>
	<h1><?php echo esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Premium', 'boldgrid-backup' ) ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	echo $premium_box;

	$premium->printCards();
	?>
</div>
