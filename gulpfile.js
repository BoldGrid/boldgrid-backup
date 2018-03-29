var gulp = require( 'gulp' ),
	readme = require( 'gulp-readme-to-markdown' );

gulp.task( 'readme', function() {
	gulp
		.src( [ 'readme.txt' ] )
		.pipe( readme() )
		.pipe( gulp.dest( '.' ) );
} );

gulp.task( 'default', [ 'readme' ] );
