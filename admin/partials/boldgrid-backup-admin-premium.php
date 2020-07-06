<?php
/**
 * File: boldgrid-backup-admin-premium.php
 *
 * This file is used to markup premium cards page.
 *
 * @link https://www.boldgrid.com
 * @since 1.13.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$dashboard = new \Boldgrid\Library\Library\Ui\Dashboard();

$dashboard->cards = $this->get_cards();

$dashboard->classes = 'bglib-smaller';

$premium_box = $this->core->config->is_premium_done ? '' : sprintf(
	'
	<div class="bg-box-bottom premium" style="margin:15px 0;">
		<p style="margin-top:0;">
			<span class="bg-box-title">%1$s</span>
			%2$s
		</p>
		<p style="margin-bottom:0;">
			%3$s
		</p>
	</div>',
	/* 1 */ esc_html__( 'Total Upkeep Premium', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button( $this->core->go_pro->get_premium_url( 'bgbkup-premium-features' ) ),
	/* 3 */ esc_html__(
		'Give your website data the protection it deserves with Total Upkeep Premium.
	Enjoy automated remote backups to Google Drive and Amazon S3, individual file restoration,
	the ability to restore from historical versions, premium support and more.',
		'boldgrid-backup'
	)
);

require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

echo $premium_box; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

$dashboard->printCards();
