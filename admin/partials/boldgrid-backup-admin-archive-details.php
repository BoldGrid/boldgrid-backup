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

/*
 * On the archive details page, the user can click "upload" to upload this backup to any number of available
 * remote storage providers. This nonce is used for those uploads.
 */
wp_nonce_field( 'boldgrid_backup_remote_storage_upload', 'bgbkup_remote_upload_nonce' );

/*
 * This nonce is used for several of the actions on the Archive details page, specific to the browser.
 * For example, it is used to on the "browser archive" and "browse database" features.
 *
 * @see Boldgrid_Backup_Admin_Archive_Browser::authorize to see it being used in authorization.
 */
wp_nonce_field( 'bgbkup_archive_details_page', 'bgbkup_archive_details_nonce' );

$separator = '<hr class="separator">';

$allowed_html = array(
	'a'      => array(
		'href' => array(),
	),
	'strong' => array(),
);

$is_premium           = $this->core->config->get_is_premium();
$is_premium_installed = $this->core->config->is_premium_installed;
$is_premium_active    = $this->core->config->is_premium_active;
$is_premium_all       = $is_premium && $is_premium_active;

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

// Enqueue the scripts needed for the Backup Site Now and Upload Backups to work.
$this->core->folder_exclusion->enqueue_scripts();
$this->core->db_omit->enqueue_scripts();
$this->core->auto_rollback->enqueue_home_scripts();
$this->core->auto_rollback->enqueue_backup_scripts();

