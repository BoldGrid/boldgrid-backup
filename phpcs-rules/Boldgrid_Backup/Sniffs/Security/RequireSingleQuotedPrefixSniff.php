<?php
/**
 * Require a single-quoted table prefix assignment.
 *
 * @package Boldgrid_Backup
 */

namespace Boldgrid_Backup\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures update_table_prefix() writes a single-quoted $table_prefix assignment.
 */
class RequireSingleQuotedPrefixSniff implements Sniff {
	/**
	 * @return array
	 */
	public function register() {
		return array( T_FUNCTION );
	}

	/**
	 * @param File $phpcs_file File.
	 * @param int  $stack_ptr  Pointer.
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$filename = basename( $phpcs_file->getFilename() );
		if ( 'class-boldgrid-backup-admin-migrate-restore.php' !== $filename && 0 !== strpos( $filename, 'prefix_assign_' ) ) {
			return;
		}

		$name = $phpcs_file->getDeclarationName( $stack_ptr );
		if ( 'update_table_prefix' !== $name ) {
			return;
		}

		$tokens = $phpcs_file->getTokens();
		if ( ! isset( $tokens[ $stack_ptr ]['scope_opener'], $tokens[ $stack_ptr ]['scope_closer'] ) ) {
			return;
		}

		$needle = '$table_prefix = \\\'';
		$start  = $tokens[ $stack_ptr ]['scope_opener'];
		$end    = $tokens[ $stack_ptr ]['scope_closer'];
		for ( $i = $start; $i < $end; $i++ ) {
			if ( T_CONSTANT_ENCAPSED_STRING !== $tokens[ $i ]['code'] ) {
				continue;
			}
			if ( false !== strpos( $tokens[ $i ]['content'], $needle ) ) {
				return;
			}
		}

		$phpcs_file->addError(
			'update_table_prefix() must write a single-quoted $table_prefix assignment.',
			$stack_ptr,
			'Missing'
		);
	}
}
