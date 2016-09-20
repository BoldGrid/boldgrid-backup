<?php
/**
 * The admin-specific notice methods for the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.2
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin notice class.
 *
 * @since 1.2
 */
class Boldgrid_Backup_Admin_Notice {
	/**
	 * Print an admin notice.
	 *
	 * @since 1.2
	 *
	 * @param string $message A message to display in the admin notice.
	 * @param string $class The class string for the div.
	 */
	public function boldgrid_backup_notice( $message, $class = 'notice notice-error is-dismissible' ) {
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}

	/**
	 * Queue a notice for a failed functionality test.
	 *
	 * @since 1.2.3
	 *
	 * @param bool $use_link Link to the Functionality Tests page.  Default is TRUE.
	 */
	public function functionality_fail_notice( $use_link = true ) {
		if ( $use_link ) {
			$message = sprintf(
				esc_html__(
					'Functionality test has failed.  You can go to %1$sFunctionality Test%2$s to view a report.',
					'boldgrid-backup'
				),
				'<a href="' . admin_url( 'admin.php?page=boldgrid-backup-test' ) . '">',
				'</a>'
			);
		} else {
			$message = esc_html__( 'Functionality test has failed.', 'boldgrid-backup' );
		}

		do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );
	}
}
