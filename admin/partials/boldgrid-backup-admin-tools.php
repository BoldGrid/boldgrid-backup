<?php
/**
 * Tools.
 *
 * @link http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

defined( 'WPINC' ) ? : die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$sections = array(
	'sections' => array(
		array(
			'id'      => 'section_locations',
			'title'   => __( 'Local & Remote', 'boldgrid-backup' ),
			'content' => include BOLDGRID_BACKUP_PATH . '/admin/partials/tools/local-remote.php',
		),
	),
);

/**
 * Allow other plugins to modify the sections of the tools page.
 *
 * @since 1.5.4
 *
 * @param array $sections
 */
$sections = apply_filters( 'boldgrid_backup_tools_sections', $sections );

/**
 * Render the $sections into displayable markup.
 *
 * @since 1.5.4
 *
 * @param array $sections
 */
$col_container = apply_filters( 'Boldgrid\Library\Ui\render_col_container', $sections );

?>

<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup and Restore Settings', 'boldgrid-backup' ); ?></h1>

	<?php
	echo $nav;
	echo $col_container;
	?>
</div>
