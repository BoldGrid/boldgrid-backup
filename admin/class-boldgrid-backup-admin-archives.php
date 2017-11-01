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
	 * Get a table containing a list of all backups.
	 *
	 * This table is displayed on the Backup Archives page.
	 *
	 * @since 1.5.4
	 *
	 * @return string
	 */
	public function get_table() {
		$trs = array();
		$archives = $this->core->get_archive_list();

		if ( ! empty( $archives ) ) {
			foreach ( $archives as $key => $archive ) {
				$trs[$archive['lastmodunix']] = include dirname( __FILE__ ) . '/partials/archives/archive-tr.php';
			}
		}

		/**
		 * Allow remote storage providers to add their backups to the list.
		 *
		 * @since 1.5.4
		 *
		 * @param array $trs The current list of backups.
		 */
		$trs = apply_filters ( 'boldgrid_backup_archive_tr', $trs );

		// Show backups oldest to newest.
		ksort( $trs );

		$table = '
			<table class="wp-list-table widefat fixed striped pages">
				<tbody id="backup-archive-list-body">
		';

		foreach( $trs as $tr ) {
			$table .= $tr;
		}

		$table .= '</tbody></table>';

		$header = sprintf( '
			<p>%1$s (%2$s)</p>',
			/* 1 */ esc_html__( 'Archive Count', 'boldgrid-backup' ),
			/* 2 */ count( $trs )
		);

		$table = $header . $table;

		if( empty( $trs ) ) {
			$table = sprintf( '
				<p>%1$s</]>',
				esc_html__( 'There are no archives for this site in the backup directory.', 'boldgrid-backup' )
			);
		}

		return $table;
	}
}
