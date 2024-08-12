<?php
/**
 * File: class-boldgrid-backup-admin-folder-exclusion.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP, WordPress.CSRF.NonceVerification.NoNonceVerification

/**
 * Class: Boldgrid_Backup_Admin_Folder_Exclusion
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Folder_Exclusion {
	/**
	 * The default exclude value.
	 *
	 * @since 1.6.0
	 * @var   string
	 */
	public $default_exclude = '.git,node_modules,wp-content/cache';

	/**
	 * The default include value.
	 *
	 * @since 1.6.0
	 * @var   string
	 */
	public $default_include = 'WPCORE,/wp-content';

	/**
	 * By default, backup all files and folders (use default settings).
	 *
	 * @since 1.6.0
	 * @var   string
	 */
	public $default_type = 'full';

	/**
	 * Our exclude value.
	 *
	 * @since 1.6.0
	 * @var   string|null
	 */
	public $exclude = null;

	/**
	 * Our include value.
	 *
	 * @since 1.6.0
	 * @var   string|null
	 */
	public $include = null;

	/**
	 * Whether or not we're in the ajax preview.
	 *
	 * @since 1.6.0
	 * @var   bool
	 */
	public $in_ajax_preview = false;

	/**
	 * Filename of our restore-info.json file.
	 *
	 * @since 1.14.10
	 * @var string
	 */
	public $restore_info_filename;

	/**
	 * Determine the type of backup we are performing.
	 *
	 * Usually it will be 'full' or 'custom'.
	 *
	 * @since 1.6.0
	 * @var   null|string
	 */
	public $type = null;

	/**
	 * Allowable types.
	 *
	 * @since 1.6.0
	 * @var   array
	 */
	public $types = array( 'include', 'exclude', 'type' );

	/**
	 * Valid backup types.
	 *
	 * @since 1.6.0
	 * @var   array
	 */
	public $valid_types = array( 'full', 'custom' );

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

		/**
		 * Allow the filtering of the default include and exclude settings.
		 *
		 * As of 1.6.0, the backup plugin is not yet "properly" designed to
		 * allow for these settings to be filtered. Yes, these filters below are
		 * being ran, however they're being ran in the constructor. The only
		 * plugins we can guarantee that will be able to hook into these filters
		 * are mu-plugins. One possible scenario this would be used is an mu-plugin
		 * hooking into these filters to ensure "mu-plugins" are excluded from
		 * backups.
		 *
		 * @since 1.6.0
		 *
		 * @param string $this->default_include Default include values.
		 */
		$this->default_include = apply_filters( 'boldgrid_backup_default_folder_include', $this->default_include );
		$this->default_exclude = apply_filters( 'boldgrid_backup_default_folder_exclude', $this->default_exclude );

		// Set in the constructor so as to prevent excessive calls in self::allow_file.
		$this->restore_info_filename = basename( \Boldgrid\Backup\Cli\Info::get_results_filepath() );
	}

	/**
	 * Determine if we should allow a file in the backup.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $file File path.
	 * @return bool
	 */
	public function allow_file( $file ) {
		// If this file is in our backup directory, do not allow it.
		if ( $this->core->backup_dir->file_in_dir( ABSPATH . $file, true ) ) {
			return false;
		}

		// Do not allow the "cron/restore-info.json" file used for emergency restorations.
		if ( $this->is_match( 'cron/' . $this->restore_info_filename, $file ) ) {
			return false;
		}

		if ( $this->is_banned( $file ) ) {
			return false;
		}

		// Get comma-delimited lists from user input or settings.  Sanitizing is done below.
		$include = $this->in_ajax_preview ? $_POST['include'] : $this->from_settings( 'include' );
		$exclude = $this->in_ajax_preview ? $_POST['exclude'] : $this->from_settings( 'exclude' );

		// Convert comma-delimited strings to arrays, and sanitize (also trim whitespace).
		$includes = array_map( 'sanitize_text_field', explode( ',', $include ) );
		$excludes = array_map( 'sanitize_text_field', explode( ',', $exclude ) );

		// Default values, include everything and exclude nothing.
		$is_match_include = false;
		$is_match_exclude = false;

		foreach ( $includes as $include ) {
			if ( $this->is_match( $include, $file ) ) {
				$is_match_include = true;
			}
		}

		// If we're not including this file, we don't need to check excludes.
		if ( ! $is_match_include ) {
			return false;
		}

		// If the user left "excludes" blank, then we're not excluding anything.
		if ( empty( $exclude ) ) {
			return true;
		}

		foreach ( $excludes as $exclude ) {
			if ( $this->is_match( $exclude, $file ) ) {
				$is_match_exclude = true;
			}
		}

		return ! $is_match_exclude;
	}

	/**
	 * Generate a section for email alerts including information about files and
	 * folders excluded.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $info File and folder settings.
	 * @return string
	 */
	public function email_part( $info ) {
		$body = '';

		$has_folder_included = isset( $info['folder_include'] );
		$has_folder_excluded = isset( $info['folder_exclude'] );

		if ( $has_folder_included || $has_folder_excluded ) {
			$body .= "\n" . __( 'FILE AND FOLDER SETTINGS', 'boldgrid-backup' ) . "\n";
		}

		if ( $has_folder_included ) {
			$body .= sprintf(
				// translators: 1: Included folder list.
				esc_html__( 'Included: %1$s', 'boldgrid-backup' ),
				$info['folder_include']
			) . "\n";
		}

		if ( $has_folder_excluded ) {
			$body .= sprintf(
				// translators: 1: Excluded folder list.
				esc_html__( 'Excluded: %1$s', 'boldgrid-backup' ),
				$info['folder_exclude']
			) . "\n";
		}

		return $body;
	}

	/**
	 * Create our regex pattern.
	 *
	 * If the user enters wp-* for their include / exclude value, then we need
	 * to convert that into a proper regex pattern.
	 *
	 * When we look for matches, we want to keep it specific to one folder.
	 * For example, if we're given wp-adm*n, the expectation is that we want
	 * to find everything within the wp-admin folder.
	 *
	 * To prevent this false positive:
	 *   wp-admin/images/media-button.png
	 *   wp-adm************************ng
	 * ... we will set the wildcard to match everything except a directory separator.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $value Input string.
	 * @return string
	 */
	public function create_pattern( $value ) {
		$first_char = substr( $value, 0, 1 );

		// Clean up our value. Forward slashes will be hanlded uniquely.
		$value = trim( $value, ' /' );

		// Escape everything except the wildcard.
		$value = preg_quote( $value );
		$value = str_replace( '\*', '*', $value );

		/*
		 * If the first character is a /, then assume we're requiring the $file
		 * to be directly in the ABSPATH, and so the $file should beging with
		 * ^$value.
		 *
		 * Otherwise assume our $file should begin with ^value or procede a
		 * forward slash /$value.
		*/
		$pattern = '/' === $first_char ? '#^' : '#(^|/)';

		/*
		 * To keep wildcards within a path part, ensure the wildcard matches
		 * anything except a /. This ensure that wp-adm* matches wp-adm*n and
		 * not wp-admin/file.png.
		 */
		$pattern .= str_replace( '*', '[^/]*', $value );

		/*
		 * Our $file should either end with our $$value, or our $value should be
		 * followed by a forward slash $value/.
		 */
		$pattern .= '($|/)#';

		return $pattern;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {
		$handle = 'boldgrid-backup-admin-folder-exclude';
		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-folder-exclude.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$translation = array(
			'default_include' => $this->default_include,
			'default_exclude' => $this->default_exclude,
			'items'           => __( 'items', 'boldgrid-backup' ),
			'no_results'      => __( 'No results', 'boldgrid-backup' ),
			'of'              => __( 'of', 'boldgrid-backup' ),
		);
		wp_localize_script( $handle, 'BoldGridBackupAdminFolderExclude', $translation );
		wp_enqueue_script( $handle );

		// Enqueue CSS for folder exclude functionality.
		wp_enqueue_style(
			$handle,
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-folder-exclude.css', array(),
			BOLDGRID_BACKUP_VERSION
		);

		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-effects-bounce' );
	}

	/**
	 * Get our include or exclude value from the settings.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $type     Either 'include' or 'exclude'.
	 * @param  array  $settings File and folder settings.
	 * @return string
	 */
	public function from_settings( $type, $settings = false ) {
		if ( ! in_array( $type, $this->types, true ) ) {
			return false;
		}

		$key     = 'folder_exclusion_' . $type;
		$default = 'default_' . $type;

		/*
		 * Determine if we need to do a full backup.
		 *
		 * Scenarios include:
		 * # We are in the middle of creating a backup file for update protection.
		 * # We are creating a 'full' backup.
		 * # We are creating a backup immediately before a WordPress auto update.
		 */
		if ( $this->core->is_archiving_update_protection || $this->core->is_backup_full || $this->core->pre_auto_update ) {
			return $this->$default;
		}

		/*
		 * If we are backing up a site now (not for update protection) and
		 * we've posted folder settings, use those.
		 */
		if ( $this->core->is_backup_now && isset( $_POST[ $key ] ) ) {
			$this->$type = $this->from_post( $type );
			return $this->$type;
		}

		if ( ! is_null( $this->$type ) ) {
			return $this->$type;
		}

		if ( $this->core->settings->is_saving_settings ) {
			$this->$type = $this->from_post( $type );
		} elseif ( isset( $settings['folder_exclusion_type'] ) && 'full' === $settings['folder_exclusion_type'] ) {
			/*
			 * If the user configured "Backup all files" as the "Files and Folders" settings, then
			 * use the default values.
			 */
			$this->$default;
		} elseif ( isset( $settings[ $key ] ) ) {
			/*
			 * Is there value for this in the settings?
			 *
			 * Initially, we checked to make sure $settings[$key] wasn't empty and
			 * it was a string. Now, we'll simply see if it is set. This will allow
			 * for the user to enter nothing in the exclude field.
			 */
			$this->$type = $settings[ $key ];
		} elseif ( ! $settings ) {
			$settings = $this->core->settings->get_settings();
			if ( ! empty( $settings[ $key ] ) && is_string( $settings[ $key ] ) ) {
				$this->$type = $settings[ $key ];
			}
		}

		if ( is_null( $this->$type ) ) {
			$this->$type = $this->$default;
		}

		return $this->$type;
	}

	/**
	 * Whether or not a file is banned.
	 *
	 * Some files are just bad for business. Files in this list won't be backed up, and the user has
	 * no control at this time to modify. Only files we're certain should be banned, should be.
	 *
	 * @since 1.14.13
	 *
	 * @param string $file A filepath. Not absolute, but relative to ABSPATH, such as wp-admin/css/about.css
	 *
	 * @return bool
	 */
	public function is_banned( $filepath ) {
		$banned = Boldgrid_Backup_Admin::get_configs()['banned'];

		// @todo Allow for regular expressions in the future.
		return in_array( basename( $filepath ), $banned, true );
	}

	/**
	 * Determine if a include/exclude value matches a file.
	 *
	 * For example, if I pass in "wp-content" as a $value and
	 * wp-content/file.php as a $file, it should match. If I pass in "joec" as a
	 * $value and wp-content/file.php as a $file, it should not match.
	 *
	 * @param  string $value Input string.
	 * @param  string $file  File path relative to ABSPATH, such as:
	 *                       "wp-content/plugins/boldgrid-backup/boldgrid-backup.php".
	 * @return bool
	 */
	public function is_match( $value, $file ) {
		if ( '*' === $value ) {
			return true;
		}

		// Handle filtering of core WordPress files.
		if ( 'WPCORE' === $value ) {
			return $this->core->core_files->is_core_file( $file );
		}

		/*
		 * Convert a Windows filepath to Linux. In this method we're going to
		 * assume that / is the directory separator.
		 */
		$file = str_replace( '\\', '/', $file );

		$pattern = $this->create_pattern( $value );

		preg_match( $pattern, $file, $matches );

		return ! empty( $matches );
	}

	/**
	 * Is using default settings?
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_using_defaults() {
		$type = $this->from_settings( 'type' );

		return 'full' === $type;
	}

	/**
	 * Get our include / exclude settings from $_POST.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $type Either include or exclude.
	 * @return string
	 */
	public function from_post( $type ) {
		if ( ! in_array( $type, $this->types, true ) ) {
			return false;
		}

		$key = 'folder_exclusion_' . $type;

		switch ( $type ) {
			case 'include':
				/*
				 * If you submit an empty "include" setting, it will be
				 * interpreted as include all, *.
				 */
				$value = ! empty( $_POST[ $key ] ) ? $_POST[ $key ] : $this->default_include;
				break;
			case 'exclude':
				/*
				 * You are allowed to submit a blank "exclude" setting. It means
				 * you do not want to exclude anything.
				 */
				$value = empty( $_POST[ $key ] ) ? '' : $_POST[ $key ];
				break;
			case 'type':
				$value = ! empty( $_POST[ $key ] ) &&
					in_array( $_POST[ $key ], $this->valid_types, true ) ?
					$_POST[ $key ] : $this->default_type;
				break;
		}

		$value = sanitize_text_field( $value );

		return $value;
	}

	/**
	 * Handle the ajax request to preview the filters.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_preview() {
		if ( ! check_ajax_referer( 'folder_exclusion_preview', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$include = isset( $_POST['include'] ) ? sanitize_text_field( $_POST['include'] ) : null;
		$exclude = isset( $_POST['exclude'] ) ? sanitize_text_field( $_POST['exclude'] ) : null;

		if ( is_null( $include ) || is_null( $exclude ) ) {
			wp_send_json_error( __( 'Invalid include / exclude values.', 'boldgrid-backup' ) );
		}

		$this->in_ajax_preview = true;

		$filelist = $this->core->get_filtered_filelist();

		if ( empty( $filelist ) ) {
			wp_send_json_error( __( 'No files match your criteria.', 'boldgrid-backup' ) );
		}

		$markup = array();

		foreach ( $filelist as $file ) {
			$markup[] = $file[1];
		}

		wp_send_json_success( $markup );
	}
}
