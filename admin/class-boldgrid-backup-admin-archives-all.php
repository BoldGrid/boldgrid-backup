<?php
/**
 * File: class-boldgrid-backup-admin-archives-all.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archives_All
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Archives_All {
	/**
	 * An array of all archives.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    array
	 */
	public $all = array();

	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Whether or not we have initialized all backups.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    bool
	 */
	public $is_init = false;

	/**
	 * Local server title, such as "Web server".
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    string
	 */
	public $local_title;

	/**
	 * An array of data about remote locations and how many backups at each.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    array
	 */
	public $location_count = array();

	/**
	 * Location types.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    array
	 */
	public $location_types = array(
		'on_web_server',
		'on_remote_server',
	);

	/**
	 * An array of meta information about each archive.
	 *
	 * This array is initialized during init(). Each key of this array is an
	 * archive filename.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    array
	 */
	public $archives = array();

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->local_title = __( 'Web Server', 'boldgrid-backup' );
	}

	/**
	 * Add a backup to the list of all backups.
	 *
	 * @since 1.6.0
	 *
	 * @param array $backup Backup archive information.
	 */
	public function add( $backup ) {
		$in_all = false;

		$is_remote = ! empty( $backup['locations'][0]['on_remote_server'] ) && true === $backup['locations'][0]['on_remote_server'];
		if ( $is_remote ) {
			$this->archives[ $backup['filename'] ]['on_remote_server'] = true;
		}

		// Loop through all of our existing backups to see if this one exists.
		foreach ( $this->all as &$all_backup ) {
			if ( $backup['filename'] === $all_backup['filename'] ) {

				// Once we found our backup, flag that we found it.
				$in_all                    = true;
				$all_backup['locations'][] = $backup['locations'][0];
			}
		}

		// If we didn't find it, add it to the list.
		if ( ! $in_all ) {
			$this->all[] = $backup;
		}
	}

	/**
	 * Determine if an archive has a location type.
	 *
	 * @since 1.6.0
	 *
	 * @param  array  $archive       Archive information.
	 * @param  string $location_type Location type.
	 * @return bool
	 */
	public function has_location_type( $archive, $location_type ) {
		foreach ( $archive['locations'] as $location ) {
			if ( isset( $location[ $location_type ] ) && true === $location[ $location_type ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Init the $location_count property.
	 *
	 * @since 1.6.0
	 */
	public function init_location_count() {

		$this->location_count['all'] = count( $this->all );

		foreach ( $this->all as $archive ) {

			if ( empty( $archive['locations'] ) ) {
				continue;
			}

			foreach ( $this->core->archives_all->location_types as $location_type ) {
				if ( ! $this->has_location_type( $archive, $location_type ) ) {
					continue;
				}

				if ( empty( $this->location_count[ $location_type ] ) ) {
					$this->location_count[ $location_type ] = 0;
				}

				$this->location_count[ $location_type ]++;
			}
		}
	}

	/**
	 * Init and get a list of all backups (local and remote).
	 *
	 * @since 1.6.0
	 */
	public function init() {
		if ( $this->is_init ) {
			return;
		}

		$archives = $this->core->get_archive_list();

		foreach ( $archives as $archive ) {
			$this->all[] = array(
				'filename'      => $archive['filename'],
				'last_modified' => $archive['lastmodunix'],
				'size'          => $archive['filesize'],
				'locations'     => array(
					array(
						'title'         => $this->local_title,
						'on_web_server' => true,
					),
				),
			);

			$this->archives[ $archive['filename'] ]['on_web_server'] = true;
		}

		do_action( 'boldgrid_backup_get_all' );

		$this->init_location_count();

		$this->is_init = true;
	}
}
