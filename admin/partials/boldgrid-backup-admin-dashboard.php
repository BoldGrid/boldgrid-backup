<?php
/**
 * File: boldgrid-backup-admin-dashboard.php
 *
 * This file is used to markup dashboard page.
 *
 * @link https://www.boldgrid.com
 * @since xxx
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';
?>

<div class='wrap'>
	<h1><?php esc_html_e( 'BoldGrid Backup Dashboard', 'boldgrid-backup' ); ?></h1>

	<?php
	echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

	/*
	 * Get our next runtime and display it.
	 *
	 * This code is temporary. It is not translatable, it's not being displayed properly, etc...
	 */
	$cron = new Boldgrid\Backup\Admin\Cron();

	$backup_entry = $cron->get_entry( 'backup' );

	$next_runtime = 'No backup is scheduled';
	if ( ! empty( $backup_entry ) && $backup_entry->is_set() ) {
		$this->core->time->init( $backup_entry->get_next_runtime(), 'utc' );
		$next_runtime = $this->core->time->get_span( 'D, M jS, g:ia' );
	}

	echo '
		<p>
			<strong>Next backup scheduled to run</strong>:
			<em>' . esc_html( $next_runtime ) . '</em>
		</p>';
	?>
</div>
