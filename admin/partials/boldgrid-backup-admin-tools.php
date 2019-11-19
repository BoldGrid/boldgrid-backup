<?php
/**
 * File: boldgrid-backup-admin-tools.php
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$sections = array(
	'sections' => array(
		array(
			'id'      => 'section_locations',
			'title'   => __( 'Local & Remote', 'boldgrid-backup' ),
			'content' => include BOLDGRID_BACKUP_PATH . '/admin/partials/tools/local-remote.php',
		),
		array(
			'id'      => 'section_cron_log',
			'title'   => __( 'Cron Log', 'boldgrid-backup' ),
			'content' => $this->core->cron_log->get_markup(),
		),
	),
);

/**
 * Allow other plugins to modify the sections of the tools page.
 *
 * @since 1.6.0
 *
 * @param array $sections
 */
$sections = apply_filters( 'boldgrid_backup_tools_sections', $sections );

/**
 * Render the $sections into displayable markup.
 *
 * @since 1.6.0
 *
 * @param array $sections
 *
 * phpcs:disable WordPress.NamingConventions.ValidHookName
 */
$col_container = apply_filters( 'Boldgrid\Library\Ui\render_col_container', $sections );

?>

<div class='wrap'>
	<h1><?php echo esc_html( BOLDGRID_BACKUP_TITLE . ' ' . __( 'Tools', 'boldgrid-backup' ) ); ?></h1>

	<?php
	echo $nav . $col_container; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	?>
</div>
