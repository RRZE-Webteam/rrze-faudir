const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const csso = require('gulp-csso'); // Corrected minifyCSS to csso to match the correct variable name
const concat = require('gulp-concat');

// Define the CSS task
gulp.task('css', function() {
  return gulp.src('src/scss/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(csso())  // Corrected to match the variable name
    .pipe(concat('rrze-faudir.css'))
    .pipe(gulp.dest('assets/css'));
});

// Define the JS task
gulp.task('js', function() {
  return gulp.src('src/js/*.js')
    .pipe(concat('rrze-faudir.js'))
    .pipe(gulp.dest('assets/js'));
});

// Define the default task that runs both 'css' and 'js' tasks in series
gulp.task('default', gulp.series('css', 'js'));
