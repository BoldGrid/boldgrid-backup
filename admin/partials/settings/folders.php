<?php
/**
 * Folder settings.
 *
 * Which files and folders should be included / excluded?
 *
 * @since 1.5.4
 */

$nonce = wp_create_nonce( 'folder_exclusion_preview' );

$tags = array(
	'strong' => array(),
);

// $core will vary based on how this file is included.
$core = isset( $this->core ) ? $this->core : $this;

$markup = sprintf( '
	<input type="hidden" name="folder_exclusion_nonce" value="%2$s" />

	<table class="form-table">
		<tr>
			<th>
				<h2>%1$s</h2>
				<span class="dashicons dashicons-editor-help" data-id="folder_exclude_inputs"></span>
			</th>
			<td>
				<p>%8$s</p>
					<div class="help" data-id="folder_exclude_inputs" style="padding:20px 0px 20px 20px;margin:15px 0px 0px 0px;">

					%33$s

					<table class="folder_exclude_help wp-list-table widefat fixed striped pages">
						<tr>
							<th>*</th>
							<td>
								%21$s<br />
								<span class="example">%22$s</span>
							</td>
						</tr>
						<tr>
							<th>WPCORE</th>
							<td>
								%23$s<br />
								<span class="example">%24$s</span>
							</td>
						</tr>
						<tr>
							<th>/</th>
							<td>
								%25$s<br />
								<span class="example">%26$s</span>
							</td>
						</tr>
						<tr>
							<th>,</th>
							<td>
								%27$s<br />
								<span class="example">%28$s</span>
							</td>
						</tr>
					</table>

					<p>&nbsp;</p>
					<p>&nbsp;</p>

					%9$s
					<table class="folder_exclude_help wp-list-table widefat fixed striped pages">
						<tr>
							<th><a href="#" class="folder_exclude_sample" data-include="%31$s" data-exclude="%32$s">%29$s</a></th>
							<td>%30$s</td>
						</tr>
						<tr>
							<th><a href="#" class="folder_exclude_sample" data-include="/wp-content" data-exclude="">%10$s</a></th>
							<td>%14$s</td>
						</tr>
						<tr>
							<th><a href="#" class="folder_exclude_sample" data-include="/wp-content/plugins,/wp-content/themes" data-exclude="">%11$s</a></th>
							<td>%15$s</td>
						</tr>
						<tr>
							<th><a href="#" class="folder_exclude_sample" data-include="*" data-exclude="WPCORE">%12$s</a></th>
							<td>%16$s</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<th>
				%17$s
			</th>
			<td>
				<input type="text" name="folder_exclusion_include" value="%5$s" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th>
				%19$s
			</th>
			<td>
				<input type="text" name="folder_exclusion_exclude" value="%7$s" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<p>
					<button id="exclude_folders_button" class="button">%18$s</button>
				</p>

				<div id="exclude_folders_preview">
					<p class="status hidden"></p>
					<div class="tablenav hidden">
						<div class="tablenav-pages"></div>
					</div>

					<ul class="hidden" style="max-height:300px;overflow:auto; background: #fff; padding:5px;border:1px solid #ddd;"></ul>
				</div>
			</td>
		</tr>
	</table>
	',
	/* 1  */ esc_html__( 'Files and Folders', 'boldgrid-backup' ),
	/* 2  */ $nonce,
	/* 3  */ null,
	/* 4  */ null,
	/* 5  */ esc_attr( $settings['folder_exclusion_include'] ),
	/* 6  */ null,
	/* 7  */ esc_attr( $settings['folder_exclusion_exclude'] ),
	/* 8  */ wp_kses( __( 'Use the <strong>Include</strong> and <strong>Exclude</strong> settings to adjust which files are included in your backup. Click the <strong>Preview</strong> button to see which files will be included in your backup based on your settings.', 'boldgrid-backup' ), $tags ),
	/* 9  */ wp_kses( __( 'For help with creating a filter, click one of the examples. This will fill in the <strong>Include</strong> and <strong>Exclude</strong> settings below.', 'boldgrid-backup' ), $tags ),
	/* 10 */ wp_kses( __( 'Example 1', 'boldgrid-backup' ), $tags ),
	/* 11 */ wp_kses( __( 'Example 2', 'boldgrid-backup' ), $tags ),
	/* 12 */ wp_kses( __( 'Example 3', 'boldgrid-backup' ), $tags ),
	/* 13 */ wp_kses( __( 'Click the Preview button to get a list of which files will be included in your backup based upon your Include and Exclude settings.', 'boldgrid-backup' ), $tags ),
	/* 14 */ wp_kses( __( 'Backup only the wp-content folder.', 'boldgrid-backup' ), $tags ),
	/* 15 */ wp_kses( __( 'Backup only the plugins and themes folders.', 'boldgrid-backup' ), $tags ),
	/* 16 */ wp_kses( __( 'Backup everything except WordPress core files.', 'boldgrid-backup' ), $tags ),
	/* 17 */ wp_kses( __( 'Include', 'boldgrid-backup' ), $tags ),
	/* 18 */ wp_kses( __( 'Preview', 'boldgrid-backup' ), $tags ),
	/* 19 */ wp_kses( __( 'Exclude', 'boldgrid-backup' ), $tags ),
	/* 20 */ wp_kses( __( 'Click the <strong>Preview</strong> button to get a list of which files will be included in your backup based upon your Include and Exclude settings. Click the <strong>Use default</strong> button to restore default values.', 'boldgrid-backup' ), $tags ),
	/* 21 */ wp_kses( __( 'Use an asterisk(*) as a wildcard.', 'boldgrid-inspirations' ), $tags ),
	/* 22 */ wp_kses( __( 'For example, <strong>wp-*</strong> will match <strong>wp-</strong>content, <strong>wp-</strong>admin, and <strong>wp-</strong>includes.', 'boldgrid-inspirations' ), $tags ),
	/* 23 */ wp_kses( __( 'Use WPCORE to specify WordPress core files.', 'boldgrid-bacukp' ), $tags ),
	/* 24 */ wp_kses( __( 'This includes the wp-admin and wp-includes folders, as well as all of the core WordPress files in the root directory of WordPress.', 'boldgrid-backup' ), $tags ),
	/* 25 */ wp_kses( __( 'Begin a filter with a forward slash(/) to specify the file or folder must be in the root WordPress directory.', 'boldgrid-backup' ), $tags ),
	/* 26 */ wp_kses( __( 'For example, <strong>/index.php</strong> will not match index.php files in subdirectories, such as wp-includes/index.php', 'boldgrid-backup' ), $tags ),
	/* 27 */ wp_kses( __( 'Use a comma to add more than one filter.', 'boldgrid-backup' ), $tags ),
	/* 28 */ wp_kses( __( 'For example, <strong>wp-admin,wp-includes</strong> will backup both the wp-admin folder and the wp-includes folder.', 'boldgrid-backup' ), $tags ),
	/* 29 */ wp_kses( __( 'Default', 'boldgrid-backup' ), $tags ),
	/* 30 */ wp_kses( __( 'These are the <strong>default</strong> settings. Include all core WordPress files and the wp-content folder (which includes your plugins, themes, and uploads).', 'boldgrid-backup' ), $tags ),
	/* 31 */ $core->folder_exclusion->default_include,
	/* 32 */ $core->folder_exclusion->default_exclude,
	/* 33 */ wp_kses( __( 'The following special characters can be used in your <strong>include</strong> and <strong>exclude</strong> filters:', 'boldgrid-backup' ), $tags )
);

return $markup;

?>
