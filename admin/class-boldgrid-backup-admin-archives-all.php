<?php
/**
 * Archives All class.
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
 * BoldGrid Backup Admin Archives All Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Archives_All {

	/**
	 * An array of all archives.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $all = array();

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Whether or not we have initialized all backups.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    bool
	 */
	public $is_init = false;

	/**
	 * Local server title, such as "Web server".
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $local_title;

	/**
	 * An array of data about remote locations and how many backups at each.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $location_count = array();

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->local_title = __( 'Web Server', 'BoldGrid Backup' );
	}

	/**
	 * Add a backup to the list of all backups.
	 *
	 * @since 1.5.4
	 *
	 * @param array $backup
	 */
	public function add( $backup ) {
		$in_all = false;
		foreach( $this->all as $k => $v ) {
			if( $backup['filename'] === $v['filename'] ) {
				$in_all = true;
				$this->all[$k]['locations'][] = $backup['location'];
				break;
			}
		}

		if( ! $in_all ) {
			$this->all[] = $backup;
		}
	}

	/**
	 * Init the $location_count property.
	 *
	 * @since 1.5.4
	 */
	public function init_location_count() {
		$this->location_count['All'] = count( $this->all );

		foreach( $this->all as $backup ) {

			if( empty( $backup['locations'] ) ) {
				continue;
			}

			foreach( $backup['locations'] as $location ) {
				if( empty( $this->location_count[$location] ) ) {
					$this->location_count[$location] = 0;
				}

				$this->location_count[$location]++;
			}
		}
	}

	/**
	 * Init and get a list of all backups (local and remote).
	 *
	 * @since 1.5.4
	 */
	public function init() {
		if( $this->is_init ) {
			return;
		}

		$archives = $this->core->get_archive_list();

		foreach( $archives as $archive ) {
			$this->all[] = array(
				'filename' => $archive['filename'],
				'last_modified' => $archive['lastmodunix'],
				'size' => $archive['filesize'],
				'locations' => array(
					$this->local_title,
				),
			);
		}

		do_action( 'boldgrid_backup_get_all' );

		$this->init_location_count();

		$this->is_init = true;
	}
}
