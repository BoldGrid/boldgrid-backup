<?php
private function safe_relative_parts( $relative_path ) {
	if ( ! is_string( $relative_path ) || '' === $relative_path ) {
		return false;
	}

	$safe = array();
	foreach ( explode( '/', $relative_path ) as $part ) {
		if ( '' === $part || '.' === $part ) {
			continue;
		}
		if ( '..' === $part ) {
			return false;
		}
		$safe[] = $part;
	}

	return $safe;
}
