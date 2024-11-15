<?php
/**
 * File: class-boldgrid-backup-admin-settings.php
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

use \Boldgrid\Library\Library\Notice;

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Settings
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Settings {
	/**
	 * The core class object.
	 *
	 * @since 1.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Whether or not we're in the middle of saving settings from $_POST.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    bool
	 */
	public $is_saving_settings = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		$this->core = $core;

		$this->is_saving_settings = isset( $_POST['save_time'] ); // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
	}

	/**
	 * How many days of the week are being saved?
	 *
	 * This method counts the number of $_POST keys that begin with "dow_". The count returned by
	 * this method is used to help enforce restrictions on the free version of the plugin.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_dow_count() {
		$count = 0;

		if ( ! isset( $_POST ) || ! is_array( $_POST ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			return 0;
		}

		// Loop through each $_POST value and check if the key begins with dow_.
		foreach ( $_POST as $k => $v ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			if ( substr( $k, 0, 4 ) === 'dow_' ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get a setting from the settings.
	 *
	 * @since 1.9.0
	 *
	 * @param  string $key The setting name.
	 * @return mixed
	 */
	public function get_setting( $key ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Get settings using defaults.
	 *
	 * @since 1.0
	 *
	 * @param  bool $raw Return the raw settings. This would happen very early
	 *                   on in this method, before all of the other checks
	 *                   happen.
	 * @return array     An array of settings.
	 */
	public function get_settings( $raw = false ) {
		// Get settings.
		$settings = get_site_option( 'boldgrid_backup_settings', [] );

		if ( $raw ) {
			return $settings;
		}

		// Configure a random minute. 5:4am will fail, but 5:04am will pass.
		$random_minute = mt_rand( 1, 59 );
		$random_minute = 1 === strlen( $random_minute ) ? '0' . $random_minute : $random_minute;

		// Parse settings.
		if ( ! empty( $settings['schedule'] ) ) {
			// Update schedule format.
			// Days of the week.
			$settings['schedule']['dow_sunday']    = (
				! empty( $settings['schedule']['dow_sunday'] ) ? 1 : 0
			);
			$settings['schedule']['dow_monday']    = (
				! empty( $settings['schedule']['dow_monday'] ) ? 1 : 0
			);
			$settings['schedule']['dow_tuesday']   = (
				! empty( $settings['schedule']['dow_tuesday'] ) ? 1 : 0
			);
			$settings['schedule']['dow_wednesday'] = (
				! empty( $settings['schedule']['dow_wednesday'] ) ? 1 : 0
			);
			$settings['schedule']['dow_thursday']  = (
				! empty( $settings['schedule']['dow_thursday'] ) ? 1 : 0
			);
			$settings['schedule']['dow_friday']    = (
				! empty( $settings['schedule']['dow_friday'] ) ? 1 : 0
			);
			$settings['schedule']['dow_saturday']  = (
				! empty( $settings['schedule']['dow_saturday'] ) ? 1 : 0
			);

			// Time of day.
			$settings['schedule']['tod_h'] = (
				! empty( $settings['schedule']['tod_h'] ) ?
				$settings['schedule']['tod_h'] : mt_rand( 1, 5 )
			);
			$settings['schedule']['tod_m'] = (
				! empty( $settings['schedule']['tod_m'] ) ?
				$settings['schedule']['tod_m'] : $random_minute
			);
			$settings['schedule']['tod_a'] = (
				! empty( $settings['schedule']['tod_a'] ) ?
				$settings['schedule']['tod_a'] : 'AM'
			);

			// Notification settings.
			$settings['notifications']['backup']  = (
				! isset( $settings['notifications']['backup'] ) ||
				! empty( $settings['notifications']['backup'] ) ? 1 : 0
			);
			$settings['notifications']['restore'] = (
				! isset( $settings['notifications']['restore'] ) ||
				! empty( $settings['notifications']['restore'] ) ? 1 : 0
			);

			// Notification email address.
			if ( empty( $settings['notification_email'] ) ) {
				$settings['notification_email'] = $this->core->config->get_admin_email();
			}

			// Other settings.
			$settings['auto_backup'] = (
				! isset( $settings['auto_backup'] ) || ! empty( $settings['auto_backup'] ) ? 1 : 0
			);

			$settings['auto_rollback'] = (
				! isset( $settings['auto_rollback'] ) || ! empty( $settings['auto_rollback'] ) ?
				1 : 0
			);

			// Get retention count setting.  Limit 1-99, default is from config.
			$settings['retention_count'] = ( isset( $settings['retention_count'] ) &&
				99 >= $settings['retention_count'] ) ?
				$settings['retention_count'] : $this->core->config->get_default_retention();

			if ( $settings['retention_count'] > 99 ) {
				$settings['retention_count'] = 99;
			}
		} else {
			// Define defaults.
			// Days of the week.
			$settings['schedule']['dow_sunday']    = 0;
			$settings['schedule']['dow_monday']    = 0;
			$settings['schedule']['dow_tuesday']   = 0;
			$settings['schedule']['dow_wednesday'] = 0;
			$settings['schedule']['dow_thursday']  = 0;
			$settings['schedule']['dow_friday']    = 0;
			$settings['schedule']['dow_saturday']  = 0;

			// Time of day.
			$settings['schedule']['tod_h'] = mt_rand( 1, 5 );
			$settings['schedule']['tod_m'] = $random_minute;
			$settings['schedule']['tod_a'] = 'AM';

			// Other settings.
			$settings['retention_count']          = $this->core->config->get_default_retention();
			$settings['notification_email']       = $this->core->config->get_admin_email();
			$settings['notifications']['backup']  = 1;
			$settings['notifications']['restore'] = 1;
			$settings['auto_backup']              = 1;
			$settings['auto_rollback']            = 1;

			$settings['remote']['local']['enabled'] = true;
		}

		if ( empty( $settings['remote'] ) ) {
			$settings['remote'] = array();
		}

		// For consistency, untrailingslashit the backup dir.
		if ( isset( $settings['backup_directory'] ) ) {
			$settings['backup_directory'] = untrailingslashit( $settings['backup_directory'] );
		}

		if ( empty( $settings['exclude_tables'] ) ) {
			$settings['exclude_tables'] = array();
		}

		// Configure default folder_exclusion settings.
		$settings['folder_exclusion_include'] = $this->core->folder_exclusion->from_settings( 'include', $settings );
		$settings['folder_exclusion_exclude'] = $this->core->folder_exclusion->from_settings( 'exclude', $settings );

		// Site Check settings.
		$settings['site_check']['enabled'] = isset( $settings['site_check']['enabled'] ) ?
			(bool) $settings['site_check']['enabled'] : true;

		$settings['site_check']['logger'] = isset( $settings['site_check']['logger'] ) ?
			(bool) $settings['site_check']['logger'] : true;

		$settings['site_check']['auto_recovery'] = isset( $settings['site_check']['auto_recovery'] ) ?
			(bool) $settings['site_check']['auto_recovery'] : false;

		$settings['notifications']['site_check'] = isset( $settings['notifications']['site_check'] ) ?
			(bool) $settings['notifications']['site_check'] : true;

		// Site Check interval (in minutes); 5-59, defaults to 15.
		$settings['site_check']['interval'] = ( isset( $settings['site_check']['interval'] ) &&
			4 < $settings['site_check']['interval'] && 60 > $settings['site_check']['interval'] ) ?
			$settings['site_check']['interval'] : 15;

		// Encryption.
		$settings['encrypt_db'] = isset( $settings['encrypt_db'] ) ? (bool) $settings['encrypt_db'] : false;

		// Auto Updates.
		$settings['auto_update'] = $this->set_update_settings( $settings );

		// Return the settings array.
		return $settings;
	}

	/**
	 * Get the Auto Update Settings.
	 *
	 * With the new changes of wp5.5, there are new changes to the auto update settings.
	 * Moving this to it's own method allows us to isolate that logic from the get_settings() method
	 * and ensure that the Auto Update Settings retrieved are accurate.
	 *
	 * @since 1.14.3
	 *
	 * @param  array  $settings The Settings array.
	 * @global string $wp_version The current version of WordPress.
	 *
	 * @return array
	 */
	public function set_update_settings( $settings ) {
		global $wp_version;

		// If the 'auto_update' settings are not set, add default values, otherwise use values from $settings array..
		if ( empty( $settings['auto_update'] ) ) {
			// The value for 'default' was changed from false, to '0' for data type consistency.
			$auto_update_settings = array(
				'days'    => 0,
				'plugins' => array(
					'default' => '0',
				),
				'themes'  => array(
					'default' => '0',
				),
			);
		} else {
			$auto_update_settings = $settings['auto_update'];
		}

		return $auto_update_settings;
	}

	/**
	 * Updates the WordPress auto_update options.
	 *
	 * This is somewhat the opposite of set_update_settings. This is used as necessary
	 * to update the new options added to WP5.5, auto_update_plugins and auto_update_themes
	 * to make sure that all the settings play nicely together. Anytime we change the update settings
	 * in Total Upkeep, This should run once for themes, and once for plugins to make sure that
	 * the WordPress Options are kept up to date.
	 *
	 * @since 1.4.3
	 * @global string $wp_version WordPress Version Number.
	 *
	 * @param array $new_settings New settings.
	 * @param bool  $is_theme     Whether or not the settings being updated are for a theme.
	 */
	public function update_autoupdate_options( $new_settings, $is_theme = false ) {
		global $wp_version;
		// If version is wp_version is greater than 5.4.99 then it must be 5.5.
		if ( version_compare( $wp_version, '5.4.99', 'gt' ) ) {
			// Depending on whether this is being run for themes or plugins, get the correct option table.
			$auto_update_field = get_option( $is_theme ? 'auto_update_themes' : 'auto_update_plugins', array() );
			// For each offer ( theme or plugin ) passed to this function.
			foreach ( $new_settings[ $is_theme ? 'themes' : 'plugins' ] as $offer => $enabled ) {
				$offer_in_option = array_search( $offer, $auto_update_field, true );
				if ( '1' === $enabled && false === $offer_in_option ) {
					/*
					* If auto updates for the plugin / theme are enabled in our settings, but not enabled in
					* wp option, enable it in the wp option.
					*/
					$auto_update_field[] = $offer;
				} elseif ( '0' === $enabled && false !== $offer_in_option ) {
					/*
					 * If auto updates for the plugin / theme are disabled in our settings, but not disabled in
					 * the wp option, disable it in the wp option.
					 */
					unset( $auto_update_field[ $offer_in_option ] );
				}
			}
			// Update the WordPress option with the settings passed to this function.
			update_option( $is_theme ? 'auto_update_themes' : 'auto_update_plugins', $auto_update_field );
		}
	}

	/**
	 * Determine whether or not the user has full site protection.
	 *
	 * Generally, this means they have scheduled backups that upload backups to a remote server.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function has_full_protection() {
		return $this->has_scheduled_backups() && $this->has_remote_configured();
	}

	/**
	 * Determine whether or not the user has any remote storage options enabled.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function has_remote_configured() {
		$remotes = $this->get_setting( 'remote', array() );

		$has_remote = false;

		foreach ( $remotes as $id => $config ) {
			if ( 'local' === $id ) {
				continue;
			}

			if ( ! empty( $config['enabled'] ) ) {
				$has_remote = true;
			}
		}

		return $has_remote;
	}

	/**
	 * Whether or not the user has backups scheduled.
	 *
	 * This method is not exhaustive, and insteads returns true if the user has any days of the week
	 * selected.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function has_scheduled_backups() {
		$settings = $this->get_settings();

		$schedule = empty( $settings['schedule'] ) ? [] : $settings['schedule'];

		$days_scheduled = 0;

		foreach ( $schedule as $key => $value ) {
			if ( 'dow_' !== substr( $key, 0, 4 ) ) {
				continue;
			}

			$days_scheduled += empty( $value ) ? 0 : 1;
		}

		return ! empty( $days_scheduled );
	}

	/**
	 * Get the url to the settings pages.
	 *
	 * @since 1.10.1
	 *
	 * @param  string $section If passed, $section will be appended to the url so it loads that
	 *                         specific section first.
	 * @return string
	 */
	public function get_settings_url( $section = '' ) {
		$url = admin_url( 'admin.php?page=boldgrid-backup-settings' );

		if ( ! empty( $section ) ) {
			$url .= '&section=' . $section;
		}

		return $url;
	}

	/**
	 * Whether or not we are backing up all files, as defined in the settings.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_all_files() {
		$is_all = true;

		$folder_exclusion_exclude = $this->get_setting( 'folder_exclusion_exclude' );
		$folder_exclusion_include = $this->get_setting( 'folder_exclusion_include' );

		if ( $this->core->folder_exclusion->default_include !== $folder_exclusion_include ) {
			$is_all = false;
		}

		if ( $this->core->folder_exclusion->default_exclude !== $folder_exclusion_exclude ) {
			$is_all = false;
		}

		/**
		 * Filter whether or not the backup is considered backing up all files.
		 *
		 * For example, an advanced user may have custom include / exclude settings, and wants this
		 * to be considered a backup of all their files.
		 *
		 * @since 1.9.0
		 *
		 * @param bool $is_all The current value of $is_all.
		 */
		apply_filters( 'boldgrid_backup_settings_is_all_files', $is_all );

		return $is_all;
	}

	/**
	 * Whether or not we are backing up all tables, as defined in the settings.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_all_tables() {
		$exclude_tables = $this->get_setting( 'exclude_tables' );

		return empty( $exclude_tables );
	}

	/**
	 * Move backups from one directory to another.
	 *
	 * @since 1.3.2
	 *
	 * @param string $old_dir Source directory.
	 * @param string $new_dir Destination directory.
	 * @return bool TRUE on success / no backups needed to be moved.
	 */
	public function move_backups( $old_dir, $new_dir ) {
		$fail_count = 0;

		$old_dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $old_dir );
		$new_dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $new_dir );

		$archives = $this->core->get_archive_list( null, $old_dir );

		ignore_user_abort( true );

		// Loop through each archive and move it.
		foreach ( $archives as $archive ) {
			$source      = $archive['filepath'];
			$destination = $new_dir . $archive['filename'];

			$success = @$this->core->wp_filesystem->move( $source, $destination ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

			if ( ! $success ) {
				$fail_count++;
			}
		}

		return 0 === $fail_count;
	}

	/**
	 * Update settings.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Cron::add_cron_entry()
	 * @see Boldgrid_Backup_Admin_Cron::get_cron_secret()
	 *
	 * @return bool Update success.
	 */
	private function update_settings() {
		$update_errors = array();

		// Check security nonce and referer.
		if ( ! check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Security violation! Please try again.', 'boldgrid-backup' ),
				)
			);
		}

		// Check for settings update.
		if ( ! empty( $_POST['save_time'] ) ) {
			// Get settings.
			$settings = $this->get_settings();

			// Initialize $update_error.
			$update_error = false;

			// Initialize $days_scheduled.
			$days_scheduled = array();

			// Validate input for schedule.
			$indices = array(
				'dow_sunday',
				'dow_monday',
				'dow_tuesday',
				'dow_wednesday',
				'dow_thursday',
				'dow_friday',
				'dow_saturday',
				'tod_h',
				'tod_m',
				'tod_a',
			);

			foreach ( $indices as $index ) {
				// Determine input type.
				if ( 0 === strpos( $index, 'dow_' ) ) {
					$type = 'day';
				} elseif ( 'tod_h' === $index ) {
					$type = 'h';
				} elseif ( 'tod_m' === $index ) {
					$type = 'm';
				} elseif ( 'tod_a' === $index ) {
					$type = 'a';
				} else {
					// Unknown type.
					$type = '?';
				}

				if ( ! empty( $_POST[ $index ] ) ) {
					// Validate by type.
					switch ( $type ) {
						case 'day':
							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// If day was scheduled, then track it.
							if ( 1 === $_POST[ $index ] ) {
								$days_scheduled[] = date( 'w', strtotime( str_replace( 'dow_', '', $index ) ) );
							}

							break;
						case 'h':
							if ( $_POST[ $index ] < 1 || $_POST[ $index ] > 12 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							break;
						case 'm':
							if ( $_POST[ $index ] < 0 || $_POST[ $index ] > 59 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// Pad left with 0.
							$_POST[ $index ] = str_pad( $_POST[ $index ], 2, '0', STR_PAD_LEFT );

							break;
						case 'a':
							if ( 'AM' !== $_POST[ $index ] && 'PM' !== $_POST[ $index ] ) {
								// Error in input; unknown type.
								$update_error = true;
								break 2;
							}

							break;
						default:
							// Error in input; unknown type.
							$update_error = true;
							break 2;
					}

					// Update the setting value provided.
					$settings['schedule'][ $index ] = $_POST[ $index ];
				} elseif ( 'day' === $type ) {
					// Unassigned days.
					$settings['schedule'][ $index ] = 0;
				} else {
					// Error in input.
					$update_error = true;

					break;
				}
			}

			// Validate input for other settings.
			$settings['notifications']['backup'] = isset( $_POST['notify_backup'] ) &&
				'1' === $_POST['notify_backup'] ? 1 : 0;

			$settings['notifications']['restore'] = isset( $_POST['notify_restore'] ) &&
				'1' === $_POST['notify_restore'] ? 1 : 0;

			$settings['notifications']['site_check'] = isset( $_POST['notify_site_check'] ) &&
				'1' === $_POST['notify_site_check'];

			$settings['auto_backup'] = ! isset( $_POST['auto_backup'] ) ||
				'1' === $_POST['auto_backup'] ? 1 : 0;

			$settings['auto_rollback'] = ! isset( $_POST['auto_rollback'] ) ||
				'1' === $_POST['auto_rollback'] ? 1 : 0;

			$settings['site_check']['enabled'] = isset( $_POST['site_check'] ) &&
				'1' === $_POST['site_check'];

			$settings['site_check']['interval'] = isset( $_POST['site_check_interval'] ) &&
				4 < $_POST['site_check_interval'] && 60 > $_POST['site_check_interval'] ?
				(int) $_POST['site_check_interval'] : 15;

			$settings['site_check']['logger'] = isset( $_POST['site_check_logger'] ) &&
				'1' === $_POST['site_check_logger'];

			$settings['site_check']['auto_recovery'] = isset( $_POST['auto_recovery'] ) &&
				'1' === $_POST['auto_recovery'];

			// Update notification email address, if changed.
			if ( isset( $settings['notification_email'] ) &&
				sanitize_email( $_POST['notification_email'] ) !== $settings['notification_email'] ) {
					$settings['notification_email'] = sanitize_email( $_POST['notification_email'] );
			}

			// Database encryption.
			$settings['encrypt_db'] = isset( $_POST['encrypt_db'] ) && '1' === $_POST['encrypt_db'];

			/*
			 * Save compressor settings.
			 *
			 * @since 1.5.1
			 */
			if ( ! empty( $_POST['compressor'] ) ) {
				$available_compressors = $this->core->compressors->get_available();
				$selected_compressor   = $_POST['compressor'];
				if ( in_array( $selected_compressor, $available_compressors, true ) ) {
					$settings['compressor'] = $selected_compressor;
				} else {
					$update_error    = true;
					$update_errors[] = __( 'The compressor you seleted is unavailable. Please select another.', 'boldgrid-backup' );
				}
			}

			/*
			 * Save "backup filelist analysis" setting.
			 *
			 * @since 1.14.13
			 */
			$settings['filelist_analysis'] = ! empty( $_POST['filelist_analysis'] ) ? 1 : 0;

			/*
			 * Save Compression Level Settings.
			 *
			 * @since 1.14.0
			 */
			if ( isset( $_POST['compression_level'] ) ) {
				$settings['compression_level'] = $_POST['compression_level'];
			}

			/*
			 * Save extractor settings.
			 *
			 * At this time, the extractor cannot be selected within the settings.
			 *
			 * @since 1.5.1
			 */
			if ( ! empty( $_POST['extractor'] ) ) {
				$selected_extractor = $_POST['extractor'];
				if ( in_array( $selected_extractor, $available_compressors, true ) ) {
					$settings['extractor'] = $selected_extractor;
				} else {
					$update_error    = true;
					$update_errors[] = __( 'The extractor you seleted is unavailable. Please select another.', 'boldgrid-backup' );
				}
			}

			/*
			 * Change the scheduler.
			 *
			 * If the scheduler is indeed changed, clear all prior backup
			 * schedules.
			 *
			 * @since 1.5.1
			 */
			$original_scheduler   = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : false;
			$schedulers_available = $this->core->scheduler->get_available();
			$scheduler_changed    = ! empty( $_POST['scheduler'] ) && $original_scheduler !== $_POST['scheduler'];
			if ( $scheduler_changed && array_key_exists( $_POST['scheduler'], $schedulers_available ) ) {
				$settings['scheduler'] = $_POST['scheduler'];
			}

			/*
			 * Save cron interval Settings.
			 *
			 * @since 1.16.0
			 */
			$intervals_available = $this->core->scheduler->get_intervals();
			if ( isset( $_POST['cron_interval'] ) && array_key_exists( $_POST['cron_interval'], $intervals_available ) ) {
				$settings['cron_interval'] = $_POST['cron_interval'];
			}

			/*
			 * Save WP Cron / Crons.
			 *
			 * @since 1.5.1
			 */
			$settings = $this->update_cron( $settings );

			// Add a notice if we tried and failed to add crons.
			if ( ! $settings['crons_added'] ) {
				$update_error    = true;
				$update_errors[] = esc_html__( 'An error occurred when modifying cron jobs. Please try again.', 'boldgrid-backup' );
			}

			/*
			 * Save storage locations.
			 *
			 * @since 1.5.2
			 */
			$storage_locations = ! empty( $settings['remote'] ) ? $settings['remote'] : array();

			// Start off by disabling each storage location.
			foreach ( $storage_locations as $remote_key => $storage_location ) {
				$settings['remote'][ $remote_key ]['enabled'] = false;
			}

			// Get the storage location array from POST, then sanitize below.
			$storage_locations_save = ! empty( $_POST['storage_location'] ) ?
				$_POST['storage_location'] : array();

			// Then enable it only if submitted.  Values are not used, only key/index.
			foreach ( $storage_locations_save as $storage_location => $storage_location_enabled ) {
				$storage_location = sanitize_key( $storage_location );

				/*
				 * Unless this is the local environment, don't enable a storage location if it has
				 * not yet been setup. Why enable FTP if FTP hasn't been setup yet.
				 */
				if ( 'local' === $storage_location || isset( $settings['remote'][ $storage_location ] ) ) {
					$settings['remote'][ $storage_location ]['enabled'] = true;
				}
			}

			/*
			 * Save tables to include.
			 *
			 * @since 1.5.3
			 */
			$settings['exclude_tables']      = $this->core->db_omit->get_from_post();
			$settings['exclude_tables_type'] = $this->core->db_omit->get_post_type();

			/*
			 * Save folder exclusion settings.
			 *
			 * @since 1.6.0
			 */
			$settings['folder_exclusion_include'] = $this->core->folder_exclusion->from_post( 'include' );
			$settings['folder_exclusion_exclude'] = $this->core->folder_exclusion->from_post( 'exclude' );
			$settings['folder_exclusion_type']    = $this->core->folder_exclusion->from_post( 'type' );

			/*
			 * Save Auto Update options.
			 *
			 * As of WP5.5 , a new UI for auto updates ws added.
			 * Therefore we have to make sure the 'auto_update_plugins' and
			 * 'auto_update_themes' option tables are also updated.
			 *
			 * @since 1.14.0
			 */
			if ( ! empty( $_POST['auto_update'] ) ) {
				$settings['auto_update'] = $this->validate_auto_update( $_POST['auto_update'] );
				// As of WordPress 5.5 we also have to update the option in the WordPress auto update option as well.
				if ( isset( $_POST['auto_update']['plugins'] ) ) {
					$this->update_autoupdate_options( $_POST['auto_update'] );
				}

				// This updates the options field for themes as well.
				if ( isset( $_POST['auto_update']['themes'] ) ) {
					$this->update_autoupdate_options( $_POST['auto_update'], true );
				}
				$update_error = $settings['auto_update'] ? $update_error : true;
			}

			// Read BoldGrid settings form POST request, sanitize, and merge settings with saved.
			$boldgrid_settings = array_merge(
				get_option( 'boldgrid_settings' ),
				\Boldgrid\Library\Library\Page\Connect::sanitizeSettings(
					array(
						'autoupdate'            => ! empty( $_POST['autoupdate'] ) ?
							(array) $_POST['autoupdate'] : array(),
						'release_channel'       => ! empty( $_POST['plugin_release_channel'] ) ?
							sanitize_key( $_POST['plugin_release_channel'] ) : 'stable',
						'theme_release_channel' => ! empty( $_POST['theme_release_channel'] ) ?
							sanitize_key( $_POST['theme_release_channel'] ) : 'stable',
					)
				)
			);

			// Cleanup old settings.
			unset(
				$settings['plugin_autoupdate'],
				$settings['theme_autoupdate'],
				$boldgrid_settings['plugin_autoupdate'],
				$boldgrid_settings['theme_autoupdate']
			);

			$settings['updated'] = time();

			// If no errors, then save the settings.
			if ( ! $update_error ) {
				update_site_option( 'boldgrid_backup_settings', $settings );
				update_option( 'boldgrid_settings', $boldgrid_settings );
			}
		}

		// If there was no error, then show success notice.
		if ( ! $update_error ) {
			// Success.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Settings saved.', 'boldgrid-backup' ),
				'updated settings-error notice is-dismissible'
			);
		} elseif ( empty( $update_errors ) ) {
			$failure_message = esc_html__( 'Invalid settings submitted.  Please try again.', 'boldgrid-backup' );
		} else {
			$failure_message = sprintf( '<strong>%1$s</strong><br /><br />%2$s', __( 'We were unable to save your settings for the following reason(s):', 'boldgrid-backup' ), implode( '<br /><br />', $update_errors ) );
		}

		if ( isset( $failure_message ) ) {
			do_action( 'boldgrid_backup_notice', $failure_message );
		}

		if ( ! $update_error ) {
			/**
			 * Take action when settings have been updated.
			 *
			 * @since 1.5.3
			 */
			do_action( 'boldgrid_backup_settings_updated', $settings );
		}

		// Return success.
		return ! $update_error;
	}

	/**
	 * Validates the auto_update submissions.
	 *
	 * @since 1.14.0
	 *
	 * @param array $posted_update_settings Update settings submitted via POST.
	 *
	 * @return array
	 */
	public function validate_auto_update( $posted_update_settings ) {
		$post_days = isset( $posted_update_settings['days'] ) ? $posted_update_settings['days'] : null;

		if ( null === $post_days || ( is_numeric( $post_days ) && 0 <= $post_days && 99 >= $post_days ) ) {
			return $posted_update_settings;
		}

		return false;
	}

	/**
	 * Delete the boldgrid_backup_pending_rollback option.
	 *
	 * @since 1.0.1
	 */
	public function delete_rollback_option() {
		delete_site_option( 'boldgrid_backup_pending_rollback' );
	}

	/**
	 * Menu callback to display the Backup schedule page.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Config::get_is_premium
	 * @see Boldgrid_Backup_Admin_Config::is_premium_active
	 */
	public function page_backup_settings() {

		$is_premium           = $this->core->config->get_is_premium();
		$is_premium_installed = $this->core->config->is_premium_installed;
		$is_premium_active    = $this->core->config->is_premium_active;

		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );
		wp_enqueue_style( 'bglib-ui-css' );
		wp_enqueue_script( 'bglib-ui-js' );
		wp_enqueue_script( 'bglib-sticky' );
		wp_enqueue_script( 'bglib-license' );

		$this->core->auto_rollback->enqueue_home_scripts();
		$this->core->auto_rollback->enqueue_backup_scripts();
		$this->core->archive_actions->enqueue_scripts();

		if ( ! $this->is_saving_settings ) {
			$is_functional = $this->core->test->run_functionality_tests();
		}

		// If tests fail, then show an admin notice and abort.
		if ( isset( $is_functional ) && ! $is_functional ) {
			do_action(
				'boldgrid_backup_notice',
				sprintf(
					// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
					__(
						'Functionality test has failed.  You can go to %1$sFunctionality Test%2$s to view a report.',
						'boldgrid-backup'
					),
					'<a href="' . admin_url( 'admin.php?page=boldgrid-backup-test' ) . '">',
					'</a>'
				),
				'notice notice-error is-dismissible'
			);
		}

		/*
		 * Display warning on resource usage and backups.
		 *
		 * As of 1.11.6, this notice is dismissable (and won't return).
		 */
		$notice_id = 'bgbkup_uses_resources';
		if ( ! Notice::isDismissed( $notice_id ) ) {
			$message = '<p>' . esc_html__(
				'Warning: Making backups uses resources. When the system is backing up, it will slow down your site for visitors. Furthermore, when the database itself is being copied, your site must “pause” temporarily to preserve data integrity. For most sites, the pause is typically a few seconds and is not noticed by visitors. Large sites take longer though. Please keep the number of backups you have stored and how often you make those backups to a minimum.',
				'boldgrid-backup'
			) . '</p>';

			Notice::show( $message, $notice_id );
		}

		// Get BoldGrid reseller settings.
		$boldgrid_reseller = get_option( 'boldgrid_reseller' );

		/*
		 * If not part of a reseller, then show the unofficial host notice.
		 *
		 * As of 1.11.6, this notice is dismissable (and won't return).
		 */
		$notice_id = 'bgbkup_host_policy';
		if ( ! Notice::isDismissed( $notice_id ) && empty( $boldgrid_reseller ) ) {
			$message = '<p>' . esc_html__(
				'Please note that your web hosting provider may have a policy against these types of backups. Please verify with your provider or choose a BoldGrid Official Host.',
				'boldgrid-backup'
			) . '</p>';

			Notice::show( $message, $notice_id );
		}

		// Check for settings update.
		if ( $this->is_saving_settings ) {
			// The nonce is verified in the update_settings method.
			$this->update_settings();
		}

		// Enqueue CSS for the settings page.
		wp_enqueue_style(
			'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-settings.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Enqueue the JS for the settings page.
		wp_enqueue_script(
			'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-settings.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		wp_enqueue_script(
			'boldgrid-backup-admin-settings-autoupdate',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-settings-autoupdate.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		// Enqueue JS for the toggles on the auto-updates section.
		wp_enqueue_script(
			'boldgrid-library-connect',
			\Boldgrid\Library\Library\Configs::get( 'libraryUrl' ) . 'src/assets/js/connect.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		// Enqueue jquery-toggles JS.
		wp_enqueue_script(
			'jquery-toggles',
			\Boldgrid\Library\Library\Configs::get( 'libraryUrl' ) . 'build/toggles.min.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			true
		);

		// Enqueue jquery-toggles CSS.
		wp_enqueue_style(
			'jquery-toggles-full',
			\Boldgrid\Library\Library\Configs::get( 'libraryUrl' ) . 'build/toggles-full.css',
			array(),
			BOLDGRID_BACKUP_VERSION
		);

		$this->core->folder_exclusion->enqueue_scripts();
		$this->core->db_omit->enqueue_scripts();

		$settings = $this->get_settings();

		// If the directory path is not in the settings, then add it for the form.
		if ( empty( $settings['backup_directory'] ) ) {
			$settings['backup_directory'] = $this->core->backup_dir->get();
		}

		$available_compressors = $this->core->compressors->get_available();

		if ( ! $is_premium || ! $is_premium_installed || ! $is_premium_active ) {
			$settings['encrypt_db'] = false;
		}

		$in_modal = true;
		$modal    = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
		$in_modal = false;

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html__( 'Total Upkeep Backup and Restore Settings', 'boldgrid-backup' ) . '</h1>
						<div class="page-title-actions">
						<a href="#TB_inline?width=800&amp;height=600&amp;inlineId=backup_now_content" class="thickbox page-title-action page-title-action-primary">' .
							esc_html__( 'Backup Site Now', 'boldgrid-backup' ) . '
						</a>
						<a class="page-title-action add-new">' . esc_html__( 'Upload Backup', 'boldgrid-backup' ) . '</a>
					</div>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		echo $modal; //phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-settings.php';
		echo '
				</div>
			</div>
		</div>';
	}

	/**
	 * Save our settings.
	 *
	 * @since 1.5.2
	 *
	 * @param  array $settings Settings.
	 * @return bool True on success.
	 */
	public function save( $settings ) {
		return update_site_option( 'boldgrid_backup_settings', $settings );
	}

	/**
	 * Update CRON jobs.
	 *
	 * @since 1.8.0
	 *
	 * @param  array $settings Settings.
	 * @return array
	 */
	public function update_cron( array $settings ) {
		$settings['crons_added'] = false;

		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;

		if ( 'wp-cron' === $scheduler ) {
			$settings['crons_added'] = $this->core->wp_cron->add_all_crons( $settings );
		} elseif ( 'cron' === $scheduler ) {
			$settings['crons_added']     = $this->core->cron->add_all_crons( $settings );
			$settings['crontab_version'] = $this->core->cron->crontab_version;
			$settings['cron_secret']     = $this->core->cron->get_cron_secret();
		}

		return $settings;
	}
}
