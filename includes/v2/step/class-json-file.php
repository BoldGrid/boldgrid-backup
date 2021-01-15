<?php
/**
 * JSON File class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Step;

/**
 * Class: JSON File
 *
 * @since SINCEVERSION
 */
class Json_File {
	/**
	 *
	 */
	private $filepath;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param \Boldgrid\Backup\V2\Step\Step $step Our parent step.
	 * @param string                        $id   The id of our step.
	 */
	public function __construct( $filepath ) {
		$this->filepath = $filepath;

		$this->core = apply_filters( 'boldgrid_backup_get_core', null );

		// If the info.json file does not exist, create it.
		if ( ! $this->core->wp_filesystem->exists( $this->filepath ) ) {
			$this->core->wp_filesystem->touch( $this->filepath );
		}
	}

	/**
	 *
	 */
	public function get() {
		$file_contents = $this->core->wp_filesystem->get_contents( $this->filepath );

		$info = json_decode( $file_contents, true );
		$info = empty( $info ) ? array() : $info;

		return $info;
	}

	/**
	 *
	 */
	public function get_key( $key, $default = false ) {
		$info = $this->get();

		return ( isset( $info[ $key ] ) ? $info[ $key ] : $default );
	}

	/**
	 *
	 */
	public function set_key( $key, $value ) {
		$info = $this->get();

		$info[ $key ] = $value;

		$this->write( $info );
	}

	/**
	 *
	 */
	public function set_keys( $array ) {
		foreach ( $array as $key => $value ) {
			$this->set_key( $key, $value );
		}
	}

	/**
	 *
	 */
	public function write( $info ) {
		$this->core->wp_filesystem->put_contents( $this->filepath, wp_json_encode( $info ) );
	}
}
