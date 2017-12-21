<?php
/**
 * Database settings.
 *
 * @since 1.5.3
 */

defined( 'WPINC' ) ? : die;

// $core will vary based on how this file is included.
$core = isset( $this->core ) ? $this->core : $this;

$tables = $core->db_omit->format_prefixed_tables();

$all_included = empty( $settings['exclude_tables'] );

$status = sprintf( '
	<p class="yes-default">
		%1$s
	</p>
	<p class="no-default">
		%2$s %3$s
	</p>
	',
	/* 1 */ $core->lang['icon_success'] . wp_kses( __( 'All database tables will be backed up.', 'boldgrid-backup' ), $tags ),
	/* 2 */ $core->lang['icon_warning'] . wp_kses( __( 'You are not backing up all database tables. Backups created may not contain all of the needed tables to restore your website in the event of an emergency.', 'boldgrid-backup' ), $tags ),
	/* 3 */ sprintf( '<a href="#" class="include-all">%1$s</a>', __( 'Backup all tables', 'boldgrid-backup' ) )
);

return sprintf( '
	<table class="form-table" id="table_inclusion">
		<tr>
			<th>
				<h2>%1$s</h2>
			</th>
			<td>
				<p>
					%2$s<a id="configure_include_tables" href="">%5$s</a>
				</p>

				<div id="tables_to_include" class="hidden">
					%10$s

					<p>
						<button id="include_all_tables" class="button button-primary">%7$s</button>
						<button id="exclude_all_tables" class="button button">%8$s</button>
					</p>

					<div class="include-tables">
						%9$s
					</div>
				</div>
			</td>
		</tr>
	</table>
	',
	/* 1 */ esc_html__( 'Database', 'boldgrid-backup' ),
	/* 2 */ ! $all_included ? $core->lang['icon_warning'] : $core->lang['icon_success'],
	/* 3 */ null, //$all_included ? '' : 'hidden',
	/* 4 */ null,
	/* 5 */ esc_html__( 'Configure', 'boldgrid-backup' ),
	/* 6 */ null, //$all_included ? 'hidden' : '',
	/* 7 */ esc_html__( 'Include all', 'boldgrid-backup' ),
	/* 8 */ esc_html__( 'Exclude all', 'boldgrid-backup' ),
	/* 9 */ $tables,
	/* 10 */ $status
);

?>
