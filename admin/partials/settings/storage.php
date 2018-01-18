<?php
/**
 * Display "Backup Storage" options.
 *
 * This file is included on the BoldGrid Backup Settings page and helps render
 * the "Backup Storage" section.
 *
 * @since 1.5.2
 */

defined( 'WPINC' ) ? : die;

ob_start();

$storage_locations = array(
	array(
		'title' => __( 'Web Server', 'boldgrid-backup' ),
		'key' => 'local',
		'is_setup' => true,
		'enabled' => ! empty( $settings['remote']['local']['enabled'] ) && true === $settings['remote']['local']['enabled'],
	),
);

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

$premium_box = $this->core->config->is_premium_done ? '' : sprintf( '
	<div class="bg-box-bottom premium">
		<input type="checkbox" disabled="true" /> <strong>%1$s</strong>

		<p>
			%2$s
			%3$s
		</p>
	</div>',
	/* 1 */ __( 'Amazon S3', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button(),
	/* 3 */ __( 'Upgrade to premium for more Storage Locations!', 'boldgrid-backup' )
);

?>

<div class='bg-box'>
	<div class='bg-box-top'>
		<?php echo __( 'Backup Storage', 'boldgrid-backup' ); ?>
		<?php echo '<span class="dashicons dashicons-editor-help" data-id="remote_storage"></span>'; ?>
	</div>
	<div class='bg-box-bottom'>
		<p class="help" data-id="remote_storage">
			<?php echo __( 'The following is a list of storage locations available to store your backup archives on. It is recommended to store your backups on at least 2 different storage locations. You can find more information <a href="admin.php?page=boldgrid-backup-tools&section=section_locations">here</a>.', 'boldgrid-backup' ); ?>
		</p>

		<table id="storage_locations">
		<?php
		foreach( $storage_locations as $location ) {
			$tr = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage-location.php';
			echo $tr;
		}
		?>
		</table>

		<br />
		<p class="hidden" id="no_storage">
			<span class="dashicons dashicons-warning yellow"></span>
			<?php echo __( 'Backup will not occur if no storage locations are selected.', 'boldgrid-backup' ); ?>
		</p>
	</div>
	<?php echo $premium_box; ?>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
?>