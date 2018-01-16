<?php
/**
 * Go Pro class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Go Pro Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Go_Pro {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get a "Get Premium" button.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $url
	 * @param  string $text
	 * @return string
	 */
	public function get_premium_button( $url = 'https://boldgrid.com/update-backup', $text = 'Get Premium' ) {
		return sprintf( '
			<a href="%1$s" class="button button-success" target="_blank">%2$s</a>',
			esc_url( $url ),
			$text
		);
	}
}