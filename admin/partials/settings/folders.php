<?php
/**
 * Folder settings.
 *
 * Which files and folders should be included / excluded?
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nonce = wp_create_nonce( 'folder_exclusion_preview' );

$tags = array(
	'strong' => array(),
	'a'      => array(
		'href' => array(),
		'id'   => array(),
	),
);

// $core will vary based on how this file is included.
$core = isset( $this->core ) ? $this->core : $this;

$checked = 'checked="checked"';

$in_modal = isset( $in_modal ) && true === $in_modal;

$using_defaults = $core->folder_exclusion->is_using_defaults();

$markup = '<table class="form-table bulk-action-notice" id="folder_exclusion"><tbody>';

// TR for the header and intro.
$tr_header = sprintf(
	'
	<p>
		<input type="radio" name="folder_exclusion_type" value="full" %3$s>%1$s<br>
		<input type="radio" name="folder_exclusion_type" value="custom" %4$s>%2$s
	</p>',
	/* 1 */ esc_html__( 'Backup all files (full backup)', 'boldgrid-backup' ),
	/* 2 */ esc_html__( 'Custom Backup', 'boldgrid-backup' ),
	/* 3 */ $in_modal || $using_defaults ? $checked : '',
	/* 4 */ ! $in_modal && ! $using_defaults ? $checked : ''
);

// This markup for the legend. TR for the help text.
$table_legend = sprintf(
	'
	<table class="folder_exclude_help wp-list-table widefat fixed striped pages">
		<tr>
			<th>*</th>
			<td>
				%1$s<br />
				<span class="example">%2$s</span>
			</td>
		</tr>
		<tr>
			<th>WPCORE</th>
			<td>
				%3$s<br />
				<span class="example">%4$s</span>
			</td>
		</tr>
		<tr>
			<th>/</th>
			<td>
				%5$s<br />
				<span class="example">%6$s</span>
			</td>
		</tr>
		<tr>
			<th>,</th>
			<td>
				%7$s<br />
				<span class="example">%8$s</span>
			</td>
		</tr>
	</table>
	',
	/* 1 */ wp_kses( __( 'Use an asterisk(*) as a wildcard.', 'boldgrid-inspirations' ), $tags ),
	/* 2 */ wp_kses( __( 'For example, <strong>wp-*</strong> will match <strong>wp-</strong>content, <strong>wp-</strong>admin, and <strong>wp-</strong>includes.', 'boldgrid-inspirations' ), $tags ),
	/* 3 */ wp_kses( __( 'Use WPCORE to specify WordPress core files.', 'boldgrid-bacukp' ), $tags ),
	/* 4 */ wp_kses( __( 'This includes the wp-admin and wp-includes folders, as well as all of the core WordPress files in the root directory of WordPress.', 'boldgrid-backup' ), $tags ),
	/* 5 */ wp_kses( __( 'Begin a filter with a forward slash(/) to specify the file or folder must be in the root WordPress directory.', 'boldgrid-backup' ), $tags ),
	/* 6 */ wp_kses( __( 'For example, <strong>/index.php</strong> will not match index.php files in subdirectories, such as wp-includes/index.php', 'boldgrid-backup' ), $tags ),
	/* 7 */ wp_kses( __( 'Use a comma to add more than one filter.', 'boldgrid-backup' ), $tags ),
	/* 8 */ wp_kses( __( 'For example, <strong>wp-admin,wp-includes</strong> will backup both the wp-admin folder and the wp-includes folder.', 'boldgrid-backup' ), $tags )
);

// Examples.
$table_examples = sprintf(
	'
	<table class="folder_exclude_help wp-list-table widefat fixed striped pages">
		<tr>
			<th><a href="#" class="folder_exclude_sample" data-include="%1$s" data-exclude="%2$s">%3$s</a></th>
			<td>%4$s</td>
		</tr>
		<tr>
			<th><a href="#" class="folder_exclude_sample" data-include="/wp-content" data-exclude="">%5$s</a></th>
			<td>%6$s</td>
		</tr>
		<tr>
			<th><a href="#" class="folder_exclude_sample" data-include="/wp-content/plugins,/wp-content/themes" data-exclude="">%7$s</a></th>
			<td>%8$s</td>
		</tr>
		<tr>
			<th><a href="#" class="folder_exclude_sample" data-include="*" data-exclude="WPCORE">%9$s</a></th>
			<td>%10$s</td>
		</tr>
	</table>
	',
	/* 1 */ $core->folder_exclusion->default_include,
	/* 2 */ $core->folder_exclusion->default_exclude,
	/* 3 */ wp_kses( __( 'Default', 'boldgrid-backup' ), $tags ),
	/* 4 */ wp_kses( __( 'These are the <strong>default</strong> settings. Include all core WordPress files and the wp-content folder (which includes your plugins, themes, and uploads).', 'boldgrid-backup' ), $tags ),
	/* 5 */ wp_kses( __( 'Example 1', 'boldgrid-backup' ), $tags ),
	/* 6 */ wp_kses( __( 'Backup only the wp-content folder.', 'boldgrid-backup' ), $tags ),
	/* 7 */ wp_kses( __( 'Example 2', 'boldgrid-backup' ), $tags ),
	/* 8 */ wp_kses( __( 'Backup only the plugins and themes folders.', 'boldgrid-backup' ), $tags ),
	/* 9 */ wp_kses( __( 'Example 3', 'boldgrid-backup' ), $tags ),
	/* 10 */ wp_kses( __( 'Backup everything except WordPress core files.', 'boldgrid-backup' ), $tags )
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
	/* 1 */ $core->lang['icon_success'] . wp_kses( __( 'You are using the the default <strong>Include</strong> and <strong>Exclude</strong> settings. Backups created will contain all of the needed files to restore your website in the event of an emergency.', 'boldgrid-backup' ), $tags ),
	/* 2 */ $core->lang['icon_warning'] . wp_kses( __( 'You are not using the the default <strong>Include</strong> and <strong>Exclude</strong> settings. Backups created may not contain all of the needed files to restore your website in the event of an emergency.', 'boldgrid-backup' ), $tags ),
	/* 3 */ sprintf(
		'<a href="#" class="folder_exclude_sample" data-include="%1$s" data-exclude="%2$s">%3$s</a>',
		/* 1A */ $core->folder_exclusion->default_include,
		/* 2A */ $core->folder_exclusion->default_exclude,
		/* 3A */ wp_kses( __( 'Use default settings', 'boldgrid-backup' ), $tags )
	)
);

