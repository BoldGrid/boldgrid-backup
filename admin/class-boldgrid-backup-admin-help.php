<?php
/**
 * File: class-boldgrid-backup-admin-help.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.9.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Help
 *
 * @since 1.9.0
 */
class Boldgrid_Backup_Admin_Help {
	/**
	 * Screen ids.
	 *
	 * The screens listed in this array have a help tab added to them.
	 *
	 * @since 1.9.0
	 * @var array
	 */
	public $screen_ids;

	/**
	 *
	 */
	public function __construct() {
		$this->screen_ids = array(
			'toplevel_page_boldgrid-backup',
			'boldgrid-backup_page_boldgrid-backup-settings',
			'boldgrid-backup_page_boldgrid-backup-test',
			'boldgrid-backup_page_boldgrid-backup-tools',
			'admin_page_boldgrid-backup-archive-details',
		);
	}

	/**
	 * Add our help tab.
	 *
	 * @since 1.9.0
	 */
	public function add() {
		$screen = get_current_screen();

		$args = array(
			'id'      => 'boldgrid_backup',
			'title'   => 'BoldGrid Backup',
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
		);

		$screen->add_help_tab( $args );
	}
}
