<?php
/**
 * File: full-protection.php
 *
 * Show "Full Protection" on tools page.
 *
 * @link https://www.boldgrid.com
 * @since 1.10.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/tools
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

defined( 'WPINC' ) || die;

ob_start();
?>

<h2 class="site-health-hidden"><?php esc_html_e( 'Am I fully protected?', 'boldgrid-backup' ); ?></h2>
<p><?php esc_html_e( 'To be fully protected, you should have you website backed up regularly with an automatic backup, and you should store those backups offsite.', 'boldgrid-backup' ); ?></p>
<ul>
	<li>
<?php
// Inform the user on whether or not they have scheduled backups.
switch ( $this->core->settings->has_scheduled_backups() ) {
	case true:
		echo '<span class="dashicons dashicons-yes"></span> ' . esc_html__( 'You have configured your backups to run automatically.', 'boldgrid-backup' );
		break;
	case false:
		echo '<span class="dashicons dashicons-no"></span> ' . wp_kses(
			sprintf(
				// translators: 1 the opening anchor to backup schedule settings, 2 its closing anchor tag.
				__( 'You do not have automatic backups configured. Please visit your %1$sbackup schedule settings%2$s to configure an automatic backup.', 'boldgrid-backup' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_schedule' ) ) . '">',
				'</a>'
			),
			array( 'a' => array( 'href' => array() ) )
		);
		break;
}
?>
	</li>
	<li>
<?php
/*
 * Inform the user on whether or not they have remove backups configured.
 *
 * If they don't, adjust the message based on whether or not they have premium.
 */
switch ( $this->core->settings->has_remote_configured() ) {
	case true:
		echo '<span class="dashicons dashicons-yes"></span> ' . esc_html__( 'You have configured your scheduled backups to be stored with a remote storage provider.', 'boldgrid-backup' );
		break;
	case false:
		echo '<span class="dashicons dashicons-no"></span> ' . esc_html__( 'You do not have any remote storage providers enabled. ', 'boldgrid-backup' );

		switch ( $this->core->config->get_is_premium() ) {
			case true:
				echo wp_kses(
					sprintf(
						// translators: 1 the opening anchor to backup storage settings, 2 its closing anchor tag.
						__( 'Please visit your %1$sbackup storage settings%2$s to configure a remote storage provider.', 'boldgrid-backup' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_storage' ) ) . '">',
						'</a>'
					),
					array( 'a' => array( 'href' => array() ) )
				);
				break;
			case false:
				echo wp_kses(
					sprintf(
						// translators: 1 the opening anchor to backup storage settings, 2 its closing anchor tag, 3 the opening anchor tag to a link for upgrading to premium, 4 its closing tag.
						__( 'BoldGrid Backup offers FTP/SFTP as a remote storage provider, which you can configure within your %1$sbackup storage settings%2$s. However, for more robust storage providers, such as Google Drive and Amazon S3, we recommend that you %3$supgrade to BoldGrid Backup Premium%4$s.', 'boldgrid-backup' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_storage' ) ) . '">',
						'</a>',
						'<a href="' . esc_url( $this->core->go_pro->get_premium_url( 'bgbkup-tools-protection' ) ) . '" target="_blank">',
						'</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				break;
		}
		break;
}
?>
	</li>
</ul>
<?php
// Show the final .alert that says yes/no on being fully protected.
switch ( $this->core->settings->has_full_protection() ) {
	case true:
		echo '<div class="notice notice-success inline site-health-hidden"><p>' . esc_html__( 'Yes, your website is fully protected!', 'boldgrid-backup' ) . '</p></div>';
		break;
	case false:
		echo '<div class="notice notice-error inline site-health-hidden"><p>' . esc_html__( 'No, your website is not fully protected. Please follow the steps above to configure full protection.', 'boldgrid-backup' ) . '</p></div>';
		break;
}

// If this is a free user who doesn't have a remote storage option selected, suggest an upgrade.
if ( ! $this->core->settings->has_remote_configured() && ! $this->core->config->get_is_premium() ) {
	$premium_url = $this->core->go_pro->get_premium_url( 'bgbkup-tools-protection' );
	printf(
		'
		<div class="bg-box-bottom premium wp-clearfix site-health-hidden">
			%1$s
			%2$s
		</div>',
		$this->core->go_pro->get_premium_button( $premium_url ), // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		esc_html__( 'Upgrade now to get additional remote storage providers, including Google Drive and Amazon S3!', 'boldgrid-backup' )
	);
}

$output = ob_get_contents();
ob_end_clean();

return $output;