if ( ! $archive_found ) {
	$file_size     = '';
	$backup_date   = '';
	$more_info     = '';
	$major_actions = '';
	$protect       = '';
	$encrypt_db    = '';
} else {
	$file_size = sprintf(
		'<div class="misc-pub-section">%1$s: <strong>%2$s</strong></div>',
		esc_html__( 'File size', 'boldgrid-backup' ),
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] )
	);

	// dirlist -> lastmodunix -> mtime (last_modified in unix time).
	$this->core->time->init( $archive['lastmodunix'], 'utc' );
	$backup_date = sprintf(
		'<div class="misc-pub-section">%1$s: <strong>%2$s</strong></div>',
		esc_html__( 'Backup date', 'boldgrid-backup' ),
		$this->core->time->get_span()
	);

	$more_info = empty( $details ) ? '' : sprintf(
		'
		<div class="misc-pub-section">
			More info <a href="" data-bgbkup-toggle-target="#more_info">Show</a>
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

	$is_protected = (bool) $this->core->archive->get_attribute( 'protect' );
	$protect      = '
	<div class="misc-pub-section bglib-misc-pub-section dashicons-lock">
		' . esc_html__( 'Ignore retention', 'boldgrid-backup' ) . ': <span class="value-displayed"></span>
		<a class="edit" href="#" style="display: inline;">
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
			'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_storage' ) ) . '">',
			'</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-tools&section=section_locations' ) ) . '">',
			'</a>'
		), $allowed_html
	) . '
				</em>
			</p>
			<select name="backup_protect" id="backup-protect">
				<option value="0" ' . selected( $is_protected, false, false ) . '>' . esc_html__( 'No', 'boldgrid-backup' ) . '</option>
				<option value="1" ' . selected( $is_protected, true, false ) . '>' . esc_html__( 'Yes', 'boldgrid-backup' ) . '</option>
			</select>
			<p>
				<a href="" class="button">' . esc_html__( 'OK', 'boldgrid-backup' ) . '</a>
				<a href="" class="button-cancel">' . esc_html__( 'Cancel', 'boldgrid-backup' ) . '</a>
			</p>
		</div>
	</div>
	';

	// Determine premium message.
	if ( ! $is_premium_all ) {
		switch ( true ) {
			case ! $is_premium:
				$premium_url     = $this->core->go_pro->get_premium_url( 'bgbkup-archive-encrypt' );
				$premium_message = sprintf(
					'<div class="premium">%1$s</div>
					<div><p>%2$s</p></div>',
					esc_html__(
						'Upgrade to Premium to protect your sensitive data!',
						'boldgrid-backup'
					),
					'<form action="' . $premium_url . '" target="_blank"><button class="button button-success" type="submit">' .
						esc_html__( 'Get Premium', 'boldgrid-backup' ) . '</button></form>'
				);
				break;
			case ! $is_premium_installed:
				$premium_url     = $this->core->go_pro->get_premium_url(
					'bgbkup-archive-encrypt',
					'https://www.boldgrid.com/central/plugins'
				);
				$premium_message = sprintf(
					'<div class="premium">%1$s</div>
					<div><p>%2$s</p></div>',
					esc_html__(
						'Secure your sensitive data with the Premium plugin!',
						'boldgrid-backup'
					),
					'<form action="' . $premium_url . '" target="_blank"><button class="button button-success" type="submit">' .
						esc_html__( 'Unlock Feature', 'boldgrid-backup' ) . '</button></form>'
				);
				break;
			case ! $is_premium_active:
				$premium_url     = $this->core->go_pro->get_premium_url( 'bgbkup-archive-encrypt' );
				$premium_message = '<div class="premium">' . sprintf(
					// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag, 3: Premium plugin title.
					__( '%3$s is not active.  Please go to the %1$sPlugins%2$s page to activate it.', 'boldgrid-backup' ),
					'<a href="' .
						esc_url( admin_url( 'plugins.php?s=Boldgrid%20Backup%20Premium&plugin_status=inactive' ) ) .
						'">',
					'</a>',
					BOLDGRID_BACKUP_TITLE . ' Premium'
				) . '</div>';
				break;
			default:
				$premium_message = '';
				break;
		}
	} else {
		$premium_message = '';
	}

	$is_db_encrypted = (bool) $this->core->archive->get_attribute( 'encrypt_db' );
	$encrypt_db      = '
	<div class="misc-pub-section bglib-misc-pub-section bgbkup-db-lock">
		' . esc_html__( 'Encrypt database', 'boldgrid-backup' ) . ': <span class="value-displayed"></span>
		<a class="edit" href="#" style="display: inline;">
			<span aria-hidden="true">' . esc_html__( 'Edit', 'boldgrid-backup' ) . '</span>
		</a>
		<div class="options" style="display: none;">
			<p>
				<em>
					' .
	wp_kses(
		sprintf(
			// translators: 1: HTML anchor open tags, 2: HTML close tag, 3: HTML anchor open tags, 4: HTML close tag.
			__( 'Database encryption protects sensitive data stored in backup archives.  The encryption settings are configured on the %1$sBackup Security%2$s settings page.', 'boldgrid-backup' ),
			'<a href="' .
				esc_url( admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_security' ) ) .
				'">',
			'</a>'
		), $allowed_html
	) . '
				</em>
			</p>
			<select name="encrypt_db" id="encrypt-db">
				<option value="0" ' . selected( $is_db_encrypted, false, false ) . ' ' .
		disabled( $is_premium_all, false, false ) . '>' . esc_html__( 'No', 'boldgrid-backup' ) . '</option>
				<option value="1" ' . selected( $is_db_encrypted, true, false ) . ' ' .
		disabled( $is_premium_all, false, false ) . '>' . esc_html__( 'Yes', 'boldgrid-backup' ) . '</option>
			</select>
			<p>' . $premium_message . '</p>
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
			%9$s
			<div class="misc-pub-section">%2$s: <strong>%3$s</strong></div>
			%4$s
			%5$s
			%6$s
			%7$s
		</div>
	</div>',
	/* 1 */ esc_html__( 'Backup Archive', 'boldgrid-backup' ),
	/* 2 */ esc_html__( 'File name', 'boldgrid-backup' ),
	/* 3 */ $archive['filename'],
	/* 4 */ $file_size,
	/* 5 */ $backup_date,
	/* 6 */ $more_info,
	/* 7 */ $major_actions,
	/* 8 */ $encrypt_db,
	/* 9 */ $protect
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
	/* 1 */ esc_html__( 'Remote Storage', 'boldgrid-backup' ),
	/* 2 */ $remote_storage['postbox'],
	/* 3 */ $this->core->config->is_premium_done ? '' : sprintf(
		'
		<div class="inside premium wp-clearfix">
			%1$s
			%2$s
		</div>',
		/* 1 */ $this->core->go_pro->get_premium_button( $premium_url ),
		/* 2 */ sprintf(
			// translators: 1: HTML strong open tag, 2: HTML strong close tag, 3: Plugin title.
			esc_html__( 'Upgrade to %1$s%3$s%2$s for more Storage Locations!', 'boldgrid-backup' ),
			'<strong>',
			'</strong>',
			BOLDGRID_BACKUP_TITLE . ' Premium'
		)
	),
	/* 4 */ wp_kses(
		sprintf(
			// translators: 1 An opening anchor tag linking to the remote storate settings, 2 its closing anchor tag.
			__( 'Secure your backups by keeping copies of them on %1$sremote storage%2$s.', 'boldgrid-backup' ),
			'<a href="admin.php?page=boldgrid-backup-tools&section=section_locations">',
			'</a>'
		),
		array(
			'a' => array(
				'href' => array(),
			),
		)
	)
);

