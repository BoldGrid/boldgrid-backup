<?php
/**
 * File: class-boldgrid-backup-admin-usage.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.12.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Usage
 *
 * @since 1.12.3
 */
class Boldgrid_Backup_Admin_Usage {
	/**
	 * Screen prefixes.
	 *
	 * @since 1.12.3
	 * @access private
	 * @var array
	 */
	private $prefixes = [
		// The Total Upkeep dashboard.
		'toplevel_page_boldgrid-backup',
		// Archive details page.
		'admin_page_boldgrid-backup',
		// All other Total Upkeep pages.
		'total-upkeep_page',
	];

	/**
	 * Admin init.
	 *
	 * @since 1.12.3
	 */
	public function admin_init() {
		/*
		 * Prompt the user to track usage.
		 *
		 * The Notice class will determine whether or not the notice should be displayed.
		 *
		 * The Notice class is instantiated, which adds appropriate hooks to load javascript, ajax
		 * listeners, etc.
		 */
		$notice = new \Boldgrid\Library\Library\Usage\Notice();

		/*
		 * Track usage.
		 *
		 * The usage class will determine whether or not usage should be collected.
		 *
		 * The Usage class is instantiated, which adds appropriate hooks to load javascript, ajax
		 * listeners, etc.
		 */
		$usage = new \Boldgrid\Library\Library\Usage();
	}

	/**
	 * Determine whether or not to show the notice to the user to accept usage tracking.
	 *
	 * @since 1.12.3
	 *
	 * @return bool
	 */
	public function maybe_show_notice( $show ) {
		if ( $this->has_screen_prefix() && ! get_option( 'boldgrid_backup_latest_backup' ) ) {
			/*
			 * Only show the notice if the user already has created a backup. If they haven't, they'll see
			 * admin notices telling them how to create their first backup, and we don't want this notice
			 * to derail the user from that task.
			 */
			$show = false;
		}

		return $show;
	}

	/**
	 * Filter the notice that is given to users.
	 *
	 * @since 1.12.3S
	 *
	 * @param  array $params
	 * @return array
	 */
	public function filter_notice( $params ) {
		if ( $this->has_screen_prefix() ) {
			$params['message'] = '<p>' .
				esc_html__(
					'Thank you for using Total Upkeep by BoldGrid! Would you be ok with helping us improve our products by sending anonymous usage data? Information collected will not be personal and is not used to identify or contact you.',
					'boldgrid-backup'
				) . '</p>';
		}

		return $params;
	}

	/**
	 * Filter prefixes.
	 *
	 * Tell the Usage class to listen to pages that begin with boldgrid-backup.
	 *
	 * @since 1.12.3
	 *
	 * @param  array $prefixes An array of page prefixes.
	 * @return array
	 */
	public function filter_prefixes( $prefixes ) {
		$prefixes = array_merge( $prefixes, $this->prefixes );

		return $prefixes;
	}

	/**
	 * Whether or not the current admin page begins with boldgrid-backup.
	 *
	 * @since 1.12.3
	 *
	 * @return bool
	 */
	public function has_screen_prefix() {
		return \Boldgrid\Library\Library\Usage\Helper::hasScreenPrefix( $this->prefixes );
	}
}
