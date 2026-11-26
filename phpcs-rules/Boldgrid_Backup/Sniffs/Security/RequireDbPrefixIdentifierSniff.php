<?php
/**
 * Require the database-prefix identifier pattern.
 *
 * @package Boldgrid_Backup
 */

namespace Boldgrid_Backup\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures is_valid_db_prefix() keeps the letter, digit, and underscore pattern.
 */
class RequireDbPrefixIdentifierSniff implements Sniff {
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
		if ( 'class-boldgrid-backup-admin-migrate-util.php' !== $filename && 0 !== strpos( $filename, 'db_prefix_' ) ) {
			return;
		}

		$name = $phpcs_file->getDeclarationName( $stack_ptr );
		if ( 'is_valid_db_prefix' !== $name ) {
			return;
		}

		$tokens = $phpcs_file->getTokens();
		if ( ! isset( $tokens[ $stack_ptr ]['scope_opener'], $tokens[ $stack_ptr ]['scope_closer'] ) ) {
			return;
		}

		$start = $tokens[ $stack_ptr ]['scope_opener'];
		$end   = $tokens[ $stack_ptr ]['scope_closer'];
		for ( $i = $start; $i < $end; $i++ ) {
			if ( T_CONSTANT_ENCAPSED_STRING !== $tokens[ $i ]['code'] ) {
				continue;
			}
			if ( "'/\\A[A-Za-z0-9_]+\\z/'" === $tokens[ $i ]['content'] ) {
				return;
			}
		}

		$phpcs_file->addError(
			'is_valid_db_prefix() must keep the letter, digit, and underscore pattern.',
			$stack_ptr,
			'Missing'
		);
	}
}
