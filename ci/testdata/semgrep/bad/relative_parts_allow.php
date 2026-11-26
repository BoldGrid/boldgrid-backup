<?php
private function safe_relative_parts( $relative_path ) {
	$safe = array();
	foreach ( explode( '/', $relative_path ) as $part ) {
		if ( '' === $part || '.' === $part ) {
			continue;
		}
		$safe[] = $part;
	}

	return $safe;
}
