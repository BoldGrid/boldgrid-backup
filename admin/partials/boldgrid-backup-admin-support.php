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
	'a' => [
		'href'   => [],
		'target' => [],
	],
];

$nav      = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
$reseller = get_option( 'boldgrid_reseller' );

if ( ! empty( $reseller ) ) {
	// Is under a reseller.
	$premium_markup = '<ul><li>' . sprintf(
		wp_kses(
			/* translators: 1: Anchored URL address for reseller support, 2: Reseller title/name, 3: HTML anchor close tag. */
			__(
				'You can receive premium support from your official reseller %1$s%2$s%3$s',
				'boldgrid-backup'
			),
			$allowed_tags
		),
		'<a href="' . esc_url( $reseller['reseller_support_url'] ) . '" target="_blank">',
		$reseller['reseller_title'],
		'</a>'
	) . '</li>';

	if ( ! empty( $reseller['reseller_phone'] ) ) {
		$premium_markup .= '<li>' . sprintf(
			wp_kses(
				/* translators: 1: Reseller telephone number */
				__(
					'Telephone: %1$s',
					'boldgrid-backup'
				),
				$allowed_tags
			),
			$reseller['reseller_phone']
		) . '</li>';
	}

	if ( ! empty( $reseller['reseller_email'] ) ) {
		$premium_markup .= '<li>' . sprintf(
			wp_kses(
				/* translators: Reseller email address. */
				__(
					'Email: %1$s',
					'boldgrid-backup'
				),
				$allowed_tags
			),
			'<a href="mailto:' . esc_attr( $reseller['reseller_email'] ) . '">' . esc_attr( $reseller['reseller_email'] ) . '</a>'
		) . '</li>';
	}

	$premium_markup .= '</ul>';
} elseif ( $this->core->config->is_premium_done ) {
	// Is BoldGrid Premium.
	$premium_markup = '<ul><li>' . sprintf(
		wp_kses(
			/* translators: 1: HTML anchor for the URL address for BoldGrid Central, 2: HTML anchor close tag.. */
			__(
				'Create a ticket at %1$sBoldGrid Support%2$s',
				'boldgrid-backup'
			),
			$allowed_tags
		),
		'<a href="https://support.boldgrid.com/open.php" target="_blank">',
		'</a>'
	) . '</li>';
} else {
	$premium_button = $this->core->go_pro->get_premium_button(
		$this->core->go_pro->get_premium_url( 'bgbkup-support' )
	);
	$premium_markup = '<div class="bgbkup-upgrade-message"><p>' .
		esc_html__( 'Upgrade to receive Premium support from BoldGrid', 'boldgrid-backup' ) .
		'</p><p>' . $premium_button . '</p></div>';
}

echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';
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
