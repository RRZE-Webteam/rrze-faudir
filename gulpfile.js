import gulp from 'gulp';
import gulpSass from 'gulp-sass';
import * as sassCompiler from 'sass';
import sourcemaps from 'gulp-sourcemaps';
import csso from 'gulp-csso';
import concat from 'gulp-concat';
import wpPot from 'gulp-wp-pot';
import svgmin from 'gulp-svgmin';
import touch from 'gulp-touch-cmd';
import header from 'gulp-header';
import { readFileSync } from 'fs';

const { src, dest, series, watch } = gulp;
const sass = gulpSass(sassCompiler);

// Options for compiling Dart SASS in Dev Versions
const sassDevOptions = {
    indentWidth: 4, 
    quietDeps: true, 
    precision: 3, 
    sourceComments: true,  
    silenceDeprecations: ['legacy-js-api'] 
};

const sassProdOptions = {
    quietDeps: true, 
    outputStyle: 'compressed',
    silenceDeprecations: ['legacy-js-api'] 
};


// Lade die package.json-Datei und füge dessen Werte in die 
// globale Variable info ein.
let info;

function initialize() {
    const data = readFileSync('./package.json', 'utf-8');
    info = JSON.parse(data);
    console.log("Info erfolgreich geladen:", info); // Debug-Ausgabe
}

// Initialisierung direkt beim Laden des Skripts
initialize();

/**
 * Template for banner to add to file headers
 */

let banner = [
  "/*!",
  "Plugin Name: <%= info.name %>",
  "Plugin URI: <%= info.repository.url %>",
  "Version: <%= info.version %>",
  "Requires at least: <%= info.compatibility.wprequires %>",
  "Tested up to: <%= info.compatibility.wptestedup %>",
  "Requires PHP: <%= info.compatibility.phprequires %>",
  "Description: <%= info.description %>",
  "GitHub Issue URL: <%= info.repository.issues %>",
  "Author: <%= info.author.name %>",
  "Author URI: <%= info.author.url %>",
  "License: <%= info.license %>",
  "License URI: <%= info.licenseurl %>",
  "Text Domain: <%= info.textdomain %>",
  "*/",
].join("\n");

let smallcssbanner = [
    "/*!",
    "* Editor CSS for Theme:",
    "* Plugin Name: <%= info.name %>",
    "* Version: <%= info.version %>",
    "* GitHub Issue URL: <%= info.repository.issues %>",
    "*/",
  ].join("\n");




/*
 * SASS and Autoprefix CSS Files, without clean
 */
export function devcss() {
    return src([info.source.sass + "rrze-faudir.scss"])
      .pipe(header(banner, { info: info }))
      .pipe(sass(sassDevOptions).on("error", sass.logError))
      .pipe(dest(info.target.css))
      .pipe(touch());
}

/*
 * Compile all styles with SASS and clean them up
 */
export function css() {
    return src([info.source.sass + "rrze-faudir.scss"])
	.pipe(header(banner, { info: info }))
	.pipe(sass(sassProdOptions).on("error", sass.logError))
	.pipe(dest(info.target.css))
	.pipe(touch());
}


// JavaScript-Task für die Hauptdateien
export function jsMain() {
  return src(['src/js/*.js', '!src/js/fau_dir_block.js'])
    .pipe(concat('rrze-faudir.js'))
    .pipe(dest('assets/js'));
}

// JavaScript-Task für Admin-spezifische Dateien
export function jsAdmin() {
  return src('src/js/admin/*.js') // Pfad zu Admin-JS
    .pipe(concat('admin.js')) // Kombinierte Datei
    .pipe(dest('assets/js')); // Zielordner
}

// Task zum Generieren der .pot-Datei
export function makepot() {
  return src(['**/*.php', '!vendor/**']) // PHP-Dateien, Vendor ausschließen
    .pipe(
      wpPot({
        domain: 'rrze-faudir', // Text-Domain
        package: 'rrze-faudir', // Plugin-Name
        bugReport: 'https://github.com/RRZE-Webteam/rrze-faudir/issues', // Support-URL
        lastTranslator: 'Webteam RRZE <webmaster@fau.de>', // Letzter Übersetzer
        team: 'Webteam RRZE <webmaster@fau.de>', // Übersetzungsteam
      })
    )
    .pipe(dest('languages/rrze-faudir.pot')); // Zielordner der .pot-Datei
}

// Task zur SVG-Optimierung
export function svg() {
  return src('src/svg/**/*.svg')
    .pipe(svgmin()) // SVG-Optimierung
    .pipe(dest('dist/svg')); // Zielordner
}

// Watch-Task zur Überwachung von Änderungen
export function watchTask() {
  watch('src/scss/*.scss', css); // Überwachung von SCSS-Dateien
  watch('src/js/*.js', jsMain); // Überwachung von JS-Dateien
}

// Standard-Task
export default series(css, jsMain, jsAdmin);
    
    