<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

/*
 * Variables passed by scope.
 *
 * @param int $archives_count The archive file count.
 * @param int $archives_size The total size of all archive files.
 * @param string $archive_nonce Nonce used for archive operations.
 * @param array $archives {
 * 	A numbered array of arrays containing the following indexes.
 * 	@type string $filepath Archive file path.
 * 	@type string $filename Archive filename.
 * 	@type string $filedate Localized file modification date.
 * 	@type int $filesize The archive file size in bytes.
 * 	@type int $lastmodunix The archive file modification time in unix seconds.
 * }
 */

?>
<div class='wrap'>
<h1>BoldGrid Backup</h1>
<hr />
<h2>Backup Archive Summary</h2>
<table id='backup-archive-summary-table'>
	<tbody id='backup-archive-list-header'>
		<tr>
			<td class='backup-archive-summary-metric'>Archive Count:</td>
			<td class='backup-archive-summary-value' id='archives-count'><?php echo $archives_count; ?></td>
		</tr>
		<tr>
			<td class='backup-archive-summary-metric'>Total Size:</td>
			<td class='backup-archive-summary-value' id='archives-size'><?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archives_size );?></td>
		</tr>
	</tbody>
</table>
<h2>Backup Archives</h2>
<table id='backup-archive-list-table'>
	<thead id='backup-archive-list-header'>
		<tr>
			<th class='backup-archive-list-path'>Filename</th>
			<th class='backup-archive-list-size'>Size</th>
			<th class='backup-archive-list-date'>Date</th>
			<th class='backup-archive-list-download'></th>
			<th class='backup-archive-list-restore'></th>
		</tr>
		<tr>
			<th colspan="6"><hr /></th>
		</tr>
	</thead>
	<tbody id='backup-archive-list-body'>
<?php

// Print the list of archive files.
if ( false === empty( $archives ) ) {
	foreach ( $archives as $key => $archive ) {
		include dirname( __FILE__ ) . '/boldgrid-backup-admin-archives.php';
	}
} else {
?>
	<tr>
		<td colspan='3'>There are no archives for this site in the backup directory.</td>
	</tr>
<?php
}
?>
	</tbody>
</table>
<div id='backup-site-now-section'>
	<form action='#' id='backup-site-now-form' method='POST'>
		<?php wp_nonce_field( 'boldgrid_backup_now', 'backup_auth' ); ?>
		<p>
			<a id='backup-site-now' class='button button-primary'<?php

// If a restoration was just performed, then disable the backup button.
	if ( false === empty( $_POST['restore_now'] ) ) {
		?> disabled='disabled' style='pointer-events: none;'<?php
	}

?>><?php esc_html_e( 'Backup Site Now', 'boldgrid-backup' ); ?></a>
			<span class='spinner'></span>
		</p>
	</form>
</div>
<div id='backup-site-now-results'></div>
</div>
