<?php
/**
 * SFTP class.
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
 * SFTP class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Sftp {

	/**
	 * An SFTP connection.
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
	 * Hooks class.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Sftp_Hooks
	 */
	public $hooks;

	/**
	 * SFTP host.
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
	 * Our key / label for sftp.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $key = 'sftp';

	/**
	 * SFTP password.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    string
	 */
	private $pass = null;

	/**
	 * SFTP port.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    int
	 */
	public $port = 22;

	/**
	 * SFTP remote directory.
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
	 * Our title / label for sftp.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $title = 'SFTP';

	/**
	 * SFTP username.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    string
	 */
	private $user = null;

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
		// $this->hooks = new Boldgrid_Backup_Admin_Sftp_Hooks( $core );
	}

	/**
	 * Connect to our sftp server.
	 *
	 * @since 1.5.4
	 */
	public function connect() {
		if ( ! empty( $this->connection ) ) {
			return;
		}

		$this->init();

		if ( empty( $this->user ) || empty( $this->pass ) || empty( $this->host ) ) {
			return;
		}

		$this->connection = new phpseclib\Net\SFTP( $this->host, $this->port );
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
		if ( ! $this->logged_in ) {
			return false;
		}

		$contents = $this->get_contents();
		if ( in_array( $this->remote_dir, $contents, true ) ) {
			return true;
		}

		return $this->connection->mkdir( $this->remote_dir );
	}

	/**
	 * Disconnect from SFTP server.
	 *
	 * @since 1.5.4
	 */
	public function disconnect() {
		if ( $this->connection ) {
			// sftp_close( $this->connection );
			// $this->connection = null;
			// $this->logged_in = false;
		}
	}

	/**
	 * Enforce retention.
	 *
	 * @since 1.5.4
	 */
	public function enforce_retention() {
		if ( empty( $this->retention_count ) ) {
			return;
		}

		$backups  = array();
		$contents = $this->get_contents( true, $this->remote_dir );

		// The contents usually include . and .., so remove 2 from list.
		$count_to_delete = count( $contents ) - $this->retention_count - 2;

		if ( ! is_array( $contents ) || $count_to_delete <= 0 ) {
			return false;
		}

		$backups = $this->format_raw_contents( $contents );

		usort(
			$backups, function( $a, $b ) {
				return $a['time'] < $b['time'] ? -1 : 1;
			}
		);

		for ( $x = 0; $x < $count_to_delete; $x++ ) {
			$filename = $backups[ $x ]['filename'];
			$this->connection->delete( $this->remote_dir . '/' . $filename );

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
	 *     @type int    $time     Timestamp file was uploaded to sftp server.
	 *     @type string $filename
	 * }
	 */
	public function format_raw_contents( $contents ) {
		$skips   = array( '.', '..' );
		$backups = array();

		if ( ! is_array( $contents ) ) {
			return array();
		}

		foreach ( $contents as $item ) {
			$exploded_item = explode( ' ', $item );

			$count = count( $exploded_item );

			$filename = $exploded_item[ $count - 1 ];

			if ( in_array( $filename, $skips, true ) ) {
				continue;
			}

			// Get the timestamp.
			$month = $exploded_item[ $count - 4 ];
			$day   = $exploded_item[ $count - 3 ];
			$time  = $exploded_item[ $count - 2 ];
			$time  = strtotime( $month . ' ' . $day . ' ' . $time );

			$backups[] = array(
				'time'     => $time,
				'filename' => $filename,
			);
		}

		return $backups;
	}

	/**
	 * Get the remote contents / listing.
	 *
	 * @since 1.5.4
	 *
	 * @param  bool   $raw   Whether to get the raw contents (sftp_rawlist) or not
	 *                       (sftp_nlist).
	 * @param  string $dir The directory to get listing of.
	 * @return mixed
	 */
	public function get_contents( $raw = false, $dir = '.' ) {
		$this->connect();
		$this->log_in();
		if ( ! $this->logged_in ) {
			return array();
		}

		if ( $raw ) {
			return $this->connection->rawlist( $dir );
		} else {
			return $this->connection->nlist( $dir );
		}
	}

	/**
	 * Get settings.
	 *
	 * @since 1.5.4
	 */
	public function get_details() {
		$settings = $this->core->settings->get_settings();

		return array(
			'title'     => $this->title,
			'key'       => $this->key,
			'configure' => 'admin.php?page=boldgrid-backup-sftp',
			'is_setup'  => $this->is_setup(),
			'enabled'   => ! empty( $settings['remote'][ $this->key ]['enabled'] ) && $settings['remote'][ $this->key ]['enabled'] && $this->is_setup(),
		);
	}

	/**
	 * Init properties.
	 *
	 * @since 1.5.4
	 */
	public function init() {
		if ( ! empty( $this->user ) || ! empty( $this->pass ) || ! empty( $this->host ) ) {
			return;
		}

		$settings = $this->core->settings->get_settings();

		$labels = array( 'user', 'pass', 'host', 'port', 'retention_count' );

		foreach ( $labels as $label ) {
			$this->$label = ! empty( $settings['remote'][ $this->key ][ $label ] ) ? $settings['remote'][ $this->key ][ $label ] : null;
		}
	}

	/**
	 * Determine whether or not SFTP is setup.
	 *
	 * @since 1.5.4
	 *
	 * @return bool
	 */
	public function is_setup() {
		$this->connect();
		$this->log_in();

		return $this->logged_in;
	}

	/**
	 * Determine if a set of SFTP credentials are valid.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $host
	 * @param  string $user
	 * @param  string $pass
	 * @param  int    $port
	 * @return bool
	 */
	public function is_valid_credentials( $host, $user, $pass, $port ) {
		$connection = new phpseclib\Net\SFTP( $host, $port );
		if ( ! $connection ) {
			return false;
		}

		$logged_in = @$connection->login( $user, $pass );
		if ( ! $logged_in ) {
			// sftp_close( $connection );
			$this->errors[] = __( 'Unable to connect and log in.', 'boldgrid-backup' );
			return false;
		}

		// sftp_close( $connection );
		return true;
	}

	/**
	 * Log into the SFTP server.
	 *
	 * @since 1.5.4
	 *
	 * @return bool
	 */
	public function log_in() {
		if ( $this->logged_in ) {
			return;
		}

		$this->connect();
		if ( empty( $this->connection ) ) {
			return false;
		}

		$this->logged_in = $this->connection->login( $this->user, $this->pass );
		if ( ! $this->logged_in ) {
			$this->disconnect();
		}
	}

	/**
	 *
	 */
	public function is_uploaded( $filepath ) {
		$contents = $this->get_contents( false, $this->remote_dir );

		return ! is_array( $contents ) ? false : in_array( basename( $filepath ), $contents, true );
	}

	/**
	 * Generate the submenu page for our SFTP Settings page.
	 *
	 * @since 1.5.4
	 */
	public function submenu_page() {
		wp_enqueue_style( 'boldgrid-backup-admin-hide-all' );

		$this->submenu_page_save();

		$settings = $this->core->settings->get_settings();

		$host            = ! empty( $settings['remote'][ $this->key ]['host'] ) ? $settings['remote'][ $this->key ]['host'] : null;
		$user            = ! empty( $settings['remote'][ $this->key ]['user'] ) ? $settings['remote'][ $this->key ]['user'] : null;
		$pass            = ! empty( $settings['remote'][ $this->key ]['pass'] ) ? $settings['remote'][ $this->key ]['pass'] : null;
		$port            = ! empty( $settings['remote'][ $this->key ]['port'] ) ? $settings['remote'][ $this->key ]['port'] : $this->port;
		$retention_count = ! empty( $settings['remote'][ $this->key ]['retention_count'] ) ? $settings['remote'][ $this->key ]['retention_count'] : $this->retention_count;

		include BOLDGRID_BACKUP_PATH . '/admin/partials/remote/sftp.php';
	}

	/**
	 * Process the user's request to update their SFTP settings.
	 *
	 * @since 1.5.4
	 */
	public function submenu_page_save() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if ( empty( $_POST ) ) {
			return false;
		}

		$settings = $this->core->settings->get_settings();
		if ( ! isset( $settings['remote'][ $this->key ] ) || ! is_array( $settings['remote'][ $this->key ] ) ) {
			$settings['remote'][ $this->key ] = array();
		}

		/*
		 * If the user has requested to delete all their settings, do that now
		 * and return.
		 */
		if ( __( 'Delete settings', 'boldgrid-backup' ) === $_POST['submit'] ) {
			$settings['remote'][ $this->key ] = array();
			update_site_option( 'boldgrid_backup_settings', $settings );

			$this->host            = null;
			$this->user            = null;
			$this->pass            = null;
			$this->retention_count = null;
			$this->disconnect();

			do_action( 'boldgrid_backup_notice', __( 'Settings saved.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
			return;
		}

		$errors = array();

		// Get and validate our credentials.
		$host = ! empty( $_POST['host'] ) ? sanitize_file_name( $_POST['host'] ) : null;
		$user = ! empty( $_POST['user'] ) ? sanitize_text_field( $_POST['user'] ) : null;
		$pass = ! empty( $_POST['pass'] ) ? $_POST['pass'] : null;
		$port = ! empty( $_POST['port'] ) ? (int) $_POST['port'] : $this->port;

		$valid_credentials = $this->is_valid_credentials( $host, $user, $pass, $port );

		if ( $valid_credentials ) {
			$settings['remote'][ $this->key ]['host'] = $host;
			$settings['remote'][ $this->key ]['user'] = $user;
			$settings['remote'][ $this->key ]['pass'] = $pass;
			$settings['remote'][ $this->key ]['port'] = $port;
		} elseif ( empty( $this->errors ) ) {
			$this->errors[] = __( 'Unknown error.', 'boldgrid-backup' );
		}

		$retention_count = ! empty( $_POST['retention_count'] ) &&
			is_numeric( $_POST['retention_count'] ) ?
			(int) $_POST['retention_count'] : $this->retention_count;

		$settings['remote'][ $this->key ]['retention_count'] = $retention_count;

		if ( ! empty( $this->errors ) ) {
			do_action( 'boldgrid_backup_notice', implode( '<br /><br />', $this->errors ) );
		} else {
			update_site_option( 'boldgrid_backup_settings', $settings );
			do_action( 'boldgrid_backup_notice', __( 'Settings saved.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
		}
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
		if ( ! $this->logged_in ) {
			$this->errors[] = __( 'Unable to log in to sftp server.', 'boldgrid-backup' );
			return false;
		}

		$has_remote_dir = $this->create_backup_dir();
		if ( ! $has_remote_dir ) {
			$this->errors[] = sprint_f( __( 'Unable to create the following directory on SFTP server: %1$s', 'boldgrid-backup' ), $this->remote_dir );
			return false;
		}

		$uploaded = $this->connection->put( $remote_file, $filepath, NET_SFTP_LOCAL_FILE );
		if ( ! $uploaded ) {
			$this->disconnect();
			$this->errors[] = __( 'Unable to upload file.', 'boldgrid-backup' );
			return false;
		}

		$this->enforce_retention();

		$this->disconnect();

		return true;
	}
}
