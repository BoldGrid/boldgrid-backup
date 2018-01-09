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
	',
	$this->core->lang['icon_warning'],
	__( 'This backup file is not on the server, but it is on one of your remote storage providers. Before you can restore or review this backup file, please download it to your server using one of the download buttons below.', 'boldgrid-backup' )
);

?>