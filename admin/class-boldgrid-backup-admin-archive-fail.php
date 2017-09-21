<?php
/**
 * BoldGrid Backup Admin Archive Fail.
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
 * BoldGrid Backup Admin Archive Fail Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Fail {
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
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add actions to "boldgrid_backup_archive_files_init".
	 *
	 * The "boldgrid_backup_archive_files_init" action is done as the first
	 * thing within the archive files method.
	 *
	 * @since 1.5.2
	 */
	public function archive_files_init() {
		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	/**
	 * Hook into shutdown.
	 *
	 * @since 1.5.2
	 */
	public function shutdown() {

		/*
		 * If an archive fails, there may be a rogue db dump sitting out there.
		 * If it exists, delete it, it should be in the archive file.
		 */
		if( $this->core->wp_filesystem->exists( $this->core->db_dump_filepath ) ) {
			$this->core->wp_filesystem->delete( $this->core->db_dump_filepath );
		}

		if( $this->core->doing_cron ) {
			return;
		}

		$last_error = error_get_last();

		/*
		 * If there's no error or this is not fatal, abort.
		 *
		 * @see http://php.net/manual/en/errorfunc.constants.php
		*/
		if( empty( $last_error ) || 1 !== $last_error['type'] ) {
			return;
		}

		$error_text = __( 'We were unable to create a backup of your website due to the following:', 'boldgrid-backup' ) . '<br />';

		$error_text .= sprintf(
			'<strong>%1$s</strong>: %2$s in %3$s on line %4$s',
			__( 'Fatal error', 'boldgrid-backup' ),
			$last_error['message'],
			$last_error['file'],
			$last_error['line']
		);

		$data['errorText'] = $error_text;

		wp_send_json_error( $data );
	}
}
