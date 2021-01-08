<?php
/**
 * File: class-info.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Archiver;

/**
 * Class: Info
 *
 * @since SINCEVERSION
 */
class Info {

	/**
	 *
	 * @var \BoldGrid\Backup\V2\Archiver\Archiver
	 */
	private $backup_process;

	private $info;

	private $type;

	/**
	 *
	 */
	public function __construct( $type, $value ) {
		$this->type = $type;

		if ( 'one' === $this->type ) {
			$this->info = $value;
		} else {
			$this->backup_process = $value;
		}
	}

	/**
	 *
	 */
	public function get() {
		if ( 'one' === $this->type ) {
			return $this->info;
		} else {
			return $this->backup_process->get_info()->get();
		}
	}

	/**
	 *
	 */
	public function get_key( $key, $default = false ) {
		$info = $this->get();

		return isset( $info[ $key ] ) ? $info[ $key ] : $default;
	}

	/**
	 *
	 */
	public function set_key( $key, $value ) {
		if ( 'one' === $this->type ) {
			$this->info[ $key ] = $value;
		} else {
			$this->backup_process->get_info()->set_key( $key, $value );
		}
	}

	/**
	 *
	 */
	public function set( $info ) {
		if ( 'one' === $this->type ) {
			$this->info = $info;
		} else {
			$this->backup_process->get_info()->write( $info );
		}
	}

	/**
	 *
	 */
	public function set_keys( $array ) {
		foreach ( $array as $key => $value ) {
			$this->set_key( $key, $value );
		}
	}
}
