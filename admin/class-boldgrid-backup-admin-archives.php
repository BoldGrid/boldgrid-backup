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
	 * Get a "mine count" of backup files.
	 *
	 * Returns a string such as:
	 * All (5) | Local (4) | SFTP (2) | Amazon S3 (3)
	 *
	 * @since 1.5.4
	 *
	 * @return string
	 */
	public function get_mine_count() {
		$this->core->archives_all->init();

		// Create the "mine count" section above the table.
		$locations = array();
		foreach( $this->core->archives_all->location_count as $location => $count ) {

			// The first locaion, "All", should have the "current" class.
			$current = empty( $locations ) ? 'current' : '';

			$locations[] = sprintf('
				%3$s %1$s %4$s (%2$s)
				',
				/* 1 */ $location,
				/* 2 */ $count,
				/* 3 */ sprintf( '<a href="" class="mine %1$s">', $current ),
				/* 4 */ '</a>'
			);
		}

		$markup = '<p class="subsubsub">' . implode( ' | ', $locations ) . '</p>';

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
			__( 'Date', 'boldgrid-backup'),
			__( 'Size', 'boldgrid-backup')
		);

		foreach( $this->core->archives_all->all as $archive ) {

			// Create the list of locations.
			$locations = array();
			foreach( $archive['locations'] as $location ) {
				$locations[] = sprintf( '<span data-location="%1$s">%1$s</span>', $location );
			}
			$locations = implode( ', ', $locations );

			$table .= sprintf( '
				<tr>
					<td>
						<strong>%1$s</strong>: %2$s
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
				/* 2 */ date( 'M j, Y h:i a', $archive['last_modified'] ),
				/* 3 */ Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['size'] ),
				/* 4 */ $archive['filename'],
				/* 5 */ $view_details,
				/* 6 */ $locations
			);
		}
		$table .= '</tbody>
			</table>
		';

		if( empty( $this->core->archives_all->all ) ) {
			$table = sprintf( '
				<p>%1$s</p>',
				__( 'You currently do not have any backups.', 'boldgrid-backup' )
			);
		}

		return $table;
	}
}
