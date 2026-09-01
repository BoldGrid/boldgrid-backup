<?php
/**
 * File: class-boldgrid-backup-admin-archives.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archives
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Archives {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

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
	 * Get the location type from a location.
	 *
	 * @since 1.6.0
	 *
	 * @param array $location {
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
	 * @param  string $type Location type.
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
	 * @param  array $archive Archive information.
	 * @return string
	 */
	public function get_locations( $archive ) {
		$locations = array();

		foreach ( $archive['locations'] as $location ) {

			$location_type = $this->get_location_type( $location );

			$data_attr = sprintf( 'data-%1$s="true"', $location_type );

			$title_attr = ! empty( $location['title_attr'] ) ? sprintf( 'title="%1$s"', esc_attr( $location['title_attr'] ) ) : '';

			/*
			 * As of 1.7.0, the user can flag an archive as protected (exluded from retention
			 * process). Show a padlock next to those backups.
			 */
			$icon = '';
			if ( 'on_web_server' === $location_type && '1' === $this->core->archive->get_attribute( 'protect' ) ) {
				$icon = '<span class="dashicons dashicons-lock" title="' . esc_attr__( 'This backup will not be deleted automatically from your Web Server due to your retention settings.', 'boldgrid-backup' ) . '"></span>';
			}

			$locations[] = sprintf(
				'<span %2$s %3$s>%1$s%4$s</span>',
				esc_html( $location['title'] ),
				$data_attr,
				$title_attr,
				$icon
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
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_mine_count() {
		$this->core->archives_all->init();

		// An array of locations, each array item simliar to: <a>All<a/> (5).
		$locations = array();

		foreach ( $this->core->archives_all->location_count as $location => $count ) {

			// The first locaion, "All", should have the "current" class.
			$current = 'all' === $location ? 'current' : '';

			$title = $this->get_location_type_title( $location );

			$locations[] = sprintf(
				'
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
		$markup .= sprintf(
			'
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
	 * @since 1.6.0
	 *
	 * @param  array $options {
	 *     Display options.
	 *
	 *     @type bool $show_link_button Display the "Get Download Link" button. Optional.
	 *     @type bool $transfers_mode   Alters messages for the transfers pages. Optional.
	 * }
	 * @return string
	 */
	public function get_table( array $options = [] ) {
		$this->core->archives_all->init();
		$backup       = __( 'Backup', 'boldgrid-backup' );
		$view_details = __( 'View details', 'boldgrid-backup' );

		$table = '';

		/*
		 * If a user has backups, either locally or remote, create a $table showing a list of all their
		 * backups.
		 */
		if ( ! empty( $this->core->archives_all->all ) ) {
			// If showing a "Get Download Link" button, we need a container to show the results.
			$table = (
				! empty( $options['show_link_button'] ) ?
				'<div id="download-link-copy" class="notice notice-info inline"></div>' : ''
			);

			$table .= $this->get_mine_count();

			$table .= sprintf(
				'
				<table class="wp-list-table widefat fixed striped pages">
					<thead>
						<td>%1$s</td>
						<td>%2$s</td>
						<td></td>' .
						( ! empty( $options['show_link_button'] ) ? '<td></td>' : '' ) . '
					<tbody id="backup-archive-list-body">',
				__( 'Date', 'boldgrid-backup' ),
				__( 'Size', 'boldgrid-backup' )
			);

			foreach ( $this->core->archives_all->all as $archive ) {
				$this->core->time->init( $archive['last_modified'], 'utc' );

				// Get the title of the backup.
				$filepath = $this->core->backup_dir->get_path_to( $archive['filename'] );
				$this->core->archive->init( $filepath );
				$title = $this->core->archive->get_attribute( 'title' );

				$locations = $this->get_locations( $archive );

				$db_encrypted = $this->core->archive->get_attribute( 'encrypt_db' ) ?
					'<span class="bgbkup-db-encrypted"></span>' : '';

				$table .= sprintf(
					'
					<tr>
						<td>
							%2$s
							%7$s<br />
							<p class="description">%6$s</p>
						</td>
						<td>
							%3$s%8$s
						</td>
						<td>
							<a
								class="button"
								href="admin.php?page=boldgrid-backup-archive-details&filename=%4$s"
							>%5$s</a>
						</td>
						' . (
							// Show a "Get Download Link" button.
							! empty( $options['show_link_button'] ) ?
							'<td>' . $this->core->archive_actions->get_download_link_button( $archive['filename'] ) . '</td>' : ''
					) . '</tr>',
					/* 1 */ $backup,
					/* 2 */ empty( $title ) ? '' : '<strong>' . esc_html( $title ) . '</strong><br />',
					/* 3 */ Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['size'] ),
					/* 4 */ $archive['filename'],
					/* 5 */ $view_details,
					/* 6 */ $locations,
					/* 7 */ $this->core->time->get_span(),
					/* 8 */ $db_encrypted
				);
			}

			$table .= '</tbody>
				</table>
			';
		}

		/*
		 * Create message for users who have no backups.
		 *
		 * If a user has no backups, instead of saying, "Hey, you have no backups", we should inform
		 * the user (1) how they can create their first backup and (2) how they can schedule backups.
		 *
		 * In edition to checking whether or not the user has any backups, make sure they're not currently
		 * backing up their site. We don't want to tell the user to create their first backup if there
		 * is a backup currently in progress.
		 */
		if ( ! $this->core->in_progress->get() && empty( $this->core->archives_all->all ) ) {
			$table = '
			<div class="notice notice-warning inline" style="margin:15px 0">
				<p>
					<strong>
					' . esc_html__( 'It looks like you don\'t have any backups! That\'s ok, let\'s fix that now. Here\'s what we recommend you do:', 'boldgrid-backup' ) . '
					</strong>
				</p>
				<ol>
					<li>';

			if ( ! empty( $options['transfers_mode'] ) ) {
				$table .= wp_kses(
					sprintf(
						// translators: 1 an opening strong tag, 2 its closing strong tag.
						__( 'Go to the %1$sBackups%2$s page to create a backup of your site, and then come back here for further instruction.', 'boldgrid-backup' ),
						'<em>',
						'</em>'
					),
					[ 'em' => [] ]
				);
			} else {
				$table .= wp_kses(
					sprintf(
						// translators: 1 an opening strong tag, 2 its closing strong tag.
						__( 'Create a backup of your site right now by clicking the %1$sBackup Site Now%2$s button at the top of the page.', 'boldgrid-backup' ),
						'<strong>',
						'</strong>'
					),
					array( 'strong' => array() )
				);
			}

			$table .= '</li>';

			if ( empty( $options['transfers_mode'] ) ) {
				$table .= '<li>';
				$table .= wp_kses(
					sprintf(
						// translators: 1 the opening anchor tag linking to the settings page, 2 its closing anchor tag.
						__( 'After the backup is created, go to your %1$ssettings%2$s page and setup backups so they\'re create automatically on a set schedule.', 'boldgrid-backup' ),
						'<a href="' . $this->core->settings->get_settings_url() . '">',
						'</a>'
					),
					array( 'a' => array( 'href' => array() ) )
				);
				$table .= '</li>';
			}

			$table .= '
				</ol>
			</div>';
		}

		return $table;
	}
}
