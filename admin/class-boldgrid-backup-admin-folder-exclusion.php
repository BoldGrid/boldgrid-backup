<?php
/**
 * Folder Exclusion class.
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
 * BoldGrid Backup Admin Folder Exclusion Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Folder_Exclusion {

	/**
	 * The default exclude value.
	 *
	 * @since 1.5.4
	 * @var   string
	 */
	public $default_exclude = '.git';

	/**
	 * The default include value.
	 *
	 * @since 1.5.4
	 * @var   string
	 */
	public $default_include = 'WPCORE,/wp-content';

	/**
	 * Our exclude value.
	 *
	 * @since 1.5.4
	 * @var   string|null
	 */
	public $exclude = null;

	/**
	 * Our include value.
	 *
	 * @since 1.5.4
	 * @var   string|null
	 */
	public $include = null;

	/**
	 * Whether or not we're in the ajax preview.
	 *
	 * @since 1.5.4
	 * @var   bool
	 */
	public $in_ajax_preview = false;

	/**
	 * Allowable types.
	 *
	 * @since 1.5.4
	 * @var   array
	 */
	public $types = array( 'include', 'exclude' );

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
	 * Determine if we should allow a file in the backup.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $file
	 * @return bool
	 */
	public function allow_file( $file ) {
		$include = $this->in_ajax_preview ? $_POST['include'] : $this->from_settings( 'include' );
		$exclude = $this->in_ajax_preview ? $_POST['exclude'] : $this->from_settings( 'exclude' );

		$includes = explode( ',', $include );
		$excludes = explode( ',', $exclude );

		// Default values, include everything and exclude nothing.
		$is_match_include = false;
		$is_match_exclude = false;

		foreach( $includes as $include ) {
			if( $this->is_match( $include, $file ) ) {
				$is_match_include = true;
			}
		}

		// If we're not including this file, we don't need to check excludes.
		if( ! $is_match_include ) {
			return false;
		}

		// If the user left "excludes" blank, then we're not excluding anything.
		if( empty( $exclude ) ) {
			return true;
		}

		foreach( $excludes as $exclude ) {
			if( $this->is_match( $exclude, $file ) ) {
				$is_match_exclude = true;
			}
		}

		return ! $is_match_exclude;
	}

	/**
	 * Create our regex pattern.
	 *
	 * If the user enters wp-* for their include / exclude value, then we need
	 * to convert that into a propper regex pattern.
	 *
	 * When we look for matches, we want to keep it specific to one folder.
	 * For example, if we're given wp-adm*n, the expectation is that we want
	 * to find everything within the wp-admin folder.
	 *
	 * To prevent this false positive:
	 *   wp-admin/images/media-button.png
	 *   wp-adm************************ng
	 * ... we will set the wildcard to match everything except a directory
	 * separator.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $value
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
	 * @since 1.5.4
	 */
	public function enqueue_scripts() {
		$handle = 'boldgrid-backup-admin-folder-exclude';
		wp_register_script( $handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-folder-exclude.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$translation = array(
			'default_include' => $this->default_include,
			'default_exclude' => $this->default_exclude,
			'items' => __( 'items', 'boldgrid-backup' ),
			'of' => __( 'of', 'boldgrid-backup' ),
		);
		wp_localize_script( $handle, 'BoldGridBackupAdminFolderExclude', $translation );
		wp_enqueue_script( $handle );

		// Enqueue CSS for folder exclude functionality.
		wp_enqueue_style(
			$handle,
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-folder-exclude.css', array(),
			BOLDGRID_BACKUP_VERSION
		);
	}

	/**
	 * Get our include or exclude value from the settings.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $type Either 'include' or 'exclude'.
	 * @param  array  $settings
	 * @return string
	 */
	public function from_settings( $type, $settings = false ) {
		if( ! in_array( $type, $this->types, true ) ) {
			return false;
		}

		$property = 'include' === $type ? 'include' : 'exclude';
		$key = 'folder_exclusion_' . $property;
		$default = 'default_' . $property;

		/*
		 * If we are in the middle of creating a backup file for update
		 * protection OR we are creating a 'full' backup, force default values
		 * (which backup both core files and all wp-content files).
		 */
		if( $this->core->is_archiving_update_protection || $this->core->is_backup_full ) {
			return $this->$default;
		}

		/*
		 * If we are backing up a site now (not for update protection) and
		 * we've posted folder settings, use those.
		 */
		if( $this->core->is_backup_now && isset( $_POST[$key] ) ) {
			$this->$property = $this->from_post( $type );
			return $this->$property;
		}

		if( ! is_null( $this->$property ) ) {
			return $this->$property;
		}

		if( $this->core->settings->is_saving_settings ) {
			$this->$property = $this->from_post( $type );
		} elseif( ! empty( $settings[$key] ) && is_string( $settings[$key] ) ) {
			$this->$property = $settings[$key];
		} elseif( ! $settings ) {
			$settings = $this->core->settings->get_settings();
			if( ! empty( $settings[$key] ) && is_string( $settings[$key] ) ) {
				$this->$property = $settings[$key];
			}
		}

		if( is_null( $this->$property ) ) {
			$this->$property = $this->$default;
		}

		return $this->$property;
	}

	/**
	 * Determine if a include/exclude value matches a file.
	 *
	 * For example, if I pass in "wp-content" as a $value and
	 * wp-content/file.php as a $file, it should match. If I pass in "joec" as a
	 * $value and wp-content/file.php as a $file, it should not match.
	 *
	 * @param  string $value
	 * @param  string $file  Filepath relative to ABSPATH, such as
	 *                       wp-content/plugins/boldgrid-backup/boldgrid-backup.php
	 * @return bool
	 */
	public function is_match( $value, $file ) {
		if( '*' === $value ) {
			return true;
		}

		// Handle filtering of core WordPress files.
		if( 'WPCORE' === $value ) {
			return $this->core->core_files->in( $file );
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
	 * Get our include / exclude settings from $_POST.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $type Either include or exclude.
	 * @return string
	 */
	public function from_post( $type ) {
		if( ! in_array( $type, $this->types, true ) ) {
			return false;
		}

		$key = 'folder_exclusion_';
		$key .= 'include' === $type ? 'include' : 'exclude';

		switch( $type ) {
			case 'include':
				/*
				 * If you submit an empty "include" setting, it will be
				 * interpreted as include all, *.
				 */
				$value = ! empty( $_POST[$key] ) ? $_POST[$key] : $this->default_include;
				break;
			case 'exclude':
				/*
				 * You are allowed to submit a blank "exclude" setting. It means
				 * you do not want to exclude anything.
				 */
				$value = empty( $_POST[$key] ) ? '' : $_POST[$key];
				break;
		}

		$value = trim( $value );

		return $value;
	}

	/**
	 * Handle the ajax request to preview the filters.
	 *
	 * @since 1.5.4
	 */
	public function wp_ajax_preview() {
		if( ! check_ajax_referer( 'folder_exclusion_preview', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$include = isset( $_POST['include'] ) ? $_POST['include'] : null;
		$exclude = isset( $_POST['exclude'] ) ? $_POST['exclude'] : null;
		if( is_null( $include ) || is_null( $exclude ) ) {
			wp_send_json_error( __( 'Invalid include / exclude values.', 'boldgrid-backup' ) );
		}

		$this->in_ajax_preview = true;

		$filelist = $this->core->get_filtered_filelist();

		if( empty( $filelist ) ) {
			wp_send_json_error( __( 'No files match your criteria.', 'boldgrid-backup' ) );
		}

		$markup = array();
		foreach( $filelist as $file ) {
			$markup[] = $file[1];
		}

		wp_send_json_success( $markup );
	}
}
