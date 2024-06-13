<?php
/**
 * Title and Description.
 *
 * @link https://www.boldgrid.com
 * @since 1.7.0
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
		' . esc_html__( 'Title & Description', 'boldgrid-backup' ) . '
	</div>
	<div class="bg-box-bottom">
		<p>' . esc_html__( 'To help remember the details of this backup, you may enter a title and description.', 'boldgrid-backup' ) . '</p>
		<input type="text" name="backup_title" style="width:100%" placeholder="' . esc_html__( 'Backup Title', 'boldgrid-backup' ) . '" /><br /><br />
		<textarea name="backup_description" style="width:100%;height:75px;" placeholder="' . esc_html__( 'Backup Description', 'boldgrid-backup' ) . '" /></textarea>
	</div>
</div>
';
