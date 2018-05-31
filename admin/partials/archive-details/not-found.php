<?php
/**
 * File: not-found.php
 *
 * Display for instances in which backup is not local and not remote.
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

return '<p>' . $this->core->lang['icon_warning'] .
	__( 'Backup file not found!', 'boldgrid-backup' ) . '</p>';
