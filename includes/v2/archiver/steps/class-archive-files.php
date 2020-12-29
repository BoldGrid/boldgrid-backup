<?php
/**
 * Archive Files class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver\Steps;

/**
 * Class: Archive_Files
 *
 * This class is used to archive a "type" of file. For example, this class can represet "plugins",
 * "themes", etc.
 *
 * @since SINCEVERSION
 */
class Archive_Files extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * An array of configs.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $configs;

	/**
	 * An array of files belonging to this "type".
	 *
	 * IE An array of all "plugins".
	 *
	 * Set via the self::set_filelist() method.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $filelist;

	/**
	 * The last key of the filelist archived.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $last_archived_key;

	/**
	 * The max batch size.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $max_batch_size = 25 * 1000000;

	/**
	 * Our parts class.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files\Parts
	 */
	private $parts;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $id     The id of this step.
	 * @param string $dir    The backup directory.
	 * @param array  $configs An array of configs.
	 */
	public function __construct( $id, $dir, $configs ) {
		$this->configs = $configs;

		parent::__construct( $id, $dir );

		$this->parts = new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files\Parts( $this );
	}

	/**
	 * Get our next batch data.
	 *
	 * Not too complicated, but probably the most complex part of the backup process. Need to loop through
	 * all the files and determine how to create the batches so the parts are as close as possible to
	 * the max.
	 *
	 * @since SINCEVERSION
	 *
	 * @return mixed An array of data on success, false on failure.
	 */
	private function get_next_batch() {
		// An array of files to add during this batch.
		$batch_filelist = array();

		$start_key = empty( $this->last_archived_key ) ? 0 : $this->last_archived_key + 1;

		$files_added = 0;

		$part = $this->parts->get_next();

		// Determine the max size to archive during this batch.
		$max_batch_size = min( $part->get_remaining_size(), $this->max_batch_size );
		$remaining_size = $max_batch_size;
		$is_part_empty  = $part->is_empty();

		// If the very next file would push us over the limit, we need to get a new part.
		if ( ! $is_part_empty && $this->filelist[ $start_key ][2] > $remaining_size ) {
			$part->complete();
			$part = $this->parts->get_next();

			// Determine the max size to archive during this batch.
			$max_batch_size = min( $part->get_remaining_size(), $this->max_batch_size );
			$remaining_size = $max_batch_size;
			$is_part_empty  = $part->is_empty();
		}

		// At this point, no matter what, we're adding at least one file.
		foreach ( $this->filelist as $key => $file ) {
			if ( $key < $start_key ) {
				continue;
			}

			$too_big        = $file[2] > $remaining_size;
			$allow_in_batch = ! $too_big || ( 0 === $files_added && $is_part_empty );

			if ( $allow_in_batch ) {
				$batch_filelist[]        = $file[1];
				$remaining_size         -= $file[2];
				$this->last_archived_key = $key;
				$files_added++;
			} else {
				break;
			}
		}

		// Write the batch file.
		$batch_filelist_filename = 'filelist-' . $this->configs['type'] . '-' . $start_key . '.txt';
		$batch_filelist_filepath = $this->get_path_to( $batch_filelist_filename );
		$success                 = $this->get_core()->wp_filesystem->put_contents( $batch_filelist_filepath, implode( PHP_EOL, $batch_filelist ) );

		$batch_info = array(
			'batch_filelist_filepath' => $batch_filelist_filepath,
			'part'                    => $part,
		);

		return $success ? $batch_info : false;
	}

	/**
	 * Get our configs.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_configs() {
		return $this->configs;
	}

	/**
	 * Archive files.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();
		$this->set_filelist();

		$last_key                = count( $this->filelist ) - 1;
		$this->last_archived_key = $this->get_data_type( 'step' )->get_key( 'last_archived_key', 0 );
		$attempts                = 1;

		while ( $this->last_archived_key < $last_key ) {
			$batch_info = $this->get_next_batch();

			if ( false !== $batch_info ) {
				$success = $batch_info['part']->add_batch( $batch_info['batch_filelist_filepath'] );

				if ( $success ) {
					// Save the last key we successfully archived.
					$this->get_data_type( 'step' )->set_key( 'last_archived_key', $this->last_archived_key );

					// If we've archived all the files, flag the last part as complete.
					if ( $this->last_archived_key === $last_key ) {
						$batch_info['part']->complete();
					}
				}
			}

			$attempts++;
		}

		$this->complete();
	}

	/**
	 * Set our filelist.
	 *
	 * @since SINCEVERSION
	 */
	private function set_filelist() {
		$filelist_filepath = $this->get_path_to( 'filelist-' . $this->configs['type'] . '.json' );
		$json              = $this->get_core()->wp_filesystem->get_contents( $filelist_filepath );

		$this->filelist = json_decode( $json, true );
	}
}
