import gulp from 'gulp';
import gulpSass from 'gulp-sass';
import * as sassCompiler from 'sass';
import sourcemaps from 'gulp-sourcemaps';
import concat from 'gulp-concat';
import wpPot from 'gulp-wp-pot';
import touch from 'gulp-touch-cmd';
import header from 'gulp-header';
import { readFileSync } from 'fs';
import uglify from 'gulp-uglify';
import bump from 'gulp-bump';
import semver from 'semver';
import gulpFile from 'gulp-file';
import i18next from 'gulp-i18next-conv';
import through2 from 'through2';
import { exec } from 'child_process';

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
 //   console.log("Info erfolgreich geladen:", info); // Debug-Ausgabe
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
  "*/\n",
].join("\n");

let smallcssbanner = [
    "/*!",
    "* Editor CSS for Theme:",
    "* Plugin Name: <%= info.name %>",
    "* Version: <%= info.version %>",
    "* GitHub Issue URL: <%= info.repository.issues %>",
    "*/\n",
  ].join("\n");

let jsbanner = [
    "/*",    
    "* JavaScript Definitions for: ",
    "* Plugin: <%= info.name %>",
    "* Version: <%= info.version %>",
    "*/\n",
  ].join("\n");


let readmebanner = [
    "=== Plugin Name ===",
    "Plugin Name: <%= info.name %>",
    "Plugin URI: <%= info.repository.url %>",
    "Version: <%= info.version %>",
    "Requires at least: <%= info.compatibility.wprequires %>",
    "Tested up to: <%= info.compatibility.wptestedup %>",
    "Requires PHP: <%= info.compatibility.phprequires %>",
    "GitHub Issue URL: <%= info.repository.issues %>",
    "Author: <%= info.author.name %>",
    "Author URI: <%= info.author.url %>",
    "License: <%= info.license %>",
    "License URI: <%= info.licenseurl %>",
    "Text Domain: <%= info.textdomain %>",
    "",
    "== Description ==",
    "",
    "<%= info.description %>\n"    
].join("\n");

/*
 * Erstelle CSS für Dev-Versionen: Ohne Minify mit Sourcemap
 */
export function devcss() {
    return src([info.source.sass + "rrze-faudir.scss"])      
      	.pipe(sourcemaps.init())
	.pipe(sass(sassDevOptions).on("error", sass.logError))
	.pipe(sourcemaps.write())
	.pipe(header(banner, { info: info }))
	.pipe(dest(info.target.css))
	.pipe(touch());
}

/*
 * Compile all styles with SASS and clean them up, with Minify 
 */
export function css() {
    return src([info.source.sass + "rrze-faudir.scss"])	
	.pipe(sass(sassProdOptions).on("error", sass.logError))
	.pipe(header(banner, { info: info }))
	.pipe(dest(info.target.css))
	.pipe(touch());
}


// JavaScript-Task für die Hauptdateien: Prod mit Minifizierung
export function jsMain() {
  return src([info.source.js + '*.js', '!src/js/fau_dir_block.js'])
    .pipe(concat(info.target.mainjs))
    .pipe(uglify())
    .pipe(header(jsbanner, { info: info }))
    .pipe(dest(info.target.js))
    .pipe(touch());
}
// JavaScript-Task für die Hauptdateien: Dev ohne Minifizierung
export function devjsMain() {
  return src([info.source.js + '*.js', '!src/js/fau_dir_block.js'])
    .pipe(concat(info.target.mainjs))
    .pipe(header(jsbanner, { info: info }))
    .pipe(dest(info.target.js))
    .pipe(touch());
}

// JavaScript-Task für Admin-spezifische Dateien
export function jsAdmin() {
  return src(info.source.js +'admin/*.js') // Pfad zu Admin-JS
    .pipe(concat(info.target.adminjs)) // Kombinierte Datei
    .pipe(uglify())
    .pipe(header(jsbanner, { info: info }))
    .pipe(dest(info.target.js))
    .pipe(touch()); 
}

