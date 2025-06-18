<?php

/*
Plugin Name: RRZE FAUdir
Plugin URI: https://github.com/RRZE-Webteam/rrze-faudir
Description: Plugin for displaying the FAU person and institution directory on websites.
Version: 2.3.12
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

// Define plugin constants
define('RRZE_PLUGIN_FILE', __FILE__);
define('RRZE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RRZE_PLUGIN_URL', plugin_dir_url(__FILE__));

use RRZE\FAUdir\Main;


// Check if the function exists before using it
if (! function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Constants
const RRZE_PHP_VERSION = '8.2';
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

/**
 * Hauptinitialisierung des Plugins (wird nach plugins_loaded aufgerufen)
 */
function loaded(): void {
    // Übersetzungen laden
    load_plugin_textdomain('rrze-faudir', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!rrze_faudir_system_requirements()) {
        return;
    }

    // CPT und Einstellungen laden
    require_once plugin_dir_path(__FILE__) . 'includes/custom-post-type/custom-post-type.php';
    require_once plugin_dir_path(__FILE__) . 'includes/Settings.php';

    $main = new Main(RRZE_PLUGIN_FILE);
    $main->onLoaded();

    // AJAX-Hooks
    add_action('wp_ajax_rrze_faudir_search_contacts', __NAMESPACE__ . '\\rrze_faudir_search_contacts');
    add_action('wp_ajax_nopriv_rrze_faudir_search_contacts', __NAMESPACE__ . '\\rrze_faudir_search_contacts');
}


// System requirements check
function rrze_faudir_system_requirements(): bool {
    $error = '';

    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        $error = sprintf(
            __('Your PHP version (%1$s) is outdated. Please upgrade to PHP %2$s or higher.', 'rrze-faudir'),
            PHP_VERSION,
            RRZE_PHP_VERSION
        );
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        $error = sprintf(
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

/**
 * AJAX-Handler für Kontakt-Suche
 */
function rrze_faudir_search_contacts(): void {
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $identifier = sanitize_text_field(wp_unslash($_POST['identifier'] ?? ''));

    global $wpdb;
    $contacts = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}contacts WHERE identifier LIKE %s",
        '%' . $wpdb->esc_like($identifier) . '%'
    ));

    if (!empty($contacts)) {
        $formatted_contacts = array_map(function ($contact) {
            return [
                'name'            => $contact->name,
                'identifier'      => $contact->identifier,
                'additional_info' => $contact->additional_info
            ];
        }, $contacts);
        wp_send_json_success($formatted_contacts);
    } else {
        wp_send_json_error(__('No contacts found with the provided identifier.', 'rrze-faudir'));
    }
}

