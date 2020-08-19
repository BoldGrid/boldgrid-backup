<?php
/**
 * File: class-boldgrid-backup-activator.php
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Activator
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Activator {
	/**
	 * The name of the option that signifies this plugin was just activated.
	 *
	 * The option is meant to be read immediately following plugin activation, since that is when
	 * this plugin can actually take action.
	 *
	 * @since 1.10.1
	 * @var string
	 */
	public static $option = 'boldgrid_backup_activate';

	/**
	 * Whether or not the plugin was just activated.
	 *
	 * This property is meant to track if we're within the process of activating the plugin right
	 * this second, as in we're within the "register_activation_hook". It's a little different than
	 * our static $option value.
	 *
	 * @since 1.10.1
	 * @var bool
	 */
	public static $just_activated = false;

	/**
	 * Plugin activation.
	 *
	 * This method is ran via register_activation_hook.
	 *
	 * @since 1.0
	 *
	 * @static
	 *
	 * @see Boldgrid_Backup_Admin_Core()
	 * @see Boldgrid_Backup_Admin_Settings::get_settings()
	 * @see Boldgrid_Backup_Admin_Cron::add_all_crons()
	 */
	public static function activate() {
		// Flag that the plugin has just been activated.
		update_option( self::$option, 1 );
		self::$just_activated = true;

		if ( Boldgrid_Backup_Admin_Test::is_filesystem_supported() ) {
			$core      = new Boldgrid_Backup_Admin_Core();
			$settings  = $core->settings->get_settings();
			$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;

			/*
			 * Add all previous crons.
			 *
			 * The add_all_crons methods called include proper checks to ensure
			 * scheduler is available and $settings include a schedule.
			 */
			if ( 'cron' === $scheduler ) {
				$core->cron->add_all_crons( $settings );
			} elseif ( 'wp-cron' === $scheduler ) {
				$core->wp_cron->add_all_crons( $settings );
			}
		}
	}

	/**
	 * Determine whether or not we just activated the plugin.
	 *
	 * For example, this should return true when on wp-admin/plugins.php and it says "Plugin activated".
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public static function on_post_activate() {
		$just_activated = ( ! self::$just_activated && '1' === get_option( self::$option ) );

		/*
		* On Plugin activation, we need to read the auto_update_plugins.
		* and auto_update_themes options, and sync our settings to those.
		* This ensures that when the plugin is not active, any changes made to those settings.
		* is reflected accurately in Total Upkeep.
		*/
		self::maybe_sync_options( $just_activated );

		return $just_activated;
	}

	/**
	 * Maybe Sync Options.
	 *
	 * With WP5.5, we need to check the auto_update_options table and make
	 * sure that any changes made wheile this plugin is not active
	 * are updated in our settings upon activation.
	 *
	 * @since 1.14.3
	 *
	 * @global string $wp_version Current version of WordPress
	 *
	 * @param bool $just_activated Whether or not the plugin has just now activated.
	 */
	public static function maybe_sync_options( $just_activated ) {
		global $wp_version;

		$updates = array( 'themes', 'plugins' );

		foreach ( $updates as $type ) {
			$auto_update_type = get_option( 'auto_update_' . $type, false );
			/*
				* If the user still uses WP < 5.5 and not activated any auto-updates,
				* then the auto_update_$type option will not be set yet
				* and this can therefore be ignored. However if it has been set, and all updates are disabled,
				* then this option will return an empty array.
				*/
			if ( ! $just_activated || version_compare( '5.4.99', $wp_version, 'gt' ) || false === $auto_update_type ) {
				continue;
			}
			$core = apply_filters( 'boldgrid_backup_get_core', null );
			$core->auto_updates->wordpress_option_updated( array(), $auto_update_type, 'auto_update_' . $type );
		}
	}

	/**
	 * Display admin notices immediately after activating the plugin.
	 *
	 * @since 1.10.1
	 */
	public function post_activate_notice() {
		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : null; // phpcs:ignore

		$on_archives_page = 'boldgrid-backup' === $page;

		/*
		 * Show the activation notice if just activated.
		 *
		 * Some serivces, such as CloudWP, may redirect users to the backups page immediately after
		 * activation. If you have no backups, that page will show a message similar to the message
		 * we're going to show. In that case, if $on_archives_page, don't show this message, otherwise
		 * the user will see the same info in two different notices.
		 */
		if ( $this->on_post_activate() && ! $on_archives_page ) {
			$notice = '<div class="notice notice-success">
				<h2>' .
				sprintf(
					// translators: 1: Plugin title.
					esc_html__( 'Thank you for installing %1$s!', 'boldgrid-backup' ),
					BOLDGRID_BACKUP_TITLE
				) . '</h2>
				<p>';

			$notice .= wp_kses(
				sprintf(
					// translators: 1 An opening strong tag, 2 its closing strong tag.
					esc_html__( 'Creating your first backup is easy! Simply go to your %1$sBackup Archives%2$s page and click %1$sBackup Site Now%2$s.', 'boldgrid-backup' ),
					'<strong>',
					'</strong>'
				),
				[ 'strong' => [] ]
			);

			$notice .= '</p>
				<p><a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup' ) ) . '" class="button button-primary">' . esc_html__( 'Create your first Backup now!', 'boldgrid-backup' ) . '</a></p>
			</div>';

			/**
			 * Allow our activation notice to be filtered.
			 *
			 * It could be changed, or, if it's not wanted to be shown at all, set to ''.
			 *
			 * @since 1.12.0
			 *
			 * @param string $notice HTML markup of the notice.
			 */
			$notice = apply_filters( 'boldgrid_backup_post_activate_notice', $notice );

			echo wp_kses(
				$notice,
				[
					'div'    => [
						'class' => [],
					],
					'h2'     => [
						'class' => [],
					],
					'p'      => [
						'class' => [],
					],
					'a'      => [
						'href'  => [],
						'class' => [],
					],
					'strong' => [],
				]
			);
		}
	}

	/**
	 * Shutdown action.
	 *
	 * @since 1.10.1
	 */
	public function shutdown() {
		// Delete the option that signifies we just activated.
		if ( ! self::$just_activated ) {
			delete_option( self::$option );
		}
	}
}
