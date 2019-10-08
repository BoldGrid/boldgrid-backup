<?php
/**
 * File: only-remote.php
 *
 * Display for instances in which backup is not local, but exists remotely.
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

return sprintf(
	'
	<p>
		%1$s <span class="dashicons dashicons-editor-help" data-id="help-web-server"></span>
	</p>
	<p class="help" data-id="help-web-server">
		%4$s
	</p>
	%2$s %3$s
	',
	__( 'This backup file is not on your <strong>web server</strong>, but it is saved to one or more of your <strong>remote storage providers</strong>. If you would like to restore this backup or review the contents of this backup, you will first need to download it to your web server.', 'boldgrid-backup' ),
	'<a class="button button-primary" id="download_first">Download to web server</a>',
	$this->core->lang['spinner'],
	wp_kses(
		sprintf(
			// translators: 1 An opening anchor tag to the tools page, 2 its closing tag.
			__(
				'After your backup has been downloaded to the web server, this page will refresh and you will see more options available. To learn more about your web server vs. remote storage providers, %1$sclick here%2$s.',
				'boldgrid-backup'
			),
			'<a href="' . admin_url( 'admin.php?page=boldgrid-backup-tools&section=section_locations' ) . '">',
			'</a>'
		),
		[
			'a' => [
				'href' => [],
			],
		]
	)
);
