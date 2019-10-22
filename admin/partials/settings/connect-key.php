<?php
/**
 * File: connect-key.php
 *
 * Show link to Settings >> BoldGrid Connect.
 *
 * @link       https://www.boldgrid.com
 * @since      1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

return '
<div class="bg-box">
	<div class="bg-box-top">
		' . __( 'BoldGrid Connect Key', 'boldgrid-backup' ) . '
	</div>
	<div class="bg-box-bottom">
' .
	sprintf(
		'%1$s <a href="' . admin_url( 'options-general.php?page=boldgrid-connect.php' ) . '">%2$s</a>.',
		__( 'Connect Key management has been moved to', 'boldgrid-backup' ),
		__( 'Settings >> BoldGrid Connect', 'boldgrid-backup' )
	) . '
	</div>
</div>
';
