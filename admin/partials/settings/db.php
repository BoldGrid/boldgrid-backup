<?php
/**
 * Database settings.
 *
 * @since 1.5.3
 */

$tables = $this->core->db_omit->format_prefixed_tables();

$all_included = empty( $settings['exclude_tables'] );

$summary_class = $all_included ? '' : 'hidden';
$tables_class = $all_included ? 'hidden' : '';
?>

<h2><?php esc_html_e( 'Database', 'boldgrid-backup' ); ?></h2>

<table class='form-table'>
	<tr id="tables_to_include">
		<th>
			<?php esc_html_e( 'Tables to include', 'boldgrid-backup' ); ?>
		</th>
		<td>

			<div class="<?php echo $summary_class; ?>">
				<?php
					esc_html_e( 'All database tables will be included in your backup.', 'boldgrid-backup' );
					echo ' ';
					printf( '<a id="configure_include_tables" href="">%1$s</a>', __( 'Configure', 'boldgrid-backup' ) );
				?>
			</div>

			<div class="tables <?php echo $tables_class; ?>">
				<button id="include_all_tables" class="button button-primary"><?php esc_html_e( 'Include all', 'boldgrid-backup' ); ?></button>
				<button id="exclude_all_tables" class="button button"><?php esc_html_e( 'Exclude all', 'boldgrid-backup' ); ?></button>

				<div class='include-tables'>
					<?php echo $tables;?>
				</div>
			</div>
		</td>
	</tr>
</table>