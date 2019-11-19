<?php
/**
 * File :class-boldgrid-backup-admin-time.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Time
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
	public $utc_time;

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
	 * Get the server's current date.
	 *
	 * @since 1.6.5
	 *
	 * @return string
	 */
	public function get_server_date() {
		$date = $this->core->execute_command( 'date' );

		return ! $date ? __( 'Unknown', 'boldgrid-backup' ) : $date;
	}

	/**
	 * Get a server's UTC offset.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public function get_server_offset() {
		if ( is_null( $this->server_offset ) ) {
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
		/*
		 * Determine how we are going to get the server's timezone.
		 *
		 * PHP time zone buffoonery (or my own).
		 *
		 * php -r "new DateTimeZone( '-0400' );"
		 * -------------						--------------
		 * - PHP 5.3.3 -						- PHP 5.6.30 -
		 * -------------						--------------
		 * DateTimeZone::__construct():			Works as expected.
		 * Unknown or bad timezone (-0400)
		 *
		 * php -r '$tz = new DateTimeZone( "EDT" ); echo $tz->getName();';
		 * -------------						--------------
		 * - PHP 5.3.3 -						- PHP 5.6.30 -
		 * -------------						--------------
		 * America/New_York						EDT
		 *
		 * php -r '$tz = new DateTimeZone( "EDT" ); echo $tz->getName();';
		 * -------------						--------------
		 * - PHP 5.3.3 -						- PHP 5.6.30 -
		 * -------------						--------------
		 * DateTimeZone Object					DateTimeZone Object
		 * (									(
		 * )										[timezone_type] => 2
		 *											[timezone] => EDT
		 *										)
		 */
		$timezone = new DateTimeZone( 'EDT' );
		if ( 'EDT' === $timezone->getName() ) {
			// Example, get -0400.
			$timezone = $this->core->execute_command( 'date +%z' );
		} else {
			// Example, get EDT.
			$timezone = $this->core->execute_command( 'date +%Z' );
		}

		try {
			$timezone = new DateTimeZone( $timezone );
			return $timezone;
		} catch ( Exception $e ) {
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
	 * @param  array $settings Settings.
	 * @param  array $tz_info  Timezone information.
	 * @return DateTime
	 */
	public function get_settings_date( $settings = array(), $tz_info = array() ) {
		// Abort right away if bad data sent in.
		if ( ! is_array( $settings ) || ! is_array( $tz_info ) ) {
			return false;
		}

		$settings = empty( $settings ) ? $this->core->settings->get_settings() : $settings;

		$tz_info = empty( $tz_info ) ? $this->get_timezone_info() : $tz_info;
		$is_utc  = ! empty( $tz_info['abbr'] ) && 'UTC' === substr( $tz_info['abbr'], 0, 3 );

		$time_string = $settings['schedule']['tod_h'] . ':' . $settings['schedule']['tod_m'] . ' ' . $settings['schedule']['tod_a'];

		/*
		 * DateTime Timezone types.
		 *
		 * I didn't comprehend this from:
		 * http://php.net/manual/en/datetime.construct.php
		 * ... However according to
		 * https://stackoverflow.com/questions/17694894/different-timezone-types-on-datetime-object
		 * ... DateTime objects with timezones can be constructed in the following ways:
		 * # Type 1: A UTC offset            new DateTime( "17 July 2013 -0300" );
		 * # Type 2: A timezone abbreviation new DateTime( "17 July 2013 GMT" );
		 * # Type 3: A timezone identifier   new DateTime( "17 July 2013", new DateTimeZone( "Europe/London" ) );
		 */
		try {
			if ( $is_utc ) {
				$date = new DateTime( $time_string, new DateTimeZone( 'UTC' ) );

				// If we have a gmt_offset (example: -4.5), use that, otherwise parse the -4 from UTC-4.
				$offset = ! empty( $tz_info['gmt_offset'] ) ? $tz_info['gmt_offset'] : substr( $tz_info['abbr'], 3 );

				/*
				 * The user may have set UTC-4, but the $date above is simply UTC.
				 * Modify the $date to actually get UTC. So if the user wants
				 * 10:20 am UTC-4 we'll give them 2:20 pm UTC.
				 */
				$date->modify( ( -1 * $offset * HOUR_IN_SECONDS ) . ' second' );
			} else {
				$date = new DateTime( $time_string, new DateTimeZone( $tz_info['name'] ) );
			}
			return $date;
		} catch ( Exception $e ) {
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
	 * @param  float $offset Offset in hours.
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
	 * @param  string $format Date format.
	 * @return string
	 */
	public function get_span( $format = 'M j, Y h:i a' ) {
		if ( empty( $this->local_time ) ) {
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
	 * Example: WordPress set to UTC-4
	 * Array
	 * (
	 *     [abbr] => UTC-4
	 *     [description] => Timezone is UTC-4.
	 *     [markup_timezone] => UTC-4
	 *     [markup_change] => Change timezone
	 * )
	 *
	 * Example: WordPress set to NewYork
	 * Array
	 * (
	 *     [abbr] => EDT
	 *     [name] => America/New_York
	 *     [description] => Timezone is America/New York (EDT), currently UTC-4.
	 *     [markup_timezone] => EDT
	 *     [markup_change] => Change timezone
	 * )
	 *
	 * Example: WordPress set to UTC-4.5
	 * Array
	 * (
	 *     [gmt_offset] => -4.5
	 *     [abbr] => UTC-4.5
	 *     [description] => Timezone is UTC-4.5.
	 *     [markup_timezone] => UTC-4.5
	 *     [markup_change] => Change timezone
	 * )
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public function get_timezone_info() {
		$tz_string     = get_option( 'timezone_string' );
		$timezone_info = array();

		if ( $tz_string ) {
			try {
				$tz = new DateTimezone( $tz_string );
			} catch ( Exception $e ) {
				$tz = '';
			}

			if ( $tz ) {
				$now                   = new DateTime( 'now', $tz );
				$formatted_gmt_offset  = sprintf( 'UTC%s', $this->format_gmt_offset( $tz->getOffset( $now ) / 3600 ) );
				$tz_name               = str_replace( '_', ' ', $tz->getName() );
				$timezone_info['abbr'] = $now->format( 'T' );

				// This set of code is not in core.
				$timezone_info['name'] = $tz->getName();

				/* translators: 1: timezone name, 2: timezone abbreviation, 3: gmt offset  */
				$timezone_info['description'] = sprintf( __( 'Timezone is %1$s (%2$s), currently %3$s.' ), $tz_name, $timezone_info['abbr'], $formatted_gmt_offset );
			} else {
				$timezone_info['description'] = '';
			}
		} else {

			// This set of code is not in core.
			$gmt_offset = get_option( 'gmt_offset', 0 );
			if ( ! empty( $gmt_offset ) ) {
				$timezone_info['gmt_offset'] = $gmt_offset;
			}

			/*
			 * Not sure why WordPress is doing this. If it is -4.5, show me -4.5 and not -4.
			 * $formatted_gmt_offset = $this->format_gmt_offset( intval( $gmt_offset ) );.
			 */
			$formatted_gmt_offset  = $gmt_offset;
			$timezone_info['abbr'] = sprintf( 'UTC%s', $formatted_gmt_offset );

			/* translators: %s: UTC offset  */
			$timezone_info['description'] = sprintf( __( 'Timezone is %s.' ), $timezone_info['abbr'] );
		}

		// This set of code is not in core.
		$timezone_info['markup_timezone'] = sprintf( '<span title="%1$s">%2$s</span>', esc_attr( $timezone_info['description'] ), $timezone_info['abbr'] );
		$timezone_info['markup_change']   = sprintf(
			'<a href="%1$s" title="%3$s">%2$s</a>',
			admin_url( 'options-general.php' ),
			__( 'Change timezone', 'boldgrid-backup' ),
			esc_html( __( 'WordPress timezone settings can be adjusted within Settings &raquo; General', 'boldgrid-backup' ) )
		);

		return $timezone_info;
	}

	/**
	 * Init a time.
	 *
	 * @since 1.6.0
	 *
	 * @param int    $time In seconds.
	 * @param string $type The type of type (utc, local, -5).
	 */
	public function init( $time, $type = 'utc' ) {
		if ( empty( $time ) ) {
			$this->reset();
			return;
		}

		$gmt_offset       = get_option( 'gmt_offset' );
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
		if ( 'php_zip' === $type || 'local' === $type ) {
			$server_offset = $this->get_server_offset();

			// We can calculate UTC time with the server's timezone.
			if ( is_numeric( $server_offset ) ) {
				$this->utc_time = $time + ( -1 * $server_offset * HOUR_IN_SECONDS );
			}
		}

		// Once we have the UTC time (above), we can calculate the user's local time.
		if ( $valid_gmt_offset ) {
			$this->local_time      = $this->utc_time + ( $gmt_offset * HOUR_IN_SECONDS );
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
		$this->local_time     = null;
		$this->local_timezone = null;
		$this->utc_time       = null;
	}
}
