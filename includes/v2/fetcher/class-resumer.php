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

namespace Boldgrid\Backup\V2\Fetcher;

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
			'mode=resume_fetch',
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
		$entry = $cron->get_entry( 'resume_fetch' );

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
		$fetcher = \Boldgrid\Backup\V2\Fetcher\Factory::run_by_resumer();
		if ( empty( $fetcher ) ) {
			$this->remove_cron();
			return;
		}

		if ( $fetcher->is_unresponsive() ) {
			$fetcher->log( 'Fetcher resumer: Running, prior process unresponsive.' );
			$fetcher->run();
		} else {
			$fetcher->log( 'Fetcher resumer: Not running, prior process still responsive.' );
		}
	}
}
