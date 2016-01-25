/*
required modules to run this gulpfile
maybe some of them are not actually that required :)

sudo npm install -g gulp-less gulp-logger gulp gulp-cache gulp-concat gulp-debug \
 gulp-filter gulp-load-plugins gulp-sourcemaps gulp-uglify gulp-util gulp-changed \
 gulp-if merge-stream path del gulp-rewrite-css gulp-cssnano

 */

/*
dev launch, no minify and strip:
$ gulp

prod launch to strip, minify and stuff
$ gulp --env=prod

to output processed files
$ gulp --debug=true
 */

var gulp = require('gulp'),
    concat = require('gulp-concat'),
    gutil = require('gulp-util'),
    less = require('gulp-less'),
    gulpif = require('gulp-if'),
    cssnano = require('gulp-cssnano'),
    sourcemaps = require('gulp-sourcemaps'),
    uglify = require('gulp-uglify'),
    changed = require('gulp-changed'),
    merge = require('merge-stream'),
    rewriteCSS = require('gulp-rewrite-css'),
    config = require('./gulp_config.json'),

    del = require('del'),

    env = gutil.env.env,
    debug = gutil.env.debug;

var gulpIt = function (resource) {

    var type = resource.types[0],
        destination = resource.destination.path,
        gulpy = gulp.src(resource.sources)
        // the `changed` task needs to know the destination directory
        // upfront to be able to figure out which files changed
        .pipe(changed(destination))
        ;

    if (type == 'css' || type == 'less') {
        gulpy
            .pipe(gulpif(env === 'prod', sourcemaps.init()))
            .pipe(gulpif(/[.]less/, less()))
            .pipe(rewriteCSS({destination: destination}))
            .pipe(concat(resource.destination.file))
            .pipe(gulpif(env === 'prod', cssnano()))
            .pipe(gulpif(env == 'prod', sourcemaps.write('.')))
            .pipe(gulp.dest(destination));

    } else if (type === 'js') {
        gulpy
            .pipe(gulpif(env === 'prod', sourcemaps.init()))
            .pipe(gulpif(env === 'prod', uglify()))
            .pipe(concat(resource.destination.file))
            .pipe(gulpif(env === 'prod', sourcemaps.write('.')))
            .pipe(gulp.dest(destination));
    }

    if (debug) {
        console.log(destination, resource.destination.file);
    }

    return gulpy;

};

gulp.task('clean', function() {
    if (env !== 'prod') {
        return [];
    }
    return del(['web/js/*', 'web/css/*']);
});

gulp.task('default', ['clean'], function() {

    console.log('Dumping assets in ' + (env === 'prod' ? 'production' : 'development') + ' mode');

    var tasks = config.map(gulpIt);

    return merge(tasks);

});

