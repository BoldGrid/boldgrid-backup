<?php
/**
 * File: note-pre-backup.php
 *
 * Display a note for the user next to the "Backup Site Now" button.
 *
 * @link https://www.boldgrid.com
 * @since 1.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archives
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;
?>

<p id='note-pre-backup'>
<?php

	/*
	 * Print this text:
	 *
	 * Note: Backups use resources and <a>must pause your site</a> momentarily.  Use sparingly.
	 */
	printf(
		wp_kses(
			// translators: 1: URL address.
			__(
				'<strong>Note</strong>: Backups use resources and <a href="%s" target="_blank">must pause your site</a> momentarily.  Use sparingly. ',
				'boldgrid-backup'
			),
			array(
				'a'      => array(
					'href'   => array(),
					'target' => array(),
				),
				'strong' => array(),
			)
		),
		esc_url( $this->configs['urls']['resource_usage'] )
	);

	/*
	 * Print this text:
	 *
	 * You currently have x backups stored on your server, and your <a>backup settings</a> are
	 * only configured to store x. Backing up your site now will delete your oldest backup to
	 * make room for your new backup. We recommend you download a backup to your local computer.
	 */
	if ( count( $archives ) >= $settings['retention_count'] ) {
		printf(
			wp_kses(
				// translators: 1: Archive count, 2: Retention limit, 3: URL address.
				__(
					'You currently have %1$s backups stored on your server, and your <a href="%3$s">backup settings</a> are only configured to store %2$s. Backing up your site now will delete your oldest backup to make room for your new backup. We recommend you download a backup to your local computer.',
					'boldgrid-backup'
				),
				array(
					'a' => array( 'href' => array() ),
				)
			),
			count( $archives ),
			esc_html( $settings['retention_count'] ),
			esc_url( get_admin_url( null, 'admin.php?page=boldgrid-backup-settings' ) )
		);
	}
	?>
</p>
