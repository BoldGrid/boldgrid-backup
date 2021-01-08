<?php
/**
 * Part class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver\Steps\Archive_Files;

/**
 * Class: Part
 *
 * This class represents a part of the backup, one of the several zips. For example, each of the following
 * are a part:
 * # plugins-1.zip
 * # plugins-2.zip
 *
 * @since SINCEVERSION
 */
class Part {
	/**
	 * Our parent "archive files" class.
	 *
	 * It could represent archiving plugins or themes for example.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files
	 */
	private $archive_files;

	/**
	 *
	 */
	private $configs;

	/**
	 * The filepath to this part.
	 *
	 * For example, /home/user/backups/1234/plugins-1.zip
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $filepath;

	/**
	 * This part's key.
	 *
	 * IE if this is plugins-1.zip, the key is 0.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $key;

	/**
	 * The max size of this part.
	 *
	 * IE each plugins-#.zip file can only be 100MB.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $max_size = 100 * 1000000;

	/**
	 * This part's number.
	 *
	 * IE the plugins-1.zip
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $number;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files $archive_files Our parent arching files class.
	 * @param int                                              $number        This part number.
	 */
	public function __construct( \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files $archive_files, $number ) {
		$this->archive_files = $archive_files;
		$this->number        = $number;
		$this->key           = $number - 1;

		$filename       = 'zip-' . $this->archive_files->get_configs()['type'] . '-' . $number . '.zip';
		$this->filepath = $this->archive_files->get_path_to( $filename );
	}

	/**
	 * Add a batch to this part.
	 *
	 * IE plugins-1.zip (this part) is made up of batches (batch-1.txt, batch-2.txt, etc). This method
	 * adds one of the batch files to the zip.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $batch_filelist_filepath The path to the batch file.
	 * @return bool True on success
	 */
	public function add_batch( $batch_filelist_filepath ) {
		$success = false;
		$return  = 'unknown';

		$original_size = $this->get_size();

		$j = empty( $this->configs['junk_paths'] ) ? '' : '-j';

		$archive_command = 'cd ' . ABSPATH . '; zip ' . $this->filepath . ' ' . $j . ' -@ < ' . $batch_filelist_filepath;

		// error_log( '$archive_command = ' . getmypid() . ' ' . $archive_command );

		$this->archive_files->get_core()->execute_command( $archive_command, $success, $return );

		$new_size = $this->get_size();

		return ( $original_size !== $new_size ) && $success;
	}

	/**
	 * Steps to take when this part is complete.
	 *
	 * IE we've hit the max filesize set for parts, or we're done backing up.
	 *
	 * @since SINCEVERSION
	 */
	public function complete() {
		$this->set_key( 'complete_time', time() );
		$this->set_key( 'filename', basename( $this->filepath ) );
	}

	/**
	 * Get the max size for this part.
	 *
	 * @since SINCEVERSION
	 *
	 * @return int
	 */
	public function get_max_size() {
		return $this->max_size;
	}

	/**
	 * Get this part number.
	 *
	 * @since SINCEVERSION
	 *
	 * @return int
	 */
	public function get_number() {
		return $this->number;
	}

	/**
	 * Get the remaining size available for this part.
	 *
	 * Based upon the max size we've set for a part.
	 *
	 * @since SINCEVERSION
	 *
	 * @return int
	 */
	public function get_remaining_size() {
		return $this->max_size - $this->get_size();
	}

	/**
	 * Get the current size of this part.
	 *
	 * @since SINCEVERSION
	 *
	 * @return int
	 */
	public function get_size() {
		// PHP will cache the size. Clear the cache.
		clearstatcache();

		return $this->archive_files->get_core()->wp_filesystem->size( $this->filepath );
	}

	/**
	 * Determine whether or not this part is empty.
	 *
	 * IE if it's empty, it's a new part and we haven't net written any batches to it.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function is_empty() {
		$size = $this->get_size();

		return empty( $size );
	}

	/**
	 *
	 */
	public function set_configs( $configs ) {
		$defaults = array(
			'junk_paths' => false,
		);

		$this->configs = wp_parse_args( $configs, $defaults );
	}

	/**
	 * Set a key / value for this part.
	 *
	 * IE set when the part was completed, the filename of the part, etc.
	 *
	 * This is somewhat of a hack, it stores this data in the parent archive_files class.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $key   The key for this data.
	 * @param mixed  $value The data to store.
	 */
	public function set_key( $key, $value ) {
		$data = $this->archive_files->get_data_type( 'step' )->get_data();

		$data['parts'][ $this->key ][ $key ] = $value;

		$this->archive_files->get_data_type( 'step' )->set_key( 'parts', $data['parts'] );
	}
}
