<?php
/**
 * This file contains renders the details page of a backup archive.
 *
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 *
 * @param bool  $archive_found Whether or not the archive was found.
 * @param array $archive       An array of details about the archive, similar to
 *                             the $info created during archiving.
 */

defined( 'WPINC' ) ? : die;

wp_enqueue_style( 'editor-buttons' );

wp_nonce_field( 'boldgrid_backup_remote_storage_upload' );

$separator = '<hr class="separator">';

$details = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/details.php';
$remote_storage = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/remote-storage.php';
$browser = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser.php';
$db = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/db.php';
$only_remote = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/only-remote.php';

$delete_link = $this->core->archive_actions->get_delete_link( $archive['filename'] );
$download_button = $this->core->archive_actions->get_download_button( $archive['filename'] );
$restore_button = $this->core->archive_actions->get_restore_button( $archive['filename'] );

if( ! $archive_found ) {
	$file_size = '';
	$backup_date = '';
	$more_info = '';
	$major_actions = '';
} else {
	$file_size = sprintf(
		'<div class="misc-pub-section">%1$s: <strong>%2$s</strong></div>',
		__( 'File size', 'boldgrid-backup' ),
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] )
	);

	$backup_date = sprintf(
		'<div class="misc-pub-section">%1$s: <strong>%2$s</strong></div>',
		__( 'Backup date', 'boldgrid-backup' ),
		$archive['filedate']
	);

	$more_info = empty( $details ) ? '' : sprintf( '
		<div class="misc-pub-section">
			More info <a href="" data-toggle-target="#more_info">Show</a>
			<div id="more_info" class="hidden">
				<hr />
				%1$s
			</div>
		</div>',
		$details
	);

	$major_actions = sprintf( '
		<div id="major-publishing-actions">
			<div id="delete-action">
				%1$s
			</div>

			<div style="clear:both;"></div>
		</div>',
		$delete_link
	);
}

$main_meta_box = sprintf( '
	<div id="submitdiv" class="postbox">
		<h2 class="hndle ui-sortable-handle"><span>%1$s</span></h2>
		<div class="inside submitbox">

			<div class="misc-pub-section">%2$s: <strong>%3$s</strong></div>

			%4$s

			%5$s

			%6$s

			%7$s

		</div>
	</div>',
	/* 1 */ __( 'Backup Archive', 'boldgrid-backup' ),
	/* 2 */ __( 'File name', 'boldgrid-backup' ),
	/* 3 */ $archive['filename'],
	/* 4 */ $file_size,
	/* 5 */ $backup_date,
	/* 6 */ $more_info,
	/* 7 */ $major_actions
);

$remote_meta_box = sprintf( '
	<div class="postbox remote-storage">
		<h2 class="hndle ui-sortable-handle">
			<span>%1$s</span>
			<span class="dashicons dashicons-editor-help" data-id="remote-storage-help"></span>
		</h2>

		<div class="inside">

			<p class="help" data-id="remote-storage-help">%4$s</p>

			%2$s
		</div>

		%3$s
	</div>',
	/* 1 */ __( 'Remote Storage', 'boldgrid-backup' ),
	/* 2 */ $remote_storage['postbox'],
	/* 3 */ $this->core->config->is_premium_done ? '' : sprintf( '
		<div class="inside premium wp-clearfix">
			%1$s
			%2$s
		</div>',
		/* 1 */ $this->core->go_pro->get_premium_button(),
		/* 2 */ __( 'Upgrade to <strong>BoldGrid Backup Premium</strong> for more Storage Locations!', 'boldgrid-backup' )
	),
	/* 4 */ __( 'Secure your backups by keeping copies of them on <a href="admin.php?page=boldgrid-backup-tools&section=section_locations">remote storage</a>.', 'boldgrid-backup' )
);

$editor_tools = sprintf( '
	<div style="padding-top:0px;" id="wp-content-editor-tools" class="wp-editor-tools hide-if-no-js">
		<div id="wp-content-media-buttons" class="wp-media-buttons">
			%1$s
		</div>
		<div class="wp-editor-tabs">
			<button type="button" id="content-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="content">%2$s</button>
			<button type="button" id="content-html" class="wp-switch-editor switch-html" data-wp-editor-id="content">%3$s</button>
		</div>
	</div>
	',
	/* 1 */ $db['buttons'],
	/* 2 */ __( 'Files & Folders', 'boldgrid-backup' ),
	/* 3 */ __( 'Database', 'boldgrid-backup')
);

$intro = $this->core->config->is_premium_done ? '' : sprintf( '
	<div class="bg-box-bottom premium" style="margin-bottom:15px;">
		<strong>%1$s</strong>

		<p>
			%2$s
			%3$s
		</p>
	</div>',
	/* 1 */ __( 'One click file restorations', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button(),
	/* 3 */ __( 'Please note that most functionality for the Archive Browser, such as one click file restorations, is contained within the Premium version. For help with restoring a single file without this one click feature, please <a href="https://www.boldgrid.com/support" target="_blank">click here</a>.', 'boldgrid-backup' )
);

$main_content = '
	<div id="postdivrich" class="postarea wp-editor-expand">

		<div id="wp-content-wrap" class="wp-core-ui wp-editor-wrap tmce-active has-dfw">

			<h2 style="font-size:initial; padding:0px;">
				' . __( 'Download & Restore', 'boldgrid-backup' ) . '
				<span class="dashicons dashicons-editor-help" data-id="download_and_restore"></span>
			</h2>

			<p class="help" data-id="download_and_restore">
				<strong>' . __( 'Download to Local Machine', 'boldgrid-backup' ) . '</strong><br />
				' . __( 'Backup archives generally should be stored in more locations than just your <strong>web server</strong>. Be sure to keep copies on your <strong>local machine</strong> and / or a <strong>remote storage</strong> provider. Learn more about these different locations <a href="admin.php?page=boldgrid-backup-tools&section=section_locations">here</a>.', 'boldgrid-backup' ) . '<br /><br />
				<strong>' . __( 'Restore', 'boldgrid-backup' ) . '</strong><br />
				' . __( 'Restore this backup. This will restore all the files and the database in this backup. Use the <strong>Backup Browser</strong> below to look at the backup archive and see what will be restored.', 'boldgrid-backup' ) . '
			</p>

			<p>
				' . $download_button . ' ' . $restore_button . '
			</p>

			<hr class="separator" />

			<h2 style="font-size:initial; padding:0px;">' . __( 'Backup Browser', 'boldgrid-backup' ) . '</h2>

			<p>
				' . __( 'Use the File & Folders and Database tools below to browse the contents of this backup file.', 'boldgrid-backup' ) . '
			</p>

			' . $intro . $editor_tools . $browser . $db['browser'] . '
		</div>
	</div>
';

if( ! $archive_found && count( $this->remote_storage_li ) > 0 ) {
	$main_content = $only_remote;
}

$page = sprintf( '
	<input type="hidden" id="filename" value="%1$s" />
	<div class="wrap">
		<h1 class="wp-heading-inline">%2$s</h1>
		%3$s
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content" style="position: relative">
					%4$s
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortable" style="">

						%5$s

						%6$s
					</div>
				</div>
			</div>
		</div>
	</div>
	',
	/* 1 */ $archive['filename'],
	/* 2 */ __( 'Backup Archive Details', 'boldgrid-backup' ),
	/* 3 */ include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php',
	/* 4 */ $main_content,
	/* 5 */ $main_meta_box,
	/* 6 */ $remote_meta_box
);

echo $page;

?>