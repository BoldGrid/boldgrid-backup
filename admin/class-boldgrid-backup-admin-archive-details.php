<?php
/**
 * Archive Details class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Archive Details Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Details {

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Render the details page of an archive.
	 *
	 * @since 1.5.1
	 */
	public function render_archive() {
		wp_enqueue_style(
			'boldgrid-backup-render-archive',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-render-archive.css',
			array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		$md5 = ! empty( $_GET['md5'] ) ? $_GET['md5'] : false;
		$archive_found = false;

		if( ! $md5 ) {
			echo __( 'No archive specified.', 'boldgrid-backup' );
			return;
		}

		$archives = $this->core->get_archive_list();
		if( empty( $archives ) ) {
			echo __( 'No archives available. Is your backup directory configured correctly?', 'boldgrid-backup' );
			return;
		}

		foreach( $archives as $archive ) {
			if( $md5 === md5( $archive['filepath'] ) ) {
				$log = $this->core->archive_log->get_by_zip( $archive['filepath'] );
				$archive = array_merge( $log, $archive );
				$archive_found = true;
				break;
			}
		}

		if( ! $archive_found ) {
			echo __( 'Archive not found.', 'boldgrid-backup' );
			return;
		}

		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-archive-details.php';
	}
}
