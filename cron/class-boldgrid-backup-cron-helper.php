<?php
/**
 * File: class-boldgrid-backup-cron-helper.php
 *
 * @link https://www.boldgrid.com
 * @since 1.6.5
 *
 * @package Boldgrid_Backup
 * @copyright BoldGrid
 * @version $Id$
 * @author BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Cron_Helper
 *
 * @since 1.6.5
 */
class Boldgrid_Backup_Cron_Helper {
	/**
	 * Available execution functions.
	 *
	 * @since 1.8.0
	 * @access private
	 * @var array
	 */
	private $available_exec_functions;

	/**
	 * Determine if we are in the cli.
	 *
	 * @since 1.6.5
	 *
	 * @return bool
	 */
	public function is_cli() {
		return isset( $_SERVER['argv'], $_SERVER['argc'] ) || $_SERVER['argc']; // phpcs:ignore
	}

	/**
	 * Get the available execution functions.
	 *
	 * @since 1.8.0
	 *
	 * @return array An array of function names.
	 */
	public function get_execution_functions() {
		// If the array already has elements, then return the array.
		if ( $this->available_exec_functions ) {
			return $this->available_exec_functions;
		}

		// Get the PHP disable_functions list.
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		// Make an array of execution functions.
		$exec_functions = array(
			'popen',
			'proc_open',
			'exec',
			'shell_exec',
			'passthru',
			'system',
		);

		// Iterate through the array and remove disabled functions.
		foreach ( $exec_functions as $exec_function ) {
			if ( in_array( $exec_function, $disabled, true ) ) {
				unset( $exec_functions[ $exec_function ] );
			}
		}

		// Save the array of execution functions.
		$this->available_exec_functions = $exec_functions;

		return $exec_functions;
	}

	/**
	 * Execute a system command using an array of execution functions.
	 *
	 * @since 1.8.0
	 *
	 * @param  string $command    A command string to be executed.
	 * @param  bool   $success    Success or failure of the operation, passed back.
	 * @param  int    $return_var If present, the return_var, passed back.
	 * @return string|bool Returns the command output or FALSE on error.
	 */
	public function execute_command( $command, &$success = false, &$return_var = 0 ) {
		// If no command was passed, then fail.
		if ( empty( $command ) ) {
			return false;
		}

		$exec_functions = $this->get_execution_functions();
		$success        = false;

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions, WordPress.WP.AlternativeFunctions

		// Test getting output using available execution functions, until one is successful.
		foreach ( $exec_functions as $exec_function ) {
			switch ( $exec_function ) {
				case 'exec':
					exec( $command, $out, $return_var );

					// If the exit status is int(0), then it was successful.
					if ( 0 === $return_var ) {
						$output = implode( PHP_EOL, $out );

						$success = true;

						break 2;
					} else {
						$output = false;
					}

					break 2;

				case 'passthru':
					// If output buffering is enabled, then use passthru.
					if ( ob_start() ) {
						passthru( $command, $return_var );

						// Get current buffer contents and delete current output buffer.
						$output = ob_get_clean();

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							$output = false;
						}
					}

					break 2;

				case 'popen':
					$handle = popen( $command, 'r' );

					$output = fread( $handle, 4096 );

					/*
					 * If handle is a valid resource, then check for success.
					 */
					if ( false !== $handle ) {
						// Close the process handle and get the return status.
						$return_var = pclose( $handle );

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							// Bad exit status code (non-zero).
							$output = false;
						}
					} else {
						// Failed to create a process handle.
						$output = false;
					}

					break 2;

				case 'proc_open':
					// Create the descriptor spec array.
					$descriptorspec = array(
						0 => array(
							'pipe',
							'r',
						),
						1 => array(
							'pipe',
							'w',
						),
						2 => array(
							'pipe',
							'w',
						),
					);

					// Open a process handle.
					$handle = proc_open( $command, $descriptorspec, $pipes );

					if ( is_resource( $handle ) ) {
						// Close unused pipes[0].
						fclose( $pipes[0] );

						// Read output from pipes[1].
						$output = stream_get_contents( $pipes[1] );

						// Close pipes[1].
						fclose( $pipes[1] );

						// Close unused pipes[0].
						fclose( $pipes[2] );

						// Close the process handle and get the return status.
						$return_var = proc_close( $handle );

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							$output = false;
						}
					}

					break 2;

				case 'shell_exec':
					$output = shell_exec( $command );

					if ( false === strpos( $output, 'command not found' ) ) {
						$success = true;

						break 2;
					} else {
						$output = false;
					}

					break 2;

				case 'system':
					// If output buffering is enabled, then use system.
					if ( ob_start() ) {
						system( $command, $return_var );

						// Get current buffer contents and delete current output buffer.
						$output = ob_get_clean();

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							$output = false;
						}
					}

					break 2;

				default:
					break;
			}
		}

		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions, WordPress.WP.AlternativeFunctions

		if ( ! $success ) {
			return false;
		}

		return trim( $output );
	}
}
