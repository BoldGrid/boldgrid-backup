<?php
/**
 * File: class-boldgrid-backup-admin-dashboard-widget.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.10.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Dashboard_Widget
 *
 * @since 1.10.0
 */
class Boldgrid_Backup_Admin_Dashboard_Widget {
	/**
	 * The core class object.
	 *
	 * @since  1.10.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Filter the item in the dashboard widget.
	 *
	 * @since 1.10.0
	 *
	 * @param  \Boldgrid\Library\Library\Ui\Feature    $feature The feature object.
	 * @param  \Boldgrid\Library\Library\Plugin\Plugin $plugin  The plugin object.
	 * @return \Boldgrid\Library\Library\Ui\Feature
	 */
	public function filter_feature( Boldgrid\Library\Library\Ui\Feature $feature, \Boldgrid\Library\Library\Plugin\Plugin $plugin ) {
		// Full site protection.
		if ( ! $this->core->settings->has_full_protection() ) {
			$feature->content .= '<div class="notice notice-error inline"><p>' . wp_kses(
				sprintf(
					// translators: 1 The opening anchor tag to the Inspirations page, 2 its closing tag.
					__( 'Not fully protected. %1$sFix this%2$s.', 'boldgrid-backup' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-dashboard' ) ) . '">',
					'</a>'
				),
				array( 'a' => array( 'href' => array() ) )
			) . '</p></div>';
		}

		// Show notices if the user has the premium plugin but needs to enable it, or a similar situation.
		$notices = $this->core->go_pro->get_admin_notices();
		foreach ( $notices as $notice ) {
			if ( $notice['show'] ) {
				$feature->content .= '<div class="' . $notice['class'] . ' inline">' . $notice['message'] . '</div>';
			}
		}

		return $feature;
	}
}
