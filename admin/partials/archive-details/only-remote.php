<?php
/**
 * Display for instances in which backup is not local, but exists remotely.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 */

defined( 'WPINC' ) ? : die;

return sprintf( '
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
	sprintf(
		__( 'After your backup has been downloaded to the web server, this page will refresh and you will see more options available. To learn more about your web server vs. remote storage providers, <a href="%1$s">click here</a>.', 'boldgrid-backup' ),
		'admin.php?page=boldgrid-backup-tools&section=section_locations'
	)
);


