<?php
/**
 * Boldgrid Backup Admin Remote.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * Boldgrid Backup Admin Remote class.
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Remote {

	/**
	 * The core class object.
	 *
	 * @since  1.5.2
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Return whether or not a remote storage provider is enabled.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $id amazon_s3
	 * @return bool
	 */
	public function is_enabled( $id ) {
		$settings = $this->core->settings->get_settings();

		return ! empty( $settings['remote'][$id]['enabled'] ) && true === $settings['remote'][$id]['enabled'] ;
	}

	/**
	 * Take action after a backup has been downloaded remotely.
	 *
	 * @since 1.5.4
	 */
	public function post_download( $filepath ) {
		$this->core->archive_log->restore_by_zip( $filepath );
	}
}
