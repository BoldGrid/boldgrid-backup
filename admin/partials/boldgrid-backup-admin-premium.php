<?php
/**
 * File: boldgrid-backup-admin-premium.php
 *
 * This file is used to markup premium cards page.
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$premium_listing = new \Boldgrid\Library\Library\Ui\PremiumFeatures\Listing();

$premium_listing->enqueueScripts();

$premium_listing->cards = $this->get_cards();

$premium_box = $this->core->config->is_premium_done ? '' : sprintf(
	'
	<div class="bg-box-bottom premium">
		<p>
			<span class="bg-box-title">%1$s</span>
			%2$s
		</p>
		<p>
			%3$s
	</div>',
	/* 1 */ __( 'Total Upkeep Premium', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button( $this->core->go_pro->get_premium_url( 'bgbkup-premium-features' ) ),
	/* 3 */ __( 'Upgrade to Total Upkeep Premium to take advantage of these additional features' )
);

?>

<div class='wrap'>
	<h1><?php echo esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Premium', 'boldgrid-backup' ) ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	echo $premium_box; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	$premium_listing->printCards();

	Boldgrid\Library\Library\NoticeCounts::setRead( 'boldgrid-backup-premium-features' );
	?>
</div>
