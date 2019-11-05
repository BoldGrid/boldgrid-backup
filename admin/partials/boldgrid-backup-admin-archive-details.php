<?php
/**
 * File: boldgrid-backup-admin-archive-details.php
 *
 * This file contains renders the details page of a backup archive.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * @param bool  $archive_found Whether or not the archive was found.
 * @param array $archive       An array of details about the archive, similar to
 *                             the $info created during archiving.
 */

defined( 'WPINC' ) || die;

wp_enqueue_style( 'editor-buttons' );

wp_nonce_field( 'boldgrid_backup_remote_storage_upload' );

$separator = '<hr class="separator">';

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);

$is_premium        = $this->core->config->get_is_premium();
$is_premium_active = $this->core->config->is_premium_active;

$details        = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/details.php';
$remote_storage = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/remote-storage.php';
$browser        = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser.php';
$db             = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/db.php';

// Special situations where the backup file is not local and/or remote.
$only_remote = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/only-remote.php';
$not_found   = include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/not-found.php';

$delete_link     = $this->core->archive_actions->get_delete_link( $archive['filename'] );
$download_button = $this->core->archive_actions->get_download_button( $archive['filename'] );
$restore_button  = $this->core->archive_actions->get_restore_button( $archive['filename'] );
$download_link   = $this->core->archive_actions->get_download_link_button( $archive['filename'] );

if ( ! $archive_found ) {
	$file_size     = '';
	$backup_date   = '';
	$more_info     = '';
	$major_actions = '';
	$protect       = '';
} else {
	$file_size = sprintf(
		'<div class="misc-pub-section">%1$s: <strong>%2$s</strong></div>',
		__( 'File size', 'boldgrid-backup' ),
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] )
	);

	// dirlist -> lastmodunix -> mtime (last_modified in unix time).
	$this->core->time->init( $archive['lastmodunix'], 'utc' );
	$backup_date = sprintf(
		'<div class="misc-pub-section">%1$s: <strong>%2$s</strong></div>',
		__( 'Backup date', 'boldgrid-backup' ),
		$this->core->time->get_span()
	);

	$more_info = empty( $details ) ? '' : sprintf(
		'
		<div class="misc-pub-section">
			More info <a href="" data-toggle-target="#more_info">Show</a>
			<div id="more_info" class="hidden">
				<hr />
				%1$s
			</div>
		</div>',
		$details
	);

	$major_actions = sprintf(
		'
		<div id="major-publishing-actions">
			<div id="delete-action-link">
				%1$s
			</div>
			<div id="publishing-action">
				<span class="spinner"></span>
				<button class="button button-primary button-large">%2$s</button>
			</div>
			<div style="clear:both;"></div>
		</div>',
		$delete_link,
		esc_html__( 'Update', 'boldgrid-backup' )
	);

	$is_protected = $this->core->archive->get_attribute( 'protect' );
	$is_protected = ! empty( $is_protected );
	$protect      = '
	<div class="misc-pub-section bglib-misc-pub-section dashicons-lock">
		' . esc_html__( 'Protect backup', 'boldgrid-backup' ) . ': <span class="value-displayed"></span>
		<a class="edit" href="" style="display: inline;">
			<span aria-hidden="true">' . esc_html__( 'Edit', 'boldgrid-backup' ) . '</span>
		</a>
		<div class="options" style="display: none;">
			<p>
				<em>
					' .
	wp_kses(
		sprintf(
			// translators: 1: HTML anchor open tags, 2: HTML close tag, 3: HTML anchor open tags, 4: HTML close tag.
			__( 'Protect this backup from being deleted due to %1$sretention settings%2$s. Applies only to backups stored on your %3$sWeb Server%4$s.', 'boldgrid-backup' ),
			'<a href="' . get_admin_url( null, 'admin.php?page=boldgrid-backup-settings&section=section_storage' ) . '">',
			'</a>',
			'<a href="' . get_admin_url( null, 'admin.php?page=boldgrid-backup-tools&section=section_locations' ) . '">',
			'</a>'
		), $allowed_html
	) . '
				</em>
			</p>
			<select name="backup_protect">
				<option value="0" ' . selected( $is_protected, false, false ) . '>' . esc_html__( 'No', 'boldgrid-backup' ) . '</option>
				<option value="1" ' . selected( $is_protected, true, false ) . '>' . esc_html( 'Yes', 'boldgrid-backup' ) . '</option>
			</select>
			<p>
				<a href="" class="button">' . esc_html__( 'OK', 'boldgrid-backup' ) . '</a>
				<a href="" class="button-cancel">' . esc_html__( 'Cancel', 'boldgrid-backup' ) . '</a>
			</p>
		</div>
	</div>
	';
}

$main_meta_box = sprintf(
	'
	<div id="submitdiv" class="postbox">
		<h2 class="hndle ui-sortable-handle"><span>%1$s</span></h2>
		<div class="inside submitbox">
			%8$s
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
	/* 7 */ $major_actions,
	/* 8 */ $protect
);

