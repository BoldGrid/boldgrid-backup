<?php
/**
 * Time class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Time Class.
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Time {

	/**
	 * The core class object.
	 *
	 * @since  1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * UTC Time.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    int
	 */
	public	$utc_time;

	/**
	 * Local time (local to a user).
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    int
	 */
	public $local_time;

	/**
	 * Local timezon (local to a user).
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    string
	 */
	public $local_timezone;

	/**
	 * Server offset.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    int
	 */
	public $server_offset = null;

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get a server's UTC offset.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public function get_server_offset() {
		if( is_null( $this->server_offset ) ) {
			$this->server_offset = $this->core->execute_command( 'date +%:::z' );
		}

		return $this->server_offset;
	}

	/**
	 * Get a <span> of our time.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $format
	 * @return string
	 */
	public function get_span( $format = 'M j, Y h:i a' ) {
		if( empty( $this->local_time ) ) {
			return '';
		}

		return sprintf(
			'<span title="%1$s">%2$s</span>',
			$this->local_timezone,
			date( $format, $this->local_time )
		);
	}

	/**
	 * Init a time.
	 *
	 * @since 1.6.0
	 *
	 * @param int    $time In seconds.
	 * @param string $type The type of type ( utc, local, -5)
	 */
	public function init( $time, $type = 'utc' ) {
		if( empty( $time ) ) {
			$this->reset();
			return;
		}

		$gmt_offset = get_option('gmt_offset');
		$valid_gmt_offset = ! empty( $gmt_offset ) || is_numeric( $gmt_offset );

		$this->local_timezone = 'UTC';

		/*
		 * Determine our UTC time.
		 *
		 * By default, we'll assume the $time passed in is UTC, such as a file's
		 * mtime.
		 *
		 * If the time we have s from a php_zip archive, then the time is the
		 * server's local timezone. If we can get the server's timezone offset,
		 * we can then calculate UTC and the User's local time (set in WordPress).
		 */
		$this->utc_time = $time;
		if( 'php_zip' === $type || 'local' === $type ) {
			$server_offset = $this->get_server_offset();

			// We can calculate UTC time with the server's timezone.
			if( is_numeric( $server_offset ) ) {
				$this->utc_time = $time + ( -1 * $server_offset * HOUR_IN_SECONDS );
			}
		}

		// Once we have the UTC time (above), we can calculate the user's local time.
		if( $valid_gmt_offset ) {
			$this->local_time = $this->utc_time + ( $gmt_offset * HOUR_IN_SECONDS );
			$this->local_timezone .= ' ' . $gmt_offset;
		} else {
			$this->local_time = $this->utc_time;
		}
	}

	/**
	 * Reset the class.
	 *
	 * @since 1.6.0
	 */
	public function reset() {
		$this->local_time = null;
		$this->local_timezone = null;
		$this->utc_time = null;
	}
}
