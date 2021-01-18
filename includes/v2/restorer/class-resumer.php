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

namespace Boldgrid\Backup\V2\Restorer;

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
			'mode=resume_restore',
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
		$cron  = new \Boldgrid\Backup\Admin\Cron();
		$entry = $cron->get_entry( 'resume_restore' );

		if ( ! $entry->is_set() ) {
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
		error_log( 'RUNNING RESTORE RESUMER...' ); // phpcs:ignore

		$option              = \Boldgrid\Backup\V2\Restorer\Utility::get_option();
		$backup_id           = $option->get_key( 'backup_id' );
		$restore_id          = $option->get_key( 'restore_id' );
		$restore_in_progress = ! empty( $backup_id ) && ! empty( $restore_id );

		if ( $restore_in_progress ) {
			$step = \BoldGrid\Backup\V2\Restorer\Factory::run( $backup_id, $restore_id );
			if ( $step->is_unresponsive() ) {
				error_log( 'RESUMING RESTORE!' ); // phpcs:ignore
				$step->log( 'Resuming restoration...' );

				$restorer = new \Boldgrid_Backup_Restorer( $backup_id, $restore_id );
				$restorer->run();
			} else {
				$step->log( 'Not resuming restoration. Not unresponsive.' );
				error_log( 'NOT RESUMING RESTORE - NOT UNRESONSIVE' ); // phpcs:ignore
			}
		} else {
			$this->remove_cron();
			error_log( 'NOT RESUMING - NO backup_id and restore_id FOUND' ); // phpcs:ignore
		}
	}
}
