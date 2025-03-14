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
	<div class="bgbkup-transfers-overview-options">
		<div class="bgbkup-transfers-overview-option-1">
			<h3>%3$s</h3>
			<p>%4$s</p>
		</div>
		<div class="bgbkup-transfers-overview-option-2">
			<h3>%5$s <span class="boldgrid-backup-beta-span">%13$s!</span></h3>
			<p>%6$s</p>
		</div>
	</div>
	<h2>%7$s</h2>
	<p>%8$s <strong>%9$s</strong> %10$s</p>
	<p>%8$s <strong>%11$s</strong> %12$s</p>
</div>',
	esc_html__( 'Easily transfer websites!', 'boldgrid-backup' ),
	esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'provides an easy way to transfer a website from one installation to another.', 'boldgrid-backup' ) ),
	esc_html__( 'Option 1: ZIP Transfer', 'boldgrid-backup' ),
	esc_html__( 'This method creates a full ZIP backup of your website, which you then move to the new location and restore all at once. It\'s fast and efficient for small to medium-sized sites, but on large sites or resource-limited servers (like shared hosting), the backup process may fail if it exceeds server limits. If that happens, you\'ll need to start over.', 'boldgrid-backup' ),
	esc_html__( 'Option 2: Direct Transfer', 'boldgrid-backup' ),
	esc_html__( 'Instead of creating a single large backup, this method transfers files in smaller batches. If the process is interrupted due to resource limits or other issues, it can resume where it left off. This makes it ideal for large sites or hosting environments with limited resources, reducing the risk of transfer failures.', 'boldgrid-backup' ),
	esc_html__( 'Which option is best for you?', 'boldgrid-backup' ),
	esc_html__( 'Choose', 'boldgrid-backup' ),
	esc_html__( 'ZIP Transfer', 'boldgrid-backup' ),
	esc_html__( 'if you have a small to medium-sized site and your server has the resources to handle the backup process.', 'boldgrid-backup' ),
	esc_html__( 'Direct Transfer', 'boldgrid-backup' ),
	esc_html__( 'if you have a large site or are on a resource-limited server.', 'boldgrid-backup' ),
	esc_html__( 'Beta', 'boldgrid-backup' )
);
