<?php
/**
 * File: overview.php
 *
 * Show "Overview" on transfers page.
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/transfers
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

return sprintf(
	'<div class="bgbkup-transfers-overview">
	<h2>%1$s</h2>
	<p>%2$s</p>
	<p>%3$s</p>
	<p>%4$s</p>
</div>',
	esc_html__( 'Easily transfer websites!', 'boldgrid-backup' ),
	esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'provides an easy way to transfer a website from one installation to another.', 'boldgrid-backup' ) ),
	esc_html__( 'Use the section selection on the left to choose if this WordPress installation is either the source or destination.', 'boldgrid-backup' ),
	esc_html__( 'You can also try using our new "Direct Transfer" feature to transfer the files from a website without having to create a backup.', 'boldgrid-backup' )
);
