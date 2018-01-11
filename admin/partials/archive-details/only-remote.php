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
		%1$s %2$s

	</p>
	%3$s %4$s
	',
	$this->core->lang['icon_warning'],
	__( 'This backup file is not on your web server, but it is saved to one or more of your remote storage providers. Before you can restore this backup file, please download it to your web server. After the backup is downloaded, you can then view more details about the backup and restore it if you\'d like.', 'boldgrid-backup' ),
	'<a class="button button-primary" id="download_first">Download to web server</a>',
	$this->core->lang['spinner']
);

?>