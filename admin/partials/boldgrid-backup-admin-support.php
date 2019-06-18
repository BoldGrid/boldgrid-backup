<?php
/**
 * File: boldgrid-backup-admin-support.php
 *
 * @link https://www.boldgrid.com
 * @since 1.10.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.NamingConventions.ValidHookName
 */

defined( 'WPINC' ) || die;

$allowed_tags = [
	'a'  => [
		'href'   => [],
		'target' => [],
	],
	'ul' => [],
	'li' => [],
];

$nav      = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
$reseller = get_option( 'boldgrid_reseller' );

if ( ! empty( $reseller ) ) {
	// Is under a reseller.
	$premium_markup = sprintf(
		wp_kses(
			/* translators: 1: HTML tags, 2: Anchored URL address for reseller support, 3: Reseller title/name, 4: HTML anchor close tag, 5: HTML line item close tag. */
			__(
				'%1$sYou can receive premium support from your official reseller %2$s%3$s%4$s%5$s',
				'boldgrid-backup'
			),
			$allowed_tags
		),
		'<ul><li>',
		'<a href="' . esc_url( $reseller['reseller_support_url'] ) . '" target="_blank">',
		$reseller['reseller_title'],
		'</a>',
		'</li>'
	);

	if ( ! empty( $reseller['reseller_phone'] ) ) {
		$premium_markup .= sprintf(
			wp_kses(
				/* translators: 1: HTML line item open tag, 2: Reseller telephone number, 3: HTML line item close tag. */
				__(
					'%1$sTelephone: %2$s%3$s',
					'boldgrid-backup'
				),
				$allowed_tags
			),
			'<li>',
			$reseller['reseller_phone'],
			'</li>'
		);
	}

	if ( ! empty( $reseller['reseller_email'] ) ) {
		$premium_markup .= sprintf(
			wp_kses(
				/* translators: Reseller email address. */
				__(
					'%1$sEmail: %2$s%3$s',
					'boldgrid-backup'
				),
				$allowed_tags
			),
			'<li>',
			'<a href="mailto:' . esc_attr( $reseller['reseller_email'] ) . '">' . esc_attr( $reseller['reseller_email'] ) . '</a>',
			'</li>'
		);
	}

	$premium_markup .= '</ul>';
} elseif ( $this->core->config->is_premium_done ) {
	// Is BoldGrid Premium.
	$premium_markup = sprintf(
		wp_kses(
			/* translators: 1: URL address for BoldGrid Central. */
			__(
				'<ul><li>You can receive premium support using <a href="%1$s" target="_blank">BoldGrid Central</a></li>',
				'boldgrid-backup'
			),
			$allowed_tags
		),
		'https://www.boldgrid.com/central/'
	);
} else {
	$premium_button = $this->core->go_pro->get_premium_button(
		$this->core->go_pro->get_premium_url( 'bgbkup-support' )
	);
	$premium_markup = '<div class="bgbkup-upgrade-message"><p>' .
		esc_html__( 'Upgrade to receive premium support from BoldGrid', 'boldgrid-backup' ) .
		'</p><p>' . $premium_button . '</p></div>';
}

?>

<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup Support', 'boldgrid-backup' ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	?>
	<div class="bgbkup-support-page">
	<?php
	printf(
		'<div class="bgbkup-free-support bg-box">
		<div class="bg-box-top">
		%1$s
		</div>
		<div class="bg-box-bottom">
		<ul>
		<li>%2$s</li>
		<li>%3$s</li>
		<li>%4$s</li>
		</ul>
		</div>
		</div>',
		esc_html__( 'Free Support', 'boldgrid-backup' ),
		sprintf(
			wp_kses(
				/* translators: URL address for the WordPress.org boldgrid-backup plugin support forum. */
				__( 'Ask on <a href="%1$s" target="_blank">WordPress.org</a>', 'boldgrid-backup' ),
				$allowed_tags
			),
			'https://wordpress.org/support/plugin/boldgrid-backup/'
		),
		sprintf(
			wp_kses(
				/* translators: URL address for the WordPress.org boldgrid-backup plugin support forum. */
				__( 'Browse our <a href="%1$s" target="_blank">Support Guides</a>', 'boldgrid-backup' ),
				$allowed_tags
			),
			'https://www.boldgrid.com/support/boldgrid-backup/'
		),
		sprintf(
			wp_kses(
				/* translators: URL address for the WordPress.org boldgrid-backup plugin support forum. */
				__( 'Join <a href="%1$s" target="_blank">Team Orange User Group</a> on Facebook', 'boldgrid-backup' ),
				$allowed_tags
			),
			'https://www.facebook.com/groups/BGTeamOrange'
		)
	);

	printf(
		'<div class="bgbkup-premium-support bg-box">
		<div class="bg-box-top">
		%1$s
		</div>
		<div class="bg-box-bottom">
		%2$s
		</div>
		</div>',
		esc_html__( 'Premium Support', 'boldgrid-backup' ),
		$premium_markup // phpcs:ignore WordPress.XSS.EscapeOutput
	);
	?>
	</div>
	<?php
	echo '<hr />';
	?>
</div>
