<?php
/**
 * File: class-boldgrid-backup-admin-cli.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Cli
 *
 * @since 1.8.0
 */
class Boldgrid_Backup_Admin_Cli {
	/**
	 * Available execution functions.
	 *
	 * @since 1.8.0
	 * @access private
	 * @static
	 * @var array
	 */
	private static $available_exec_functions;

	/**
	 * Get the available execution functions.
	 *
	 * @since 1.8.0
	 * @static
	 *
	 * @see Boldgrid_Backup_Admin_Test::is_php_safemode()
	 *
	 * @return array
	 */
	public static function get_execution_functions() {
		// If the array already has elements, then return the array.
		if ( ! is_null( self::$available_exec_functions ) ) {
			return self::$available_exec_functions;
		}

		// If PHP is in safe mode, then return an empty array.
		if ( ini_get( 'safe_mode' ) ) {
			return array();
		}

		// Get the PHP disable_functions list.
		$disabled = explode( ',', ini_get( 'disable_functions' ) );
		array_walk( $disabled, function( &$function ) {
			$function = trim( $function );
		} );

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
		foreach ( $exec_functions as $key => $exec_function ) {
			if ( in_array( $exec_function, $disabled, true ) ) {
				unset( $exec_functions[ $key ] );
			}
		}

		// Save the array of execution functions.
		self::$available_exec_functions = $exec_functions;

		return $exec_functions;
	}

	/**
	 * Call an execute command.
	 *
	 * To be used by Boldgrid_Backup_Admin_Core::execute_command() and some others.
	 *
	 * @since 1.8.0
	 *
	 * @param  string $command                  A command string to be executed.
	 * @param  bool   $success                  Success or failure of the operation, passed back.
	 * @param  int    $return_var               If present, the return_var, passed back.
	 * @return string Returns the command output
	 */
	public static function call_command( $command, &$success = false, &$return_var = 0 ) {
		$success = false;
		$output  = false;

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions, WordPress.WP.AlternativeFunctions

		// Test getting output using available execution functions, until one is successful.
		$available_exec_functions = self::get_execution_functions();

		foreach ( $available_exec_functions as $exec_function ) {
			switch ( $exec_function ) {
				case 'exec':
					exec( $command, $out, $return_var );

					// If the exit status is int(0), then it was successful.
					if ( 0 === $return_var ) {
						$output = implode( PHP_EOL, $out );

						$success = true;

						break 2;
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
						}
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
						}
					}

					break 2;

				case 'shell_exec':
					$output = shell_exec( $command );

					if ( false === strpos( $output, 'command not found' ) ) {
						$success = true;

						break 2;
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
						}
					}

					break 2;

				default:
					break;
			}
		}

		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions, WordPress.WP.AlternativeFunctions

		if ( ! empty( $output ) ) {
			$output = trim( $output );
		}

		return $output;
	}
}
