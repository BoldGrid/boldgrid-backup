<?php
/**
 * Database settings.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

// $core will vary based on how this file is included.
$core = isset( $this->core ) ? $this->core : $this;

$in_modal = isset( $in_modal ) && true === $in_modal;

// If we are in a modal, by default we want all tables to be backed up.
$tables = $core->db_omit->format_prefixed_tables();

// Determine if the "Backup all tables" option should be checked.
$all_included = empty( $settings['exclude_tables'] );
$type         = $core->db_omit->get_settings_type();
$full_checked = 'full' === $type || $all_included;

$checked = 'checked="checked"';

$types = sprintf(
	'
	<input type="radio" name="table_inclusion_type" value="full"   %3$s>%1$s<br>
	<input type="radio" name="table_inclusion_type" value="custom" %4$s>%2$s
	',
	/* 1 */ __( 'Backup all tables (full backup)', 'boldgrid-backup' ),
	/* 2 */ __( 'Custom Backup', 'boldgrid-backup' ),
	/* 3 */ $in_modal || $full_checked ? $checked : '',
	/* 4 */ $in_modal || $full_checked ? '' : $checked
);

$buttons = sprintf(
	'
	<button id="include_all_tables" class="button button-primary">%1$s</button>
	<button id="exclude_all_tables" class="button button">%2$s</button>
	',
	/* 1 */ esc_html__( 'Include all', 'boldgrid-backup' ),
	/* 2 */ esc_html__( 'Exclude all', 'boldgrid-backup' )
);

$status = sprintf(
	'
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

return sprintf(
	'
	<div class="bg-box" id="table_inclusion">
		<div class="bg-box-top">
			%1$s
		</div>
		<div class="bg-box-bottom wp-clearfix">
			<p>%2$s</p>

			<div id="table_inclusion_config">
				%3$s

				<p>%4$s</p>

				<div class="include-tables">
					%5$s
				</div>
			</div>
		</div>
	</div>
	',
	/* 1 */ esc_html__( 'Database', 'boldgrid-backup' ),
	/* 2 */ $types,
	/* 3 */ $status,
	/* 4 */ $buttons,
	/* 5 */ $tables
);
