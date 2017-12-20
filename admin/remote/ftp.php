<?php
/**
 * FTP class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * FTP class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Ftp {

	/**
	 * An FTP connection.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Resource
	 */
	private $connection = null;

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Default port numbers.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $default_port = array(
		'ftp' => 21,
		'sftp' => 22,
	);

	/**
	 * Default type.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $default_type = 'sftp';

	/**
	 * Errors.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $errors = array();

	/**
	 * Hooks class.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Ftp_Hooks
	 */
	public $hooks;

	/**
	 * FTP host.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    string
	 */
	private $host = null;

	/**
	 * Whether or not we have logged in.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    bool
	 */
	public $logged_in = false;

	/**
	 * Our key / label for ftp.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $key = 'ftp';

	/**
	 * FTP password.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    string
	 */
	private $pass = null;

	/**
	 * FTP remote directory.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $remote_dir = 'boldgrid_backup';

	/**
	 * Retention count.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    int $retention_count
	 */
	public $retention_count = 5;

	/**
	 * Default timeout.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    int
	 */
	public $timeout = 10;

	/**
	 * Our title / label for ftp.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $title = 'FTP / SFTP';

	/**
	 * Our FTP type, ftp or sftp.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $type = null;

	/**
	 * FTP username.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    string
	 */
	private $user = null;

	/**
	 * Valid types.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $valid_types = array( 'ftp', 'sftp' );

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		include_once BOLDGRID_BACKUP_PATH . '/vendor/phpseclib/phpseclib/phpseclib/Net/SFTP.php';

		$this->core = $core;
		$this->hooks = new Boldgrid_Backup_Admin_Ftp_Hooks( $core );
		$this->page = new Boldgrid_Backup_Admin_Ftp_Page( $core );
	}

	/**
	 * Connect to our ftp server.
	 *
	 * @since 1.5.4
	 */
	public function connect() {
		if( ! empty( $this->connection ) ) {
			return;
		}

		$this->init();

		if( empty( $this->user ) || empty( $this->pass ) || empty( $this->host ) || empty( $this->type ) || empty( $this->port ) ) {
			return;
		}

		switch( $this->type ) {
			case 'ftp':
				$this->connection = ftp_connect( $this->host, $this->port, $this->timeout );
				break;
			case 'sftp':
				$this->connection = new phpseclib\Net\SFTP( $this->host, $this->port );
				break;
		}
	}

	/**
	 * Create backup directory on remote host.
	 *
	 * @since 1.5.4
	 *
	 * @return bool False when we were unable to create directory.
	 */
	public function create_backup_dir() {
		$this->connect();
		$this->log_in();
		if( ! $this->logged_in ) {
			return false;
		}

		$contents = $this->get_contents();
		if( ! $contents || ! is_array( $contents ) ) {
			$this->errors[] = __( 'Unable to get a directory listing from FTP server.', 'boldgrid-backup' );
			return false;
		} elseif( in_array( $this->remote_dir, $contents, true ) ) {
			return true;
		}

		switch( $this->type ) {
			case 'ftp':
				$created = ftp_mkdir( $this->connection, $this->remote_dir );
				break;
			case 'sftp':
				$created = $this->connection->mkdir( $this->remote_dir );
				break;
		}

		if( ! $created ) {
			$this->errors[] = sprintf( __( 'Unable to create the following directory on FTP server: %1$s', 'boldgrid-backup' ), $this->remote_dir );
		}

		return $created;
	}

	/**
	 * Disconnect from FTP server.
	 *
	 * @since 1.5.4
	 */
	public function disconnect() {
		if( 'ftp' === $this->type && is_resource( $this->connection ) ) {
			ftp_close( $this->connection );
			$this->connection = null;
			$this->logged_in = false;
		}
	}

	/**
	 * Enforce retention.
	 *
	 * @since 1.5.4
	 */
	public function enforce_retention() {
		if( empty( $this->retention_count ) ) {
			return;
		}

		$backups = array();
		$contents = $this->get_contents( true, $this->remote_dir );

		// The contents usually include . and .., so remove 2 from list.
		$count_to_delete = count($contents) - $this->retention_count - 2;

		if( ! is_array( $contents ) || $count_to_delete <= 0 ) {
			return false;
		}

		$backups = $this->format_raw_contents( $contents );

		usort( $backups, function( $a, $b ){
			return $a['time'] < $b['time'] ? -1 : 1;
		});

		for( $x = 0; $x < $count_to_delete; $x++ ) {
			$filename = $backups[$x]['filename'];
			$path = $this->remote_dir . '/' . $filename;

			switch( $this->type ) {
				case 'ftp':
					ftp_delete( $this->connection, $path );
					break;
				case 'sftp':
					$this->connection->delete( $path, false );
					break;
			}

			/**
			 * Remote file deleted due to remote retention settings.
			 *
			 * @since 1.5.4
			 */
			do_action(
				'boldgrid_backup_remote_retention_deleted',
				$this->title,
				$filename
			);
		}
	}

	/**
	 * Get our settings from $_POST.
	 *
	 * @since 1.5.4
	 *
	 * @return array
	 */
	public function get_from_post() {
		$settings = $this->core->settings->get_settings();

		$values = array(
			array(
				'key' => 'host',
				'default' => null,
			),
			array(
				'key' => 'user',
				'default' => null,
			),
			array(
				'key' => 'pass',
				'default' => null,
			),
			array(
				'key' => 'type',
				'default' => $this->default_type,
			),
			array(
				'key' => 'port',
				'default' => $this->default_port[$this->default_type],
			),
			array(
				'key' => 'retention_count',
				'default' => $this->retention_count,
			),
		);

		foreach( $values as $value ) {
			$key = $value['key'];

			if( ! empty( $_POST[$key] ) ) {
				$data[$key] = $_POST[$key];
			} elseif( ! empty( $settings['remote'][$this->key][$key] ) ) {
				$data[$key] = $settings['remote'][$this->key][$key];
			} else {
				$data[$key] = $value['default'];
			}
		}

		return $data;
	}

	/**
	 * Format raw contents.
	 *
	 * This method takes in raw contents and returns an array of backups, with
	 * keys defining timestamp and filename.
	 *
	 * @since 1.5.4
	 *
	 * @param  array $conents Raw contents received from this->get_contents.
	 * @return array {
	 *     An array of backups.
	 *
	 *     @type int    $time     Timestamp file was uploaded to ftp server.
	 *     @type string $filename
	 * }
	 */
	public function format_raw_contents( $contents ) {
		$skips = array( '.', '..' );
		$backups = array();

		if( ! is_array( $contents ) ) {
			return array();
		}

		foreach( $contents as $item ) {

			if( 'sftp' === $this->type ) {
				$filename = $item['filename'];
				if( in_array( $filename, $skips, true ) ) {
					continue;
				}

				$backups[] = array(
					'time' => $item['mtime'],
					'filename' => $filename,
				);
			} else {
				$exploded_item = explode( ' ', $item );
				$count = count( $exploded_item );

				$filename = $exploded_item[ $count - 1 ];
				if( in_array( $filename, $skips, true ) ) {
					continue;
				}

				// Get the timestamp.
				$month = $exploded_item[ $count - 4 ];
				$day = $exploded_item[ $count - 3 ];
				$time = $exploded_item[ $count - 2 ];
				$time = strtotime( $month . ' ' . $day . ' ' . $time );

				$backups[] = array(
					'time' => $time,
					'filename' => $filename,
				);
			}
		}

		return $backups;
	}

	/**
	 * Get the remote contents / listing.
	 *
	 * @since 1.5.4
	 *
	 * @param  bool $raw   Whether to get the raw contents (ftp_rawlist) or not
	 *                     (ftp_nlist).
	 * @param  string $dir The directory to get listing of.
	 * @return mixed
	 */
	public function get_contents( $raw = false, $dir = '.' ) {
		$this->connect();
		$this->log_in();
		if( ! $this->logged_in ) {
			$this->errors[] = __( 'Unable to log in to FTP server.', 'boldgrid-backup' );
			return array();
		}

		switch( $this->type ) {
			case 'ftp':
				if( $raw ) {
					return ftp_rawlist( $this->connection, $dir );
				} else {
					return ftp_nlist( $this->connection , $dir );
				}
				break;
			case 'sftp':
				if( $raw ) {
					return $this->connection->rawlist( $dir );
				} else {
					return $this->connection->nlist( $dir );
				}
				break;
		}
	}

	/**
	 * Get settings.
	 *
	 * @since 1.5.4
	 */
	public function get_details() {
		$is_setup = $this->is_setup();

		$settings = $this->core->settings->get_settings();

		return array(
			'title' => $this->title,
			'key' => $this->key,
			'configure' => 'admin.php?page=boldgrid-backup-ftp',
			'is_setup' => $is_setup,
			'enabled' => ! empty( $settings['remote'][$this->key]['enabled'] ) && $settings['remote'][$this->key]['enabled'] && $is_setup,
		);
	}

	/**
	 * Init properties.
	 *
	 * @since 1.5.4
	 */
	public function init() {
		if( ! empty( $this->user ) || ! empty( $this->pass ) || ! empty( $this->host ) ) {
			return;
		}

		$settings = $this->core->settings->get_settings();

		$labels = array( 'user', 'pass', 'host', 'port', 'type', 'retention_count' );

		foreach( $labels as $label ) {
			$this->$label = ! empty( $settings['remote'][$this->key][$label] ) ? $settings['remote'][$this->key][$label] : null;
		}
	}

	/**
	 * Determine whether or not FTP is setup.
	 *
	 * @since 1.5.4
	 *
	 * @return bool
	 */
	public function is_setup() {
		$this->connect();
		$this->log_in();

		$logged_in = $this->logged_in;

		$this->disconnect();

		return $logged_in;
	}

	/**
	 * Determine if a set of FTP credentials are valid.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $host
	 * @param  string $user
	 * @param  string $pass
	 * @param  int    $port
	 * @param  string $type
	 * @return bool
	 */
	public function is_valid_credentials( $host, $user, $pass, $port, $type ) {
		$connection = false;
		$logged_in = false;

		// Avoid a really long timeout.
		if( 21 === $port && 'sftp' === $type ) {
			return false;
		}

		switch( $type ) {
			case 'ftp':
				$connection = @ftp_connect( $host, $port, $this->timeout );
				break;
			case 'sftp':
				$connection = @new phpseclib\Net\SFTP( $host, $port );
				break;
		}
		if( ! $connection ) {
			$this->errors[] = sprintf( __( 'Unable to connect to %1$s over port %2$s.', 'boldgrid-backup'), $host, $port );
			return false;
		}

		switch( $type ) {
			case 'ftp':
				$logged_in = @ftp_login( $connection, $user, $pass );
				ftp_close( $connection );
				break;
			case 'sftp':
				$logged_in = @$connection->login( $user, $pass );
				break;
		}
		if( ! $logged_in ) {
			$this->errors[] = __( 'Invalid FTP username / password.', 'boldgrid-backup' );
		}

		return false !== $logged_in;
	}

	/**
	 * Log into the FTP server.
	 *
	 * @since 1.5.4
	 *
	 * @return bool
	 */
	public function log_in() {
		if( $this->logged_in ) {
			return;
		}

		// If we tried to connect but don't have a connection, abort.
		$this->connect();
		if( empty( $this->connection ) ) {
			return false;
		}

		switch( $this->type ) {
			case 'ftp':
				$this->logged_in = @ftp_login( $this->connection, $this->user, $this->pass );
				break;
			case 'sftp':
				$this->logged_in = $this->connection->login( $this->user, $this->pass );
				break;
		}

		// If we tried to login and it failed, disconnect.
		if( ! $this->logged_in ) {
			$this->disconnect();
		}
	}

	/**
	 * Set our ftp password.
	 *
	 * @since 1.5.4
	 *
	 * @param string $pass
	 */
	public function set_pass( $pass ) {
		$this->pass = $pass;
	}

	/**
	 * Determine if a backup archive is uploaded to the remote server.
	 *
	 * @since 1.5.4
	 *
	 * @param string $filepath
	 */
	public function is_uploaded( $filepath ) {
		$contents = $this->get_contents( false, $this->remote_dir );

		return ! is_array( $contents ) ? false : in_array( basename( $filepath ), $contents, true );
	}

	/**
	 * Upload a file.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $filepath
	 * @return bool
	 */
	public function upload( $filepath ) {
		$remote_file = $this->remote_dir . '/' . basename( $filepath );

		$this->connect();
		$this->log_in();
		if( ! $this->logged_in ) {
			$this->errors[] = __( 'Unable to log in to ftp server.', 'boldgrid-backup' );
			return false;
		}

		$has_remote_dir = $this->create_backup_dir();
		if( ! $has_remote_dir ) {
			return false;
		}

		switch( $this->type ) {
			case 'ftp':
				$uploaded = ftp_put( $this->connection, $remote_file, $filepath, FTP_BINARY );
				break;
			case 'sftp':
				$uploaded = $this->connection->put( $remote_file, $filepath, 1 );
				break;
		}

		if( ! $uploaded ) {
			$last_error = error_get_last();

			$this->disconnect();
			$this->errors[] = __( 'Unable to upload file.', 'boldgrid-backup' );

			/*
			 * The last error message may be important on a failed uploaded,
			 * such as ftp_put(): Quota exceeded. Make sure the user sees the
			 * last error.
			 */
			if( ! empty( $last_error['message'] ) && ! empty( $last_error['file'] ) && $last_error['file'] === __FILE__ ) {
				$this->errors[] = $last_error['message'];
			}

			return false;
		}

		$this->enforce_retention();

		$this->disconnect();

		return true;
	}
}
