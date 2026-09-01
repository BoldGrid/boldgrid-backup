<?php
/**
 * File: class-boldgrid-backup-admin-nopriv.php
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Nopriv
 *
 * This is a generic utility class for nopriv calls.
 *
 * It includes methods like making an async call to trigger a backup.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Admin_Nopriv {
	/**
	 * Generate a backup.
	 *
	 * This makes an async call to generate a backup, so that the calling method knows a backup has
	 * been instantiated and can continue on to other things right away.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  array $args {
	 *      Optional. An array of args.
	 *
	 *      @type string $task_id A task id (if one already exists).
	 * }
	 * @return mixed The results of the wp_remote_post call. An array of data on success, or a WP_Error
	 *               on fail.
	 *               Example return data when creating a backup via rest: https://pastebin.com/BeACwA2k
	 */
	public function do_backup( $args = [] ) {
		$url = $this->get_backup_url();

		$body = [
			/*
			 * Sometimes a task id will already be defined before the backup is started. One example
			 * is when a backup is started via REST. It (1) creates a task, (2) calls this method to
			 * start the backup, (3) immediately returns the tasks id - which a status can be queried
			 * for ASAP.
			 */
			'task_id' => ! empty( $args['task_id'] ) ? $args['task_id'] : '',
		];

		$post_args = [
			'timeout'   => 1,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body'      => $body,
		];

		return wp_remote_post( $url, $post_args );
	}

	/**
	 * Restore a backup via url.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  array $args An optional array of args.
	 * @return mixed       Response from wp_remote_post.
	 */
	public function do_restore( $args = [] ) {
		$url = $this->get_restore_url();

		$body = [
			/*
			 * Sometimes a task id will already be defined before the restore is started. One example
			 * is when a restore is started via REST. It (1) creates a task, (2) calls this method to
			 * start the restore, (3) immediately returns the tasks id - which a status can be queried
			 * for ASAP.
			 */
			'task_id'     => ! empty( $args['task_id'] ) ? $args['task_id'] : '',
			'restore_now' => 1,
		];

		$post_args = [
			'timeout'   => 1,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body'      => $body,
		];

		return wp_remote_post( $url, $post_args );
	}

	/**
	 * Get the nopriv url for generating a backup.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public function get_backup_url() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		return add_query_arg(
			[
				'action'        => 'boldgrid_backup_run_backup',
				'id'            => $core->get_backup_identifier(),
				'secret'        => $core->cron->get_cron_secret(),
				'doing_wp_cron' => time(),
			],
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Get the nopriv url for restoring a backup via url.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public function get_restore_url() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		return add_query_arg(
			[
				'action'        => 'boldgrid_backup_run_restore',
				'id'            => $core->get_backup_identifier(),
				'secret'        => $core->cron->get_cron_secret(),
				'doing_wp_cron' => time(),
			],
			admin_url( 'admin-ajax.php' )
		);
	}
}