// Task zum Generieren der .pot-Datei
export function makepot() {
  return src(['**/*.php', '**/*.js', '!vendor/**']) // PHP-Dateien, Vendor ausschließen
    .pipe(
      wpPot({
        domain: info.textdomain, // Text-Domain
        package: info.name, // Plugin-Name
        bugReport: info.repository.issues, // Support-URL
        lastTranslator: info.author.name, // Letzter Übersetzer
        team: info.author.name, // Übersetzungsteam
      })
    )
    .pipe(dest('languages/' + info.textdomain + '.pot')); // Zielordner der .pot-Datei
}


// Funktion zur Anpassung der JSON-Struktur an das WordPress-Format
function formatForWordPress() {
    return through2.obj(function (file, _, cb) {
        if (file.isBuffer()) {
            let jsonContent = JSON.parse(file.contents.toString());

            // WordPress-kompatible Struktur
            let wpJson = {
                locale_data: {
                    messages: {
                        "": {
                            "domain": "rrze-faudir"
                        }
                    }
                }
            };

            // JSON-Schlüssel hinzufügen
            Object.keys(jsonContent).forEach(key => {
                wpJson.locale_data.messages[key] = [jsonContent[key]];
            });

            // Aktualisierte JSON-Struktur speichern
            file.contents = Buffer.from(JSON.stringify(wpJson, null, 2));
        }
        cb(null, file);
    });
}


// Funktion zur Konvertierung von .po nach .json
export function convertPoToJson() {
    return src("languages/*.po")
        .pipe(i18next())
	.pipe(formatForWordPress())
        .pipe(dest('languages/'))
	.pipe(touch());
};



// Watch-Task zur Überwachung von Änderungen
// Der Watch-Task wird zur Entwicklung verwendet, daher hier nur 
// Dev-Versionen erstellen
export function watchTask() {
  watch('src/scss/*.scss', devcss); 
  watch('src/js/*.js', devjsMain); 
}

/*
 * Build Block Editor Block by using wordpress-scripts in faudir-block directory
 */
export function buildFaudirBlock(cb) {
    exec('npm run build:block', { cwd: './faudir-block' }, (err, stdout, stderr) => {
        console.log(stdout); 
        console.error(stderr); 
        cb(err); 
    });
}

/*
 * Update Version on Patch-Level
 */
function upversionpatch() {
    var newVer = semver.inc(info.version, "patch");
    return src([
      "./package.json",
      "./" + info.main,
    ])
    .pipe(
	bump({
	  version: newVer,
	})
    )
    .pipe(dest("./"))
    .pipe(touch())
    .on("end", () => {
      // Aktualisiere die globale Variable info.version
      info.version = newVer;

      console.log(`Patch Version erfolgreich hochgezählt: ${newVer}`);
    });
}

/*
 * Update DEV Version on prerelease level.
 */
function devversion() {
    var newVer = semver.inc(info.version, "prerelease");
    return src([
      "./package.json",
      "./" + info.main,
    ])
    .pipe(
      bump({
        version: newVer,
      })
    )
    .pipe(dest("./"))
    .pipe(touch())
    .on("end", () => {
      // Aktualisiere die globale Variable info.version
      info.version = newVer;

      console.log(`Prerelease Version erfolgreich hochgezählt: ${newVer}`);
    });
}

export function createReadme() {
    return gulpFile('readme.txt', '', { src: true }) // Erstelle eine leere Datei
	.pipe(header(readmebanner, { info: info }))
        .pipe(dest('./')) // Speichere die Datei im aktuellen Verzeichnis
	.pipe(touch())
	.on('end', () => {
            console.log('readme.txt wurde erfolgreich erstellt.');
        });
}


export const pot = series(makepot);

// Nur den Block neu bauen
export const buildBlock = series(buildFaudirBlock);

// Standard-Task
export default series(upversionpatch, css, jsMain, jsAdmin);
    
// Production
export const build = series(upversionpatch, css, jsMain, jsAdmin, createReadme, buildFaudirBlock);

// Dev Version
export const dev = series(devversion, devcss, devjsMain, jsAdmin, createReadme);


