<?php
/**
 * Amazon S3 class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

use Aws\S3\S3Client;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Exception\MultipartUploadException;
use Aws\Common\Model\MultipartUpload\AbstractTransfer;

/**
 * Amazon S3 class.
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Remote_Amazon_S3 {

	/**
	 * Bucket id.
	 *
	 * @since 1.5.2
	 * @var string $bucket_id
	 */
	public $bucket_id = null;

	/**
	 * Our S3 client.
	 *
	 * @since 1.5.2
	 * @var object $client
	 */
	private $client = null;

	/**
	 * The core class object.
	 *
	 * @since 1.5.2
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Key.
	 *
	 * @since 1.5.2
	 * @var string $key
	 */
	private $key = null;

	/**
	 * Secret, I'm not telling.
	 *
	 * @since 1.5.2
	 * @var string $secret
	 */
	private $secret = null;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$settings = $this->core->settings->get_settings();

		$this->key =       ! empty( $settings['remote']['amazon_s3']['key'] )       ? $settings['remote']['amazon_s3']['key']       : $this->key;
		$this->secret =    ! empty( $settings['remote']['amazon_s3']['secret'] )    ? $settings['remote']['amazon_s3']['secret']    : $this->secret;
		$this->bucket_id = ! empty( $settings['remote']['amazon_s3']['bucket_id'] ) ? $settings['remote']['amazon_s3']['bucket_id'] : $this->create_unique_bucket();

		$this->create_unique_bucket();
	}

	/**
	 * Upload a backup via an ajax request.
	 *
	 * This is done via the archive details of a single archive.
	 *
	 * @since 1.5.2
	 */
	public function ajax_upload() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$filepath = ! empty( $_POST['filepath'] ) ? $_POST['filepath'] : false;
		if( empty( $filepath ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( __( 'Invalid archive filepath.', 'boldgrid-backup' ) );
		}

		// @todo Temp code to get more details about any errors.
		add_action( 'shutdown', function() {
			$last_error = error_get_last();

			// If there's no error or this is not fatal, abort.
			if( empty( $last_error ) || 1 !== $last_error['type'] ) {
				return;
			}

			$message = sprintf(
				'<strong>%1$s</strong>: %2$s in %3$s on line %4$s',
				__( 'Fatal error', 'boldgrid-backup' ),
				$last_error['message'],
				$last_error['file'],
				$last_error['line']
			);

			wp_send_json_error( $message );
		});

		$upload_result = $this->upload( $filepath );

		if( true === $upload_result ) {
			wp_send_json_success( $upload_result );
		} else {
			wp_send_json_error( $upload_result );
		}
	}

	/**
	 * Create a unique bucket id.
	 *
	 * When you delete a bucket, Amazon gives you the following message:
	 * Amazon S3 buckets are unique. If you delete this bucket, you may lose the
	 * bucket name to another AWS user.
	 */
	public function create_unique_bucket() {
		$url = parse_url( get_site_url() );

		$bucket_parts[] = 'boldgrid-backup';
		$bucket_parts[] = $url['host'];

		$bucket_id = implode( '-', $bucket_parts );

		return $bucket_id;
	}

	/**
	 * Get the contents of a bucket.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $bucket_id
	 * @return array  https://pastebin.com/uVkx8t5A
	 */
	public function get_bucket( $bucket_id ) {
		$this->set_client();
		$this->set_bucket_id( $bucket_id );

		$bucket_contents = array();

		// If the bucket does not exist, return an empty bucket.
		try{
			$iterator = $this->client->getIterator( 'ListObjects', array(
				'Bucket' => $this->bucket_id
			));

			foreach( $iterator as $object ) {
				$bucket_contents[] = $object;
			}
		} catch( Aws\S3\Exception\NoSuchBucketException $e ) {
			return array();
		}

		return $bucket_contents;
	}

	/**
	 * Get settings.
	 *
	 * @since 1.5.2
	 */
	public function get_details() {
		$settings = $this->core->settings->get_settings();

		return array(
			'title' => __( 'Amazon S3', 'boldgrid-backup' ),
			'key' => 'amazon_s3',
			'configure' => 'admin.php?page=boldgrid-backup-amazon-s3',
			'is_setup' => $this->is_setup(),
			'enabled' => ! empty( $settings['remote']['amazon_s3']['enabled'] ) && $settings['remote']['amazon_s3']['enabled'] && $this->is_setup(),
		);
	}

	/**
	 * Determine if a file exists in a bucket.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $filepath
	 * @return bool
	 */
	public function in_bucket( $bucket_id, $filepath ) {
		$this->set_client();
		$bucket_contents = $this->get_bucket( $bucket_id );
		$filename = basename( $filepath );

		foreach( $bucket_contents as $item ) {
			if( $item['Key'] === $filename ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set our client.
	 *
	 * @since 1.5.2
	 *
	 * @param string $key
	 * @param string $secret
	 */
	public function set_client( $key = null, $secret = null ) {
		$key = empty( $key ) ? $this->key : $key;
		$secret = empty( $secret ) ? $this->secret : $secret;

		$credentials = array(
			'key'    => $key,
			'secret' => $secret,
		);

		$this->client = S3Client::factory( array(
			'credentials' => $credentials,
		));
	}

	/**
	 * Return data about a particular archive in Amazon S3.
	 *
	 * For example, if you're looking at a single backup, we need to know if it
	 * already exists in our Amazon S3 account.
	 *
	 * This method will return an array of information useful to the single
	 * archive page.
	 *
	 * @since  1.5.2
	 * @return array
	 */
	public function single_archive_remote_option( $remote_storage, $filepath ) {

		$allow_upload = $this->is_setup();
		$uploaded = $allow_upload && $this->in_bucket( null, $filepath );

		$remote_storage_li[] = array(
			'id' => 'amazon_s3',
			'title' => 'Amazon S3',
			'uploaded' => $uploaded,
			'allow_upload' => $allow_upload,
		);

		return $remote_storage_li;
	}

	/**
	 * Generate the submenu page for our Amazon S3 Settings page.
	 *
	 * @since 1.5.2
	 */
	public function submenu_page() {
		wp_enqueue_style( 'boldgrid-backup-admin-hide-all' );

		$this->submenu_page_save();

		$settings = $this->core->settings->get_settings();

		$key = ! empty( $settings['remote']['amazon_s3']['key'] ) ? $settings['remote']['amazon_s3']['key'] : null;
		$secret = ! empty( $settings['remote']['amazon_s3']['secret'] ) ? $settings['remote']['amazon_s3']['secret'] : null;
		$bucket_id = ! empty( $settings['remote']['amazon_s3']['bucket_id'] ) ? $settings['remote']['amazon_s3']['bucket_id'] : $this->bucket_id;

		include BOLDGRID_BACKUP_PATH . '/admin/partials/remote/amazon-s3.php';
	}

	/**
	 * Process the user's request to update their Amazon S3 settings.
	 *
	 * @since 1.5.2
	 */
	public function submenu_page_save() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if( empty( $_POST ) ) {
			return false;
		}

		$settings = $this->core->settings->get_settings();
		if( ! isset( $settings['remote']['amazon_s3'] ) || ! is_array( $settings['remote']['amazon_s3'] ) ) {
			$settings['remote']['amazon_s3'] = array();
		}

		/*
		 * If the user has requested to delete all their settings, do that now
		 * and return.
		 */
		if( __( 'Delete settings', 'boldgrid-backup' ) === $_POST['submit'] ) {
			$settings['remote']['amazon_s3'] = array();
			update_site_option( 'boldgrid_backup_settings', $settings );

			$this->key = null;
			$this->secret = null;
			$this->bucket_id = null;

			do_action( 'boldgrid_backup_notice', __( 'Settings saved.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
			return;
		}

		$errors = array();

		$key =       ! empty( $_POST['key'] )       ? $_POST['key']       : null;
		$secret =    ! empty( $_POST['secret'] )    ? $_POST['secret']    : null;
		$bucket_id = ! empty( $_POST['bucket_id'] ) ? $_POST['bucket_id'] : null;

		$valid_credentials = $this->is_valid_credentials( $key, $secret );

		/*
		 * Check if we have a valid bucket name.
		 *
		 * If we don't have a valid client, set that first.
		 */
		if( is_null( $this->client ) ) {
			$this->set_client();
		}
		$valid_bucket = $this->client->isValidBucketName( $bucket_id );

		if( $valid_credentials ) {
			$settings['remote']['amazon_s3']['key'] = $key;
			$settings['remote']['amazon_s3']['secret'] = $secret;
			$this->key = $key;
			$this->secret = $secret;
		} else {
			$errors[] = __( 'Invalid Access Key Id and / or Secret Access Key.', 'boldgrid-backup' );
		}

		if( $valid_bucket ) {
			$settings['remote']['amazon_s3']['bucket_id'] = $bucket_id;
			$this->bucket_id = $bucket_id;
		} else {
			$errors[] = __( 'Invalid Bucket ID. Please only use lowercase letters, numbers, and hypens. Bucket must also be between 3 and 63 characters long.', 'boldgrid-inspirations' );
		}

		if( ! empty( $errors ) ) {
			do_action( 'boldgrid_backup_notice', implode( '<br /><br />', $errors ) );
		} else {
			update_site_option( 'boldgrid_backup_settings', $settings );
			do_action( 'boldgrid_backup_notice', __( 'Settings saved.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
		}
	}

	/**
	 * Set our bucket id.
	 *
	 * @since 1.5.2
	 *
	 * @param string $bucket_id
	 */
	public function set_bucket_id( $bucket_id ) {
		if( empty( $bucket_id ) ) {
			return;
		}

		$this->bucket_id = $bucket_id;
	}

	/**
	 * Upload a backup file.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $filepath
	 * @return mixed  True on success, error message on failure.
	 */
	public function upload( $filepath ) {
		$this->set_client();

		if( ! $this->core->wp_filesystem->exists( $filepath ) ) {
			return sprintf( __( 'Failed to upload, filepath does not exist: %1$s', 'boldgrid-backup'), $filepath );
		}

		$key = basename( $filepath );

		// Poll the bucket until it is accessible.
		$this->client->createBucket( array(
			'Bucket' => $this->bucket_id,
		));
		$this->client->waitUntil( 'BucketExists', array(
			'Bucket' => $this->bucket_id,
		));

		try {
			$uploader = UploadBuilder::newInstance()
				->setClient( $this->client )
				->setSource( $filepath )
				->setBucket( $this->bucket_id )
				->setKey( $key )
				->setOption( 'ACL', 'private' )
				->setConcurrency(3)
				->build();
		} catch ( Exception $e ) {
			return __( 'Failed to initialize', 'boldgrid-backup' );
		}

		try {
			$uploader->upload();
		} catch( MultipartUploadException $e ) {
			$uploader->abort();
			return __( 'Failed to upload.', 'boldgrid-inspirations' );
		}

		return true;
	}

	/**
	 * Upload a file.
	 *
	 * The jobs queue will call this method to upload a file.
	 *
	 * @since 1.5.2
	 *
	 * @param string $filepath
	 */
	public function upload_post_archiving( $filepath ) {
		$success = $this->upload( $filepath );

		return $success;
	}

	/**
	 * Determine if Amazon S3 is setup properly.
	 *
	 * Hook into "boldgrid_backup_is_setup_amazon_s3".
	 */
	public function is_setup() {
		return $this->is_valid_credentials( $this->key, $this->secret ) && $this->client->isValidBucketName( $this->bucket_id );
	}

	/**
	 * Determine if Amazon S3 is setup properly.
	 *
	 * This method is ran within an ajax request.
	 *
	 * @return array
	 */
	public function is_setup_ajax() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_settings', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$settings = $this->core->settings->get_settings();

		$location = $this->get_details();
		$tr = include BOLDGRID_BACKUP_PATH . '/admin/partials/settings/storage-location.php';

		if( $this->is_setup() ) {
			wp_send_json_success( $tr );
		} else {
			wp_send_json_error( $tr );
		}
	}

	/**
	 * Determine if credentials are valid.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $key
	 * @param  string $secret
	 * @return bool
	 */
	public function is_valid_credentials( $key, $secret ) {
		if( empty( $key ) || empty( $secret ) ) {
			return false;
		}

		$this->set_client( $key, $secret );

		try {
			$this->client->listBuckets();
			return true;
		} catch( Exception $e ) {
			// Deubg.
			// echo '<pre>'; echo get_class( $e ); echo '</pre>';
			return false;
		}
	}

	/**
	 * Actions to take after a backup file has been generated.
	 *
	 * @since 1.5.2
	 *
	 * @param array $info
	 */
	public function post_archive_files( $info ) {
		if( $this->core->remote->is_enabled( 'amazon_s3' ) && ! $info['dryrun'] && $info['save'] ) {

			$args = array(
				'filepath' => $info['filepath'],
				'action' => 'boldgrid_backup_amazon_s3_upload_post_archive',
				'action_data' => $info['filepath'],
			);

			$this->core->jobs->add( $args );
		}
	}

	/**
	 * Register Amazon S3 as a storage location.
	 *
	 * When you go to the BoldGrid Backup settings page and see a list of
	 * storage providers, each of those storage providers needs to hook into
	 * the "boldgrid_backup_register_storage_location" filter and add
	 * themselves.
	 *
	 * @since 1.5.2
	 */
	public function register_storage_location( $storage_locations ) {
		$storage_locations[] = $this->get_details();

		return $storage_locations;
	}
}
