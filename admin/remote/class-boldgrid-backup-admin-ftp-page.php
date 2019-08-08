<?php
/**
 * File: class-boldgrid-backup-admin-ftp-page.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/remote
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Ftp_Page
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Ftp_Page {
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
	 * Enqueue scripts.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {
		if ( $this->core->utility->is_admin_page( 'boldgrid-backup-ftp' ) ) {
			$handle = 'boldgrid-backup-admin-ftp-settings';
			wp_register_script(
				$handle,
				plugin_dir_url( dirname( __FILE__ ) ) . 'js/' . $handle . '.js',
				array( 'jquery' ),
				BOLDGRID_BACKUP_VERSION,
				false
			);
			$translation = array(
				'default_port' => $this->core->ftp->default_port,
			);
			wp_localize_script( $handle, 'BoldGridBackupAdminFtpSettings', $translation );
			wp_enqueue_script( $handle );

			wp_enqueue_style(
				$handle,
				plugin_dir_url( dirname( __FILE__ ) ) . 'css/' . $handle . '.css',
				array(),
				BOLDGRID_BACKUP_VERSION
			);

			wp_enqueue_style( 'boldgrid-backup-admin-hide-all' );
		}
	}

	/**
	 * Generate the submenu page for our FTP Settings page.
	 *
	 * @since 1.6.0
	 */
	public function settings() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		// Used with wp_kses call below.
		$allowed_html = array(
			'div'  => array(
				'class' => array(),
			),
			'span' => array(
				'class' => array(),
			),
		);

		/*
		 * Blank data, used when deleting settings.
		 *
		 * If we are deleting our settings, this data will be used to repopulate the form.
		 */
		$type       = $this->core->ftp->default_type;
		$blank_data = array(
			'type'            => $type,
			'ftp_mode'        => $this->core->ftp->default_ftp_mode,
			'host'            => null,
			'port'            => $this->core->ftp->default_port[ $type ],
			'user'            => null,
			'pass'            => null,
			'folder_name'     => $this->core->ftp->default_folder_name,
			'retention_count' => $this->core->ftp->retention_count,
			'nickname'        => '',
		);

		// Post data, used by default or when updating settings.
		$post_data = $this->core->ftp->get_from_post();

		$action = ! empty( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : null; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.NoNonceVerification

		switch ( $action ) {
			case 'save':
				echo wp_kses( $this->core->elements['long_checking_creds'], $allowed_html );
				if ( ob_get_level() > 0 ) {
					ob_flush();
				}
				flush();

				$this->settings_save();
				$data = $post_data;
				break;
			case 'delete':
				$this->settings_delete();
				$data = $blank_data;
				break;
			default:
				$data = $post_data;
		}

		include BOLDGRID_BACKUP_PATH . '/admin/partials/remote/ftp.php';
	}

	/**
	 * Process the user's request to update their FTP settings.
	 *
	 * @since 1.6.0
	 */
	public function settings_delete() {
		$ftp = $this->core->ftp;

		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		$settings = $this->core->settings->get_settings();
		if ( ! isset( $settings['remote'][ $ftp->key ] ) || ! is_array( $settings['remote'][ $ftp->key ] ) ) {
			$settings['remote'][ $ftp->key ] = array();
		}

		$settings['remote'][ $ftp->key ] = array();
		update_site_option( 'boldgrid_backup_settings', $settings );

		$ftp->reset();
		$ftp->disconnect();

		do_action( 'boldgrid_backup_notice', __( 'Settings deleted.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
	}

	/**
	 * Process the user's request to update their FTP settings.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function settings_save() {
		$success = true;

		if ( ! check_ajax_referer( 'bgb-settings-ftp', 'ftp_auth' ) ) {
			do_action(
				'boldgrid_backup_notice',
				__( 'Unauthorized request.', 'boldgrid-backup' ),
				'notice error is-dismissible'
			);

			return false;
		}

		// Readability.
		$ftp = $this->core->ftp;

		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if ( empty( $_POST ) ) {
			return false;
		}

		$settings = $this->core->settings->get_settings();
		if ( ! isset( $settings['remote'][ $ftp->key ] ) || ! is_array( $settings['remote'][ $ftp->key ] ) ) {
			$settings['remote'][ $ftp->key ] = array();
		}

		// This method has default values for each setting and also handles sanitization.
		$data = $ftp->get_from_post();

		$valid_credentials = $ftp->is_valid_credentials(
			$data['host'],
			$data['user'],
			$data['pass'],
			$data['port'],
			$data['type'],
			$data['ftp_mode']
		);

		if ( $valid_credentials ) {
			$settings['remote'][ $ftp->key ]['host']     = $data['host'];
			$settings['remote'][ $ftp->key ]['user']     = $data['user'];
			$settings['remote'][ $ftp->key ]['pass']     = $data['pass'];
			$settings['remote'][ $ftp->key ]['port']     = $data['port'];
			$settings['remote'][ $ftp->key ]['type']     = $data['type'];
			$settings['remote'][ $ftp->key ]['ftp_mode'] = $data['ftp_mode'];
		}

		$settings['remote'][ $ftp->key ]['retention_count'] = $data['retention_count'];
		$settings['remote'][ $ftp->key ]['nickname']        = $data['nickname'];
		$settings['remote'][ $ftp->key ]['folder_name']     = $data['folder_name'];

		if ( ! empty( $ftp->errors ) ) {
			do_action( 'boldgrid_backup_notice', implode( '<br /><br />', $ftp->errors ) );
			$success = false;
		} else {
			update_site_option( 'boldgrid_backup_settings', $settings );
			do_action( 'boldgrid_backup_notice', __( 'Settings saved.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
		}

		return $success;
	}
}
