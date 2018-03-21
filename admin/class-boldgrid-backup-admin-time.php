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
	 * Get our server's timezone as a DateTimeZone object.
	 *
	 * @since 1.6.0
	 *
	 * @return mixed DateTimeZone object on success, false on failure.
	 */
	public function get_server_timezone() {

		// Will return something like EDT.
		$timezone = $this->core->execute_command( 'date +%Z' );

		try {
			$timezone = new DateTimeZone( $timezone );
			return $timezone;
		} catch( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get a DateTime object based on time set in $settings.
	 *
	 * On our Settings page, the user can enter a time to schedule automated
	 * backups. This method takes that time (hour, minute, ampm) and returns it
	 * as a DateTime object.
	 *
	 * @since 1.6.0
	 *
	 * @param  array    $settings
	 * @return DateTime
	 */
	public function get_settings_date( $settings = array() ) {
		$settings = empty( $settings ) ? $this->core->settings->get_settings() : $settings;

		$tz_info = $this->get_timezone_info();
		$is_utc = 'UTC' === substr( $tz_info['abbr'], 0, 3  );

		if( $is_utc ) {
			$offset = substr( $tz_info['abbr'], 3  );
			$wordpress_timezone = $this->offset_to_timezone( $offset );
		} else {
			$wordpress_timezone = new DateTimeZone( $tz_info['abbr'] );
		}

		$time_string = $settings['schedule']['tod_h'] . ':' . $settings['schedule']['tod_m'] . ' ' . $settings['schedule']['tod_a'];

		try {
			$date = new DateTime( $time_string, $wordpress_timezone );
			return $date;
		} catch( Exception $e ) {
			return false;
		}
	}

	/**
	 * Format GMT Offset.
	 *
	 * This method required by this->get_timezone_info().
	 *
	 * @since 1.6.0
	 *
	 * @param  float  $offset Offset in hours.
	 * @return string         Formatted offset.
	 */
	public function format_gmt_offset( $offset ) {
		if ( 0 <= $offset ) {
			$formatted_offset = '+' . (string) $offset;
		} else {
			$formatted_offset = (string) $offset;
		}
		$formatted_offset = str_replace(
			array( '.25', '.5', '.75' ),
			array( ':15', ':30', ':45' ),
			$formatted_offset
		);
		return $formatted_offset;
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
     * Get timezone info.
     *
     * This is a core WordPress function, copied here. It requires
     * the format_gmt_offset() method, which is copied here as well.
     *
     * The core implementation of this method is used within the Customizer to
     * show a user their timezone when scheduling changes for a future date.
     *
     * In our efforts to use the same formatting as WordPress, we attempted to
     * use this method, however ran into a blocker. This method is defined in the
     * WP_Customize_Date_Time_Control class, which extends the WP_Customize_Control
     * class. The WP_Customize_Control class was designed for use within the
     * Customizer, and cannot really be instantiated outside of it.
     *
     * @since 1.6.0
     *
     * @return array
     */
	public function get_timezone_info() {
		$tz_string = get_option( 'timezone_string' );
		$timezone_info = array();

		if ( $tz_string ) {
			try {
				$tz = new DateTimezone( $tz_string );
			} catch ( Exception $e ) {
				$tz = '';
			}

			if ( $tz ) {
				$now = new DateTime( 'now', $tz );
				$formatted_gmt_offset = sprintf( 'UTC%s', $this->format_gmt_offset( $tz->getOffset( $now ) / 3600 ) );
				$tz_name = str_replace( '_', ' ', $tz->getName() );
				$timezone_info['abbr'] = $now->format( 'T' );

				/* translators: 1: timezone name, 2: timezone abbreviation, 3: gmt offset  */
				$timezone_info['description'] = sprintf( __( 'Timezone is %1$s (%2$s), currently %3$s.' ), $tz_name, $timezone_info['abbr'], $formatted_gmt_offset );
			} else {
				$timezone_info['description'] = '';
			}
		} else {
			$formatted_gmt_offset = $this->format_gmt_offset( intval( get_option( 'gmt_offset', 0 ) ) );
			$timezone_info['abbr'] = sprintf( 'UTC%s', $formatted_gmt_offset );

			/* translators: %s: UTC offset  */
			$timezone_info['description'] = sprintf( __( 'Timezone is %s.' ), $timezone_info['abbr'] );
		}

		// This set of code is not in core.
		$timezone_info['markup_timezone'] = sprintf( '<span title="%1$s">%2$s</span>', esc_attr( $timezone_info['description'] ), $timezone_info['abbr'] );
		$timezone_info['markup_change'] = sprintf(
			'<a href="%1$s" title="%3$s">%2$s</a>',
			admin_url( 'options-general.php' ),
			__( 'Change timezone', 'boldgrid-backup' ),
			esc_attr( __( 'WordPress timezone settings can be adjusted within Settings &raquo; General', 'boldgrid-backup' ) )
		);

		return $timezone_info;
	}

	/**
	 * Convert an offset UTC (-04) to a DateTimeZone object.
	 *
	 * @since 1.6.0
	 *
	 * @param  int          $offset
	 * @return DateTimeZone
	 */
	public function offset_to_timezone( $offset ) {

		if( '+0' === $offset ) {
			return new DateTimeZone( 'UTC' );
		}

		if( ! is_numeric( $offset ) ) {
			return false;
		}

		$zones = timezone_abbreviations_list();
		$offset_seconds = 60 * 60 * $offset;

		foreach( $zones as $zone_abbr => $locations ) {
			foreach( $locations as $location ) {
				if( $offset_seconds === $location['offset'] ) {
					try {
						return new DateTimeZone( $zone_abbr );
					} catch( Exception $e ) {
						return false;
					}
				}
				break;
			}
		}

		return false;
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