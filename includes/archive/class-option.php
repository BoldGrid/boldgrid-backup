<?php
/**
 * Option class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Archive;

/**
 * Class: Option
 *
 * This class is used to manage the boldgrid_backup_backups option.
 *
 * In it's first implementation, each entry in the array represents a single backup, and has an id and
 * a filename. For examples, please see: https://pastebin.com/Wuey2zvP
 *
 * @since SINCEVERSION
 */
class Option {
	/**
	 * The option name storing backups.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $option = 'boldgrid_backup_backups';

	/**
	 * Get all our backups.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_all() {
		return get_option( $this->option, [] );
	}

	/**
	 * Get one backup.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string $filename The filename to look for.
	 * @return array
	 */
	public function get_by_key( $key, $value ) {
		$found_backup = [];

		$backups = $this->get_all();

		foreach ( $backups as $backup ) {
			if ( isset( $backup[ $key ] ) && $backup[ $key ] === $value ) {
				$found_backup = $backup;
				break;
			}
		}

		return $found_backup;
	}

	/**
	 * Get a new id for a new backup being added to the list.
	 *
	 * @since SINCEVERSION
	 *
	 * @return int
	 */
	public function get_next_id() {
		$next_id = 1;

		$backups = $this->get_all();

		foreach ( $backups as $backup ) {
			$id = isset( $backup['id'] ) ? $backup['id'] : 1;

			$next_id = $id >= $next_id ? ( $id + 1 ) : $next_id;
		}

		return $next_id;
	}

	/**
	 * Update a backup entry based on the filename.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $filename The filename to update attributes of.
	 * @param string $key      The key to update.
	 * @param string $value    The value for the key.
	 */
	public function update_by_filename( $filename, $key, $value ) {
		$found = false;

		$backups = $this->get_all();

		// Find our backup by filename, and update the key.
		foreach ( $backups as $k => $backup ) {
			if ( ! empty( $backup['filename'] ) && $backup['filename'] === $filename ) {
				$found = true;

				$backups[ $k ][ $key ] = $value;

				break;
			}
		}

		// If the backup was not found in the array, add it.
		if ( ! $found ) {
			$backups[] = [
				'filename' => $filename,
				$key       => $value,
			];
		}

		update_option( $this->option, $backups );
	}
}
