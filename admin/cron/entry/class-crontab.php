<?php
/**
 * Crontab Entry class.
 *
 * This class represents a single entry in the crontab.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Cron\Entry;

use Boldgrid\Backup\Admin\Cron\Entry\Entry;
use Boldgrid\Backup\Admin\Cron\Entry\Base;

/**
 * Class: Crontab Entry.
 *
 * @since 1.11.0
 */
class Crontab extends Base implements Entry {
	/**
	 * Our cron entry's command.
	 *
	 * Given the following entry:
	 * "* * * * * COMMAND"
	 *
	 * This property is the "COMMAND" part.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var string
	 */
	private $command;

	/**
	 * Whether or not this cron exists in the crontab.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var bool
	 */
	private $is_set;

	/**
	 * The cron's entire command.
	 *
	 * Given the following entry:
	 * "* * * * * COMMAND"
	 *
	 * The raw command is the entire string, "* * * * * COMMAND".
	 *
	 * @since 1.11.0
	 * @access private
	 * @var string
	 */
	private $raw_command;

	/**
	 * The time defined for the cron.
	 *
	 * Given the following entry:
	 * "* * * * * COMMAND"
	 *
	 * The time is the "* * * * *" part.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var string
	 */
	private $time;

	/**
	 * An instance of Boldgrid\Backup\Admin\Cron\Crontab.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var Boldgrid\Backup\Admin\Cron\Crontab
	 */
	private $engine;

	/**
	 *
	 */
	public function __construct() {
		$this->engine = new \Boldgrid\Backup\Admin\Cron\Crontab();
	}

	/**
	 * Get our cron's next runtime.
	 *
	 * @since 1.11.0
	 *
	 * @return string The unix timestamp (UTC) of when this cron will run next.
	 */
	public function get_next_runtime() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		require_once BOLDGRID_BACKUP_PATH . '/vendor/boldgrid/tdcron/class.tdcron.php';
		require_once BOLDGRID_BACKUP_PATH . '/vendor/boldgrid/tdcron/class.tdcron.entry.php';

		/*
		 * Get our next runtime.
		 *
		 * Cron jobs are configured to run on the server's timezone, not UTC. Therefore, our next
		 * runtime will be server time.
		 */
		$next_runtime = \tdCron::getNextOccurrence( $this->time );

		/*
		 * Initialize our time class with our $next_runtime, and specify the time is in the server's
		 * timezone (local).
		 */
		$core->time->init( $next_runtime, 'local' );

		return $core->time->utc_time;
	}

	/**
	 * Init this cron entry.
	 *
	 * @since 1.11.0
	 *
	 * @return mixed
	 */
	public function init_via_search( array $patterns = [] ) {
		$this->is_set = false;

		$matched_crons = $this->engine->find_crons( $patterns );

		if ( 1 === count( $matched_crons ) ) {
			$this->raw_command = $matched_crons[0];

			$exploded_command = explode( ' ', trim( $this->raw_command ) );

			$time       = array_slice( $exploded_command, 0, 5 );
			$this->time = implode( ' ', $time );

			$command       = array_splice( $exploded_command, 5 );
			$this->command = implode( ' ', $command );

			$this->is_set = true;
		}
	}

	/**
	 * Whether or not this cron entry exists in the crontab.
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	public function is_set() {
		return $this->is_set;
	}
}
