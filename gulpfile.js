var gulp = require( 'gulp' ),
	readme = require( 'gulp-readme-to-markdown' );

gulp.task( 'readme', function() {
	gulp
		.src( [ 'readme.txt' ] )
		.pipe( readme() )
		.pipe( gulp.dest( '.' ) );
} );

gulp.task( 'build', function() {
	gulp.src( [ 'node_modules/clipboard/dist/clipboard.min.js' ] ).pipe( gulp.dest( './build' ) );
} );

gulp.task( 'default', [ 'readme', 'build' ] );
