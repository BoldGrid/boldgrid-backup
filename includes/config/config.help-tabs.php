<?php
/**
 * File: config.help-tabs.php
 *
 * @link https://www.boldgrid.com
 * @since 1.9.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

return array(
	'tabs'    => array(
		'overview' => array(
			'id'      => 'boldgrid_backup',
			'title'   => __( 'Overview', 'boldgrid-backup' ),
			'content' => '<p>' . wp_kses(
				sprintf(
					// translators: 1 opening anchor tag to the Getting Started Guides, 2 its closing anchor tag, 3 opening anchor tag to Facebook user group.
					__( 'If you have any questions on getting started with BoldGrid Backup, please visit our %1$sGetting Started Guide%2$s. We also suggest joining our %3$sTeam Orange User Group community%2$s for free support, tips and tricks.', 'boldgrid-backup' ),
					'<a href="https://www.boldgrid.com/support/boldgrid-backup/" target="_blank">',
					'</a>',
					'<a href="https://www.facebook.com/groups/BGTeamOrange" target="_blank">'
				),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			) . '</p>',
		),
	),
	'screens' => array(
		'toplevel_page_boldgrid-backup'                 => array(
			'tabs' => array(
				'overview',
			),
		),
		'boldgrid-backup_page_boldgrid-backup-settings' => array(
			'tabs' => array(
				'overview',
			),
		),
		'boldgrid-backup_page_boldgrid-backup-test'     => array(
			'tabs' => array(
				'overview',
			),
		),
		'boldgrid-backup_page_boldgrid-backup-tools'    => array(
			'tabs' => array(
				'overview',
			),
		),
		'admin_page_boldgrid-backup-archive-details'    => array(
			'tabs' => array(
				'overview',
			),
		),
	),
);
