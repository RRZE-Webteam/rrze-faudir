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

// Define the JS task for main JavaScript files
gulp.task('js-main', function() {
  return gulp.src('src/js/*.js')
    .pipe(concat('rrze-faudir.js'))
    .pipe(gulp.dest('assets/js'));
});

// Define the JS task for admin-specific JavaScript files
gulp.task('js-admin', function() {
  return gulp.src('src/js/admin/*.js')  // Adjust the path as needed
    .pipe(concat('admin.js')) // Output will be admin.js
    .pipe(gulp.dest('assets/js'));
});

gulp.task('watch', function() {
  gulp.watch('src/scss/*.scss', gulp.series('css'));
  gulp.watch('src/js/*.js', gulp.series('js-main'));
});
// Define the default task that runs all tasks in series
gulp.task('default', gulp.series('css', 'js-main', 'js-admin'));