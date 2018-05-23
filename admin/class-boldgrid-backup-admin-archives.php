<?php
/**
 * Archives class.
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
 * BoldGrid Backup Admin Archives Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Archives {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get the location type from a location.
	 *
	 * @since 1.6.0
	 *
	 * @param $location array {
	 *     A location.
	 *
	 *     @type string $title    Such as "Web Server".
	 *     @type bool   $location
	 * }
	 * @return mixed
	 */
	public function get_location_type( $location ) {
		foreach ( $this->core->archives_all->location_types as $type ) {
			if ( isset( $location[ $type ] ) && true === $location[ $type ] ) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * Get a location type's title.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $type
	 * @return string
	 */
	public function get_location_type_title( $type ) {
		if ( 'all' === $type ) {
			$title = $this->core->lang['All'];
		} elseif ( 'on_web_server' === $type ) {
			$title = $this->core->lang['Web_Server'];
		} else {
			$title = $this->core->lang['Remote'];
		}

		return $title;
	}

	/**
	 * Create the list of locations.
	 *
	 * This method returns a list of locations (html markup), which will be
	 * located under the backup, such as "Web Server, SFTP".
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_locations( $archive ) {
		$locations = array();

		foreach ( $archive['locations'] as $location ) {

			$location_type = $this->get_location_type( $location );

			$data_attr = sprintf( 'data-%1$s="true"', $location_type );

			$title_attr = ! empty( $location['title_attr'] ) ? sprintf( 'title="%1$s"', esc_attr( $location['title_attr'] ) ) : '';

			$locations[] = sprintf(
				'<span %2$s %3$s>%1$s</span>',
				esc_html( $location['title'] ),
				$data_attr,
				$title_attr
			);
		}

		$locations = implode( ', ', $locations );

		return $locations;
	}

	/**
	 * Get a "mine count" of backup files.
	 *
	 * Returns a string such as:
	 * All (5) | Web Server (4) | Remote (2)
	 *
	 * @since 1.5.4
	 *
	 * @return string
	 */
	public function get_mine_count() {
		$this->core->archives_all->init();

		// An array of locations, each array item simliar to: <a>All<a/> (5)
		$locations = array();

		foreach ( $this->core->archives_all->location_count as $location => $count ) {

			// The first locaion, "All", should have the "current" class.
			$current = 'all' === $location ? 'current' : '';

			$title = $this->get_location_type_title( $location );

			$locations[] = sprintf('
				%3$s %1$s %4$s (%2$s)
				',
				/* 1 */ $title,
				/* 2 */ $count,
				/* 3 */ sprintf( '<a href="" class="mine %1$s" data-count-type="%2$s">', $current, $location ),
				/* 4 */ '</a>'
			);
		}

		// The last location, not really a "location", is the help icon.
		$locations[] = '<span class="dashicons dashicons-editor-help" data-id="mine-count"></span>';

		$markup = '<p class="subsubsub">' . implode( ' | ', $locations ) . '</p>';

		// Create help text to go along with help icon.
		$markup .= sprintf('
			<p class="help" data-id="mine-count">
				%1$s
			</p>',
			__( 'This list shows on which computers your backup archives are being stored. They can be saved to more than one location. Please <a href="admin.php?page=boldgrid-backup-tools&section=section_locations">click here</a> for more information on what <strong>Web Server</strong> and other terms mean.', 'boldgrid-backup' )
		);

		return $markup;
	}

	/**
	 * Get a table containing a list of all backups.
	 *
	 * This table is displayed on the Backup Archives page.
	 *
	 * @since 1.5.4
	 *
	 * @return string
	 */
	public function get_table() {
		$this->core->archives_all->init();
		$backup = __( 'Backup', 'boldgrid-backup' );
		$view_details = __( 'View details', 'boldgrid-backup' );

		$table = $this->get_mine_count();

		$table .= sprintf( '
			<table class="wp-list-table widefat fixed striped pages">
				<thead>
					<td>%1$s</td>
					<td>%2$s</td>
					<td></td>
				<tbody id="backup-archive-list-body">',
			__( 'Date', 'boldgrid-backup' ),
			__( 'Size', 'boldgrid-backup' )
		);

		foreach ( $this->core->archives_all->all as $archive ) {
			$locations = $this->get_locations( $archive );

			// dirlist -> lastmodunix -> mtime (last_modified in unix time).
			$this->core->time->init( $archive['last_modified'], 'utc' );

			$table .= sprintf( '
				<tr>
					<td>
						%2$s
						<p class="description">%6$s</p>
					</td>
					<td>
						%3$s
					</td>
					<td>
						<a
							class="button"
							href="admin.php?page=boldgrid-backup-archive-details&filename=%4$s"
						>%5$s</a>
					</td>
				</tr>
				',
				/* 1 */ $backup,
				/* 2 */ $this->core->time->get_span(),
				/* 3 */ Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['size'] ),
				/* 4 */ $archive['filename'],
				/* 5 */ $view_details,
				/* 6 */ $locations
			);
		}
		$table .= '</tbody>
			</table>
		';

		if ( empty( $this->core->archives_all->all ) ) {
			$table = sprintf( '
				<p>%1$s</p>',
				__( 'You currently do not have any backups.', 'boldgrid-backup' )
			);
		}

		return $table;
	}
}
