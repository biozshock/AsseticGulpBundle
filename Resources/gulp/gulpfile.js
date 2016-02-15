/*
 required modules to run this gulpfile
 maybe some of them are not actually that required :)

 sudo npm install -g gulp-less gulp-logger gulp gulp-cache gulp-concat gulp-debug \
 gulp-filter gulp-load-plugins gulp-sourcemaps gulp-uglify gulp-util gulp-changed \
 gulp-if merge-stream path del gulp-rewrite-css gulp-cssnano gulp-watch gulp-plumber \
 gulp-watch-less gulp-watch gulp-notify

 */

/*
 dev launch, no minify and strip:
 $ gulp

 prod launch to strip, minify and stuff
 $ gulp --env=prod

 to output processed files
 $ gulp --debug=true

 to output sourcemaps
 $ gulp --sourcemaps=true
 */
"use strict";
var gulp = require('gulp'),
    gulpDebug = require('gulp-debug'),
    concat = require('gulp-concat'),
    gutil = require('gulp-util'),
    less = require('gulp-less'),
    gulpif = require('gulp-if'),
    cssnano = require('gulp-cssnano'),
    sourcemaps = require('gulp-sourcemaps'),
    uglify = require('gulp-uglify'),
    merge = require('merge-stream'),
    rewriteCSS = require('gulp-rewrite-css'),

    plumber = require('gulp-plumber'),
    watch = require('gulp-watch'),

    gnotify = require('gulp-notify'),

    config = require('./gulp_config.json'),

    del = require('del'),

    env = gutil.env.env,
    debug = gutil.env.debug,
    generateSourcemaps = gutil.env.sourcemaps,

    onError = function (err) {
        gutil.beep();
        if (debug) {
            gnotify("Something is Wrong!");
        }
        console.log(err.toString());
        this.emit('end');
    };

var gulpIt = function (resource, watchTask) {

    var type = resource.types[0],
        destination = resource.destination.path,
        gulpy = gulp.src(resource.sources),
        file = resource.destination.file;

    gulpy.pipe(plumber({errorHandler: onError}));

    if (watchTask) {
        gulpy.pipe(watch(resource.sources, {usePolling: true}, function(file) {
            return gulpIt(resource, false);
        }));
    }

    if (debug) {
        gulpy.pipe(gulpDebug());
    }

    if (type == 'css' || type == 'less') {
        gulpy
            .pipe(gulpif(generateSourcemaps, sourcemaps.init()))
            .pipe(gulpif(/[.]less/, less().on('error', onError)))
            .on('error', onError)
            .pipe(rewriteCSS({destination: destination}))
            .pipe(concat(file))
            .pipe(gulpif(env === 'prod', cssnano()))
            .pipe(gulpif(generateSourcemaps, sourcemaps.write('.')))
            .pipe(gulp.dest(destination));

    } else if (type === 'js') {
        gulpy
            .pipe(gulpif(env === 'prod', uglify()))
            .pipe(concat(file))
            .pipe(gulp.dest(destination));
    }

    if (debug) {
        console.log(destination, file);
        gulpy.pipe(gnotify({ message: "finished.", onLast: true }));
    }

    return gulpy.on('error', onError);

};



gulp.task('clean', function() {
    if (env !== 'prod') {
        return [];
    }
    return del(['web/js/*', 'web/css/*']);
});

gulp.task('default', ['clean'], function() {

    console.log('Dumping assets in ' + (env === 'prod' ? 'production' : 'development') + ' mode');

    var tasks = config.map(function(files) {
        return gulpIt(files, false);
    });

    return merge(tasks);

});

gulp.task('watch', function(done) {

    console.log('Watching assets in ' + (env === 'prod' ? 'production' : 'development') + ' mode');

    var tasks = config.map(function(resource) {
        return gulpIt(resource, true);
    });

    return merge(tasks);

});
