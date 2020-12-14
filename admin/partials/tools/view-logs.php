<?php
/**
 * File: logs.php
 *
 * Show "Auto Updates" on settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/tools
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

defined( 'WPINC' ) || die;

// Get a listing of all our logs and order them asc.
$logs_dir = $this->core->backup_dir->get_logs_dir();
$list     = $this->core->wp_filesystem->dirlist( $logs_dir );
uasort( $list, function( $a, $b ) {
	return $a['lastmodunix'] > $b['lastmodunix'] ? 1 : -1;
});

ob_start();

echo '<h2>' . esc_html__( 'Logs', 'boldgrid-backup' ) . '</h2>';

if ( empty( $list ) ) {
	echo '<div><em>' . esc_html__( 'No log files exist.', 'boldgrid-backup' ) . '</em></div>';
} else {
	echo '
		<table class="wp-list-table widefat fixed striped pages">
			<thead>
				<tr>
					<th>' . esc_html__( 'Filename', 'boldgrid-backup' ) . '</th>
					<th>' . esc_html__( 'Size', 'boldgrid-backup' ) . '</th>
					<th>' . esc_html__( 'Timestamp', 'boldgrid-backup' ) . '</th>
				</tr>
			</thead>
			<tbody>';

	foreach ( $list as $item ) {
		$this->core->time->init( $item['lastmodunix'] );

		echo '
			<tr>
				<td>
					<a
						title="' . esc_attr( $item['name'] ) . '"
						class="thickbox"
						data-filename="' . esc_attr( $item['name'] ) . '"
						href="#TB_inline&inlineId=bgbkup_show_log">' .
						esc_html( $item['name'] ) .
					'</a>
				</td>
				<td>' . esc_html( size_format( $item['size'] ) ) . '</td>
				<td>' .
					wp_kses(
						$this->core->time->get_span(),
						[
							'span' => [
								'title' => [],
							],
						]
					) . '</td>
			</tr>';
	}

	echo '
			</tbody>
		</table>';
}

wp_nonce_field( 'boldgrid_backup_view_log', 'bgbup_log_nonce' );

echo '<div id="bgbkup_show_log" style="display:none;"></div>';

$output = ob_get_contents();
ob_end_clean();

return $output;
