<?php
/**
 * File: storage.php
 *
 * This file is included on the settings page and helps render the "Backup Storage" section.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

ob_start();

// Add the web server storage details.
$storage_locations = [ $this->core->local->get_webserver_details() ];

/**
 * Allow other storage providers to register themselves.
 *
 * @since 1.5.2
 *
 * @param array $storage_locations {
 *     An array of details about our storage locations.
 *
 *     @type string $title     Amazon S3
 *     @type string $key       amazon_s3
 *     @type string $configure admin.php?page=boldgrid-backup-amazon-s3
 *     @type bool   $is_setup  Whether or not this provider is properly configured.
 *     @type bool   $enabled   Whether or not the checkbox should be checked.
 * }
 */
$storage_locations = apply_filters( 'boldgrid_backup_register_storage_location', $storage_locations );

/*
 * Create markup containing a list of premium storage providers. These will only be shown to the user
 * if they are on the free version.
 */
$premium_inputs = '';
if ( ! $this->core->config->is_premium_done ) {
	foreach ( $this->core->configs['premium_remote'] as $provider ) {
		$premium_inputs .= '<p><input type="checkbox" disabled="true" /> ';

		if ( ! empty( $provider['logo_class'] ) ) {
			$premium_inputs .= '<span style="vertical-align:top;" title="' . esc_attr( $provider['title'] ) . '" class="' . esc_attr( $provider['logo_class'] ) . '"></span>';
		} else {
			$premium_inputs .= '<strong>' . esc_html( $provider['title'] ) . '</strong>';
		}

		$premium_inputs .= '</p>';
	}
}

$premium_url = $this->core->go_pro->get_premium_url( 'bgbkup-settings-storage' );
$premium_box = $this->core->config->is_premium_done ? '' : sprintf(
	'
	<div class="bg-box-bottom premium">
		%1$s

		<p>
			%2$s
			%3$s
		</p>
	</div>',
	/* 1 */ $premium_inputs,
	/* 2 */ $this->core->go_pro->get_premium_button( $premium_url ),
	/* 3 */ __( 'Upgrade to Premium for more Storage Locations!', 'boldgrid-backup' )
);

?>

<div class='bg-box'>
	<div class='bg-box-top'>
		<?php esc_html_e( 'Backup Storage', 'boldgrid-backup' ); ?>
		<?php echo '<span class="dashicons dashicons-editor-help" data-id="remote_storage"></span>'; ?>
	</div>
	<div class='bg-box-bottom'>
		<p class="help" data-id="remote_storage">
			<?php
			printf(
				wp_kses(
					// translators: 1: URL address.
					__(
						'The following is a list of storage locations available to store your backup archives on. It is recommended to store your backups on at least 2 different storage locations. You can find more information <a href="%1$s">here</a>.',
						'boldgrid-backup'
					),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( admin_url( 'admin.php?page=boldgrid-backup-tools&section=section_locations' ) )
			);
			?>
		</p>

		<table id="storage_locations">
		<?php
		foreach ( $storage_locations as $location ) {
			$tr = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage-location.php';
			echo $tr; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		?>
		</table>

		<br />
		<p class="hidden" id="no_storage">
			<span class="dashicons dashicons-warning yellow"></span>
			<?php esc_html_e( 'Backup will not occur if no storage locations are selected.', 'boldgrid-backup' ); ?>
		</p>
	</div>
	<?php echo $premium_box; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
