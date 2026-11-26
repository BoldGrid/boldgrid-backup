<?php
/**
 * Require a parent-segment rejection in safe_relative_parts().
 *
 * @package Boldgrid_Backup
 */

namespace Boldgrid_Backup\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures safe_relative_parts() still names the parent-directory segment.
 */
class RequireParentSegmentRejectSniff implements Sniff {
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
		if ( 'class-boldgrid-backup-admin-migrate-util.php' !== $filename && 0 !== strpos( $filename, 'relative_parts_' ) ) {
			return;
		}

		$name = $phpcs_file->getDeclarationName( $stack_ptr );
		if ( 'safe_relative_parts' !== $name ) {
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
			if ( "'..'" === $tokens[ $i ]['content'] || '".."' === $tokens[ $i ]['content'] ) {
				return;
			}
		}

		$phpcs_file->addError(
			'safe_relative_parts() must reject a parent-directory segment.',
			$stack_ptr,
			'Missing'
		);
	}
}
