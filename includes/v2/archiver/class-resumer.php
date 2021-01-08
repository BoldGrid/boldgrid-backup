<?php
/**
 * Resume class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver;

/**
 * Class: Resumer
 *
 * @since SINCEVERSION
 */
class Resumer {
	private $core;

	/**
	 *
	 */
	public function __construct( $core = null ) {
		$this->core = empty( $core ) ? apply_filters( 'boldgrid_backup_get_core', null ) : $core;
	}

	/**
	 *
	 */
	private function add_cron_command() {
		$command = $this->get_cron_command();

		return $this->core->cron->update_cron( $command );
	}

	/**
	 *
	 */
	private function get_cron_command() {
		$command = array(
			'* * * * *',
			$this->core->cron->get_cron_command(),
			'"' . BOLDGRID_BACKUP_PATH . '/boldgrid-backup-cron.php"',
			'mode=resume',
			'siteurl=' . get_site_url(),
			'id=' . $this->core->get_backup_identifier(),
			'secret=' . $this->core->cron->get_cron_secret(),
		);

		return implode( ' ', $command );
	}

	/**
	 *
	 */
	public function maybe_add_cron() {
		$cron         = new \Boldgrid\Backup\Admin\Cron();
		$backup_entry = $cron->get_entry( 'resume' );

		if ( ! $backup_entry->is_set() ) {
			$this->add_cron_command();
		}
	}

	/**
	 *
	 */
	public function remove_cron() {
		$command = $this->get_cron_command();

		return $this->core->cron->entry_delete( $command );
	}

	/**
	 *
	 */
	public function run() {
		error_log( 'RUNNING RESUMER...' ); // phpcs:ignore

		$id = \Boldgrid_Backup_Admin_In_Progress_Data::get_backup_id();
		if ( ! empty( $id ) ) {
			$backup_process = \BoldGrid\Backup\V2\Archiver\Factory::run( $id );
			if ( $backup_process->is_unresponsive() ) {
				error_log( 'RESUMING!' ); // phpcs:ignore
				$archiver = new \Boldgrid_Backup_Archiver( $id );
				$archiver->run();
			} else {
				error_log( 'NOT RESUMING - NOT UNRESONSIVE' ); // phpcs:ignore
			}
		} else {
			$this->remove_cron();
			error_log( 'NOT RESUMING - NO ID FOUND' ); // phpcs:ignore
		}
	}
}