// TR for the help text.
$tr_help = sprintf(
	'
	<div id="folder_misc_info">
		<p>
			<span class="dashicons dashicons-editor-help" data-id="folder_exclude_inputs" style="float:left;margin-right:4px;"></span>
			%2$s
		</p>

		<div class="help" data-id="folder_exclude_inputs" style="padding:20px 0px 20px 20px;margin:15px 0px 0px 0px;">
			%3$s
			%4$s

			<p>&nbsp;</p>
			<p>&nbsp;</p>

			%5$s
			%6$s
		</div>

		<hr class="separator-small" />

		%7$s
	</div>
	',
	/* 1 */ $using_defaults ? 'hidden' : '',
	/* 2 */ wp_kses( __( 'Use the <strong>Include</strong> and <strong>Exclude</strong> settings to adjust which files are included in your backup. Click the <strong>Preview</strong> button to see which files will be included in your backup based on your settings.', 'boldgrid-backup' ), $tags ),
	/* 3 */ wp_kses( __( 'The following special characters can be used in your <strong>include</strong> and <strong>exclude</strong> filters:', 'boldgrid-backup' ), $tags ),
	/* 4 */ $table_legend,
	/* 5 */ wp_kses( __( 'For help with creating a filter, click one of the examples. This will fill in the <strong>Include</strong> and <strong>Exclude</strong> settings below.', 'boldgrid-backup' ), $tags ),
	/* 6 */ $table_examples,
	/* 7 */ $status
);

// TR for the include.
$tr_include = sprintf(
	'
	<tr class="%3$s">
		<th style="padding-top:0px;">
			%1$s
		</th>
		<td style="padding-top:0px;">
			<input type="text" name="folder_exclusion_include" value="%2$s" class="regular-text" />
		</td>
	</tr>
	',
	/* A1 */ wp_kses( __( 'Include', 'boldgrid-backup' ), $tags ),
	/* A2  */ esc_attr( $settings['folder_exclusion_include'] ),
	/* A1  */ $using_defaults ? 'hidden' : ''
);

// TR for the exclude.
$tr_exclude = sprintf(
	'
	<tr class="%3$s">
		<th>
			%1$s
		</th>
		<td>
			<input type="text" name="folder_exclusion_exclude" value="%2$s" class="regular-text" />
		</td>
	</tr>
	',
	/* 1 */ wp_kses( __( 'Exclude', 'boldgrid-backup' ), $tags ),
	/* 2 */ esc_attr( $settings['folder_exclusion_exclude'] ),
	/* A1  */ $using_defaults ? 'hidden' : ''
);

// TR for the preview.
$tr_preview = sprintf(
	'
		<tr class="%1$s">
			<th></th>
			<td>
				<p>
					<input type="hidden" name="folder_exclusion_nonce" value="%2$s" />
					<button id="exclude_folders_button" class="button">%3$s</button>
				</p>

				<p class="status hidden"></p>

				<div id="exclude_folders_preview" class="hidden">

					<div class="tablenav">
						<input type="text" id="folder_exclusion_filter" placeholder="%4$s" />
						<div class="tablenav-pages"></div>
					</div>

					<ul></ul>
				</div>
			</td>
		</tr>
	',
	/* 1 */ $using_defaults ? 'hidden' : '',
	/* 2 */ $nonce,
	/* 3 */ wp_kses( __( 'Preview', 'boldgrid-backup' ), $tags ),
	/* 4 */ esc_attr( __( 'Filter below results', 'boldgrid-backup' ) )
);

$markup = sprintf(
	'
	<div class="bg-box" id="folder_exclusion">
		<div class="bg-box-top">
			%1$s
		</div>
		<div class="bg-box-bottom">
			%2$s
			%3$s
			<table class="form-table">
				<tbody>
					%4$s
					%5$s
					%6$s
				</tbody>
			</table>
		</div>
	</div>',
	/* 1 */ esc_html__( 'Files and Folders', 'boldgrid-backup' ),
	/* 2 */ $tr_header,
	/* 3 */ $tr_help,
	/* 4 */ $tr_include,
	/* 5 */ $tr_exclude,
	/* 6 */ $tr_preview
);

return $markup;
