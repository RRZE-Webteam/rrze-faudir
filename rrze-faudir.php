<?php

/*
Plugin Name: RRZE FAUdir
Plugin URI: https://github.com/RRZE-Webteam/rrze-faudir
Description: Plugin for displaying the FAU person and institution directory on websites.
Version: 2.6.26
Author: RRZE Webteam
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: rrze-faudir
Domain Path: /languages
Requires at least: 6.7
Requires PHP: 8.2
*/


namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Main;
use RRZE\FAUdir\CPT;



// Check if the function exists before using it
if (! function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Constants
const RRZE_PHP_VERSION = '8.3';
const RRZE_WP_VERSION = '6.7';

/**
 * SPL Autoloader (PSR-4).
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $baseDir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});



// Hauptladepunkt
add_action('plugins_loaded', __NAMESPACE__ . '\\loaded');


/*
 * Singleton pattern for initializing and accessing the main plugin instance.
 * This method ensures that only one instance of the Plugin class is created and returned.
 *
 * @return Plugin The main instance of the Plugin class.
 */
function plugin() {
    // Declare a static variable to hold the instance.
    static $instance;

    if (null === $instance) {
        // Add a new instance of the Plugin class, passing the current file (__FILE__) as a parameter.
        $instance = new Plugin(__FILE__);
    }

    // Return the main instance of the Plugin class.
    return $instance;
}

/**
 * Hauptinitialisierung des Plugins (wird nach plugins_loaded aufgerufen)
 */
function loaded(): void {
    // Übersetzungen laden
    load_plugin_textdomain('rrze-faudir', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!rrze_faudir_system_requirements()) {
        return;
    }

     // Trigger the 'loaded' method of the main plugin instance.
    plugin()->loaded();

    $main = new Main();
    $main->onLoaded();

  
}


// System requirements check
function rrze_faudir_system_requirements(): bool {
    $error = '';

    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        $error = sprintf(
           /* translators: 1: PHP  version number, 2: required PHP Version. */
            __('Your PHP version (%1$s) is outdated. Please upgrade to PHP %2$s or higher.', 'rrze-faudir'),
            PHP_VERSION,
            RRZE_PHP_VERSION
        );
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        $error = sprintf(
           /* translators: 1: WordPress version number, 2: Required WP Version. */
            __('Your WordPress version (%1$s) is outdated. Please upgrade to WordPress %2$s or higher.', 'rrze-faudir'),
            $GLOBALS['wp_version'],
            RRZE_WP_VERSION
        );
    }

    if (!empty($error)) {
        add_action('admin_notices', function () use ($error) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($error));
        });
        return false;
    }

    return true;
}