$premium_url     = $this->core->go_pro->get_premium_url( 'bgbkup-archive-storage' );
$remote_meta_box = sprintf(
	'
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
	/* 3 */ $this->core->config->is_premium_done ? '' : sprintf(
		'
		<div class="inside premium wp-clearfix">
			%1$s
			%2$s
		</div>',
		/* 1 */ $this->core->go_pro->get_premium_button( $premium_url ),
		/* 2 */ __( 'Upgrade to <strong>BoldGrid Backup Premium</strong> for more Storage Locations!', 'boldgrid-backup' )
	),
	/* 4 */ __( 'Secure your backups by keeping copies of them on <a href="admin.php?page=boldgrid-backup-tools&section=section_locations">remote storage</a>.', 'boldgrid-backup' )
);

$editor_tools = sprintf(
	'
	<div style="padding-top:0px;" id="wp-content-editor-tools" class="wp-editor-tools hide-if-no-js">
		<div id="wp-content-media-buttons" class="wp-media-buttons">
			%1$s
		</div>
		<div class="wp-editor-tabs">
			<button type="button" id="content-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="content">%2$s</button>
			<button type="button" id="content-html" class="wp-switch-editor switch-html" data-wp-editor-id="content">%3$s%4$s</button>
		</div>
	</div>
	',
	/* 1 */ $db['buttons'],
	/* 2 */ __( 'Files & Folders', 'boldgrid-backup' ),
	/* 3 */ __( 'Database', 'boldgrid-backup' ),
	/* 4 */ empty( $archive['encrypt_db'] ) ? '' : '<span class="dashicons dashicons-admin-network" title="' .
		__( 'Encrypted database', 'boldgrid-backup' ) . '"></span>'
);

$premium_url = $this->core->go_pro->get_premium_url( 'bgbkup-archive-browser' );
$intro       = $this->core->config->is_premium_done ? '' : sprintf(
	'
	<div class="bg-box-bottom premium" style="margin-bottom:15px;">
		<strong>%1$s</strong>
		<p>
			%2$s
			%3$s
		</p>
	</div>',
	/* 1 */ __( 'Restore Individual Files With One Click', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button( $premium_url, esc_html__( 'Unlock Feature', 'boldgrid-backup' ) ),
	/* 3 */ __( 'Changed a file and now your site isn’t working properly? With BoldGrid Backup Premium, you can browse through past backup archives and restore individual files with a single click.', 'boldgrid-backup' )
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
				' . __( 'Restore this backup. This will restore all the files and the database in this backup. Use the <strong>Backup Browser</strong> below to look at the backup archive and see what will be restored.', 'boldgrid-backup' ) . '<br /><br />
				<strong>' . __( 'Download Link', 'boldgrid-backup' ) . '</strong><br />
				' . __( 'A public link that is used to download a backup archive file.  You can use it to migrate your website to another WordPress installation.  Please keep download links private, as the download files contains sensitive data.', 'boldgrid-backup' ) . '
			</p>
			<p>
				' . $download_button . ' ' . $restore_button . ' ' . $download_link . '
			</p>
			<div id="download-link-copy" class="notice notice-info inline"></div>
			<hr class="separator" />
			<h2 style="font-size:initial; padding:0px;">' . __( 'Backup Browser', 'boldgrid-backup' ) . '</h2>
			<p>
				' . __( 'Use the File & Folders and Database tools below to browse the contents of this backup file.', 'boldgrid-backup' ) . '
			</p>
			' . $intro . $editor_tools . $browser . $db['browser'] . '
		</div>
	</div>
';


/*
 * Allow the user to enter a title and description for this backup.
 *
 * Prepend this to the main content area.
 */
if ( $archive_found ) {
	$main_content = '
	<div id="titlediv">
		<div id="titlewrap">
			<input type="text" name="backup_title" size="30" value="' . esc_attr( $title ) . '" id="title" spellcheck="true" autocomplete="off" placeholder="' . esc_attr__( 'Unnamed Backup', 'boldgrid-backup' ) . '">
		</div>
	</div>
	<textarea name="backup_description" placeholder="' . esc_attr__( 'Backup description.', 'boldgrid-backup' ) . '">' . esc_html( $description ) . '</textarea>
	<hr class="separator">
	' . $main_content;
}

if ( ! $this->core->archive->is_stored_locally() ) {

	if ( $this->core->archive->is_stored_remotely() ) {
		$main_content = $only_remote;
	} else {
		$main_content    = $not_found;
		$remote_meta_box = '';
	}
}

$page = sprintf(
	'
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
	/* 3 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php',
	/* 4 */ $main_content,
	/* 5 */ $main_meta_box,
	/* 6 */ $remote_meta_box
);

echo $page; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
