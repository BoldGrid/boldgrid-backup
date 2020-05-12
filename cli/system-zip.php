<?php

$args     = $argv;
$abspath  = $args[1];
$filename = $args[2];
$filelist = file_get_contents( $args[3] );
$index    = $args[4];
ini_set( 'max_execution_time', 900 );

$filelist_array  = explode( PHP_EOL, $filelist );
$filelist_chunks = array_chunk( $filelist_array, 100 );
$filelist_chunk  = $filelist_chunks[ $index ];
$add_file_string = implode( ' ', $filelist_chunk );
if ( function_exists( pcntl_fork() ) ) {
	echo ( "PCNTL EXISTS" );
} else {
	echo ( "PCNTL NOT EXIST" );
}
$result = exec( 'cd ' . $abspath . '; zip -6 -g -q ' . $filename . ' ' . $add_file_string . ' 2>&1' );

//echo json_encode( 'Chunk No ' . $index . ': ' . $result );

exit();
