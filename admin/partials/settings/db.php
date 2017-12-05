<?php
/**
 * Database settings.
 *
 * @since 1.5.3
 */

// $core will vary based on how this file is included.
$core = isset( $this->core ) ? $this->core : $this;

$tables = $core->db_omit->format_prefixed_tables();

$all_included = empty( $settings['exclude_tables'] );

return sprintf( '
	<h2>%1$s</h2>

	<table class="form-table">
		<tr id="tables_to_include">
			<th>%2$s</th>
			<td>
				<p class="%3$s">
					%4$s <a id="configure_include_tables" href="">%5$s</a>
				</p>

				<div class="tables %6$s">
					<button id="include_all_tables" class="button button-primary">%7$s</button>
					<button id="exclude_all_tables" class="button button">%8$s</button>

					<div class="include-tables">
						%9$s
					</div>
				</div>
			</td>
		</tr>
	</table>
	',
	/* 1 */ esc_html__( 'Database', 'boldgrid-backup' ),
	/* 2 */ esc_html__( 'Tables to include', 'boldgrid-backup' ),
	/* 3 */ $all_included ? '' : 'hidden',
	/* 4 */ esc_html__( 'All database tables will be included in your backup.', 'boldgrid-backup' ),
	/* 5 */ esc_html__( 'Configure', 'boldgrid-backup' ),
	/* 6 */ $all_included ? 'hidden' : '',
	/* 7 */ esc_html__( 'Include all', 'boldgrid-backup' ),
	/* 8 */ esc_html__( 'Exclude all', 'boldgrid-backup' ),
	/* 9 */ $tables
);

?>
