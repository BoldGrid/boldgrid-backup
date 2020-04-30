<?php
/**
 * File: boldgrid-backup-admin-transfers.php
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$is_premium           = $this->core->config->get_is_premium();
$is_premium_installed = $this->core->config->is_premium_installed;
$is_premium_active    = $this->core->config->is_premium_active;

$nav         = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
$overview    = include BOLDGRID_BACKUP_PATH . '/admin/partials/transfers/overview.php';
$source      = include BOLDGRID_BACKUP_PATH . '/admin/partials/transfers/source.php';
$destination = include BOLDGRID_BACKUP_PATH . '/admin/partials/transfers/destination.php';

$this->core->archive_actions->enqueue_scripts();
$this->core->auto_rollback->enqueue_home_scripts();

$sections = [
	'sections' => [
		[
			'id'      => 'section_transfers',
			'title'   => __( 'Overview', 'boldgrid-backup' ),
			'content' => $overview,
		],
		[
			'id'      => 'section_source',
			'title'   => __( 'Source', 'boldgrid-backup' ),
			'content' => $source,
		],
		[
			'id'      => 'section_destination',
			'title'   => __( 'Destination', 'boldgrid-backup' ),
			'content' => $destination,
		],
	],
];

/**
 * Allow other plugins to modify the sections of the transfers page.
 *
 * @since 1.6.0
 *
 * @param array $sections Sections.
 */
$sections = apply_filters( 'boldgrid_backup_transfers_sections', $sections );

/**
 * Render the $sections into displayable markup.
 *
 * @since 1.6.0
 *
 * @param array $sections Sections.
 *
 * phpcs:disable WordPress.NamingConventions.ValidHookName
 */
$col_container = apply_filters( 'Boldgrid\Library\Ui\render_col_container', $sections );
?>

<div class="bgbkup-transfers-page">
	<?php
		echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';
		echo $col_container; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	?>
</div>
