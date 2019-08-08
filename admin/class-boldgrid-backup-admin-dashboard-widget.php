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
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Filter the BoldGrid Backup item in the dashboard widget.
	 *
	 * @since 1.10.0
	 *
	 * @param array $item Our item to filter.
	 * @return array $item
	 */
	public function filter_item( $item ) {
		if ( ! $this->core->settings->has_full_protection() ) {
			$item['subItems'][] = '
				<p>
					<span class="dashicons dashicons-warning"></span> ' .
					wp_kses(
						sprintf(
							// translators: 1 The opening anchor tag to the Inspirations page, 2 its closing tag.
							__( 'Not fully protected. %1$sClick here to learn more%2$s.', 'boldgrid-backup' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-dashboard' ) ) . '">',
							'</a>'
						),
						array( 'a' => array( 'href' => array() ) )
					) . '
				</p>';
		}

		return $item;
	}
}
