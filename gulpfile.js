var gulp = require( 'gulp' );

gulp.task( 'build', function() {
	gulp.src( [ 'node_modules/clipboard/dist/clipboard.min.js' ] ).pipe( gulp.dest( './build/' ) );
} );

gulp.task( 'default', [ 'build' ] );