$editor_tools = sprintf(
	'
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
	/* 2 */ esc_html__( 'Files & Folders', 'boldgrid-backup' ),
	/* 3 */ esc_html__( 'Database', 'boldgrid-backup' )
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
	/* 1 */ esc_html__( 'Restore Individual Files With One Click', 'boldgrid-backup' ),
	/* 2 */ $this->core->go_pro->get_premium_button( $premium_url, esc_html__( 'Unlock Feature', 'boldgrid-backup' ) ),
	/* 3 */ sprintf(
		// translators: 1: Plugin title.
		esc_html__(
			'Changed a file and now your site isn’t working properly? With %1$s, you can browse through past backup archives and restore individual files with a single click.',
			'boldgrid-backup'
		),
		BOLDGRID_BACKUP_TITLE . ' Premium'
	)
);

$main_content = '
	<div id="postdivrich" class="postarea wp-editor-expand">
		<div id="wp-content-wrap" class="wp-core-ui wp-editor-wrap tmce-active has-dfw">
			<h2 style="font-size:initial; padding:0px;">
				' . esc_html__( 'Download & Restore', 'boldgrid-backup' ) . '
				<span class="dashicons dashicons-editor-help" data-id="download_and_restore"></span>
			</h2>
			<p class="help" data-id="download_and_restore">
				<strong>' . esc_html__( 'Download to Local Machine', 'boldgrid-backup' ) . '</strong><br />
				' . wp_kses(
					sprintf(
						// translators: 1 the opening anchor tag linking to the locations page, 2 its closing anchor tag, 3 opening strong tag, 4 its closing strong tag.
						__( 'Backup archives generally should be stored in more locations than just your %3$sweb server%4$s. Be sure to keep copies on your %3$slocal machine%4$s and / or a %3$sremote storage%4$s provider. Learn more about these different locations %1$shere%2$s.', 'boldgrid-backup' ),
						'<a href="admin.php?page=boldgrid-backup-tools&section=section_locations">',
						'</a>',
						'<strong>',
						'</strong>'
					),
					$allowed_html
				) . '<br /><br />
				<strong>' . esc_html__( 'Restore', 'boldgrid-backup' ) . '</strong><br />
				' . wp_kses(
					sprintf(
						// translators: 1 an opening strong tag, 2 its closing strong tag.
						__( 'Restore this backup. This will restore all the files and the database in this backup. Use the %1$sBackup Browser%2$s below to look at the backup archive and see what will be restored.', 'boldgrid-backup' ),
						'<strong>',
						'</strong>'
					),
					$allowed_html
				) . '<br /><br />
				<strong>' . esc_html__( 'Download Link', 'boldgrid-backup' ) . '</strong><br />
				' . esc_html__( 'A public link that is used to download a backup archive file.  You can use it to migrate your website to another WordPress installation.  Please keep download links private, as the download files contains sensitive data.', 'boldgrid-backup' ) . '
			</p>
			<p>
				' . $download_button . ' ' . $restore_button . ' ' . $download_link . '
			</p>
			<div id="download-link-copy" class="notice notice-info inline"></div>
			<hr class="separator" />
			<h2 style="font-size:initial; padding:0px;">' . esc_html__( 'Backup Browser', 'boldgrid-backup' ) . '</h2>
			<p>
				' . esc_html__( 'Use the File & Folders and Database tools below to browse the contents of this backup file.', 'boldgrid-backup' ) . '
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
			<input type="text" name="backup_title" size="30" value="' . esc_attr( $title ) .
		'" id="title" spellcheck="true" autocomplete="off" placeholder="' .
		esc_attr__( 'Unnamed Backup', 'boldgrid-backup' ) . '">
		</div>
	</div>
	<textarea name="backup_description" id="backup-description" placeholder="' .
		esc_attr__( 'Backup description.', 'boldgrid-backup' ) . '">' . esc_html( $description ) . '</textarea>
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
$pre_page = sprintf(
	'
	<input type="hidden" id="filename" value="%1$s" />
	%2$s
	',
	/* 1 */ $archive['filename'],
	/* 2 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php'
);

$page = sprintf(
	'
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content" style="position: relative">
				%3$s
			</div>
			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box-sortables ui-sortable" style="">

					%4$s

					%5$s
				</div>
			</div>
		</div>
	</div>
	',
	/* 1 */ $archive['filename'],
	/* 2 */ require BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php',
	/* 3 */ $main_content,
	/* 4 */ $main_meta_box,
	/* 5 */ $remote_meta_box
);
echo $pre_page; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

echo $page; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
