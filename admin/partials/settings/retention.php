<?php
/**
 * Retention settings.
 *
 * @summary Show the retention settings section of the BoldGrid Backup settings page.
 *
 * @since 1.3.1
 */

defined( 'WPINC' ) ? : die;

ob_start();

$is_retention_set = ( isset( $settings['retention_count'] ) );
?>

<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Retention', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">
			<?php esc_html_e( 'Number of backup archives to retain', 'boldgrid-backup' ); ?>

			<select id='retention-count' name='retention_count'>
			<?php
			// Loop through each <option> and print it.
			for ( $x = 1; $x <= 10; $x ++ ) {
				// Is retention set and $x = that set retention?
				$x_is_retention = ( $is_retention_set && $x === $settings['retention_count'] );

				// Is retention not set and $x = the default retention?
				$x_is_default = ( ! $is_retention_set && $this->core->config->get_default_retention() === $x );

				// Should this option be 'selected'?
				$selected = ( ( $x_is_retention || $x_is_default ) ? ' selected' : '' );

				// Should we flag this option as "Requires Upgrade"?
				if( ! $this->core->config->get_is_premium() && ( $this->core->config->get_default_retention() + 1 ) === $x ) {
					$requires_upgrade = esc_html__( '- Requires Upgrade', 'boldgrid-backup' );
				} else {
					$requires_upgrade = '';
				}

				printf(	'<option value="%1$d" %2$s>%1$d</option>',
					$x,
					$selected
				);
			}
			?>
			</select>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
?>