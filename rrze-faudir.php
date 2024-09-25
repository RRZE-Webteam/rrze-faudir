<?php
/*
Plugin Name: RRZE FAU Directory
Plugin URI: https://github.com/RRZE-Webteam/rrze-faudir
Description: Plugin for displaying the FAU person and institution directory on websites.
Version: 1.0.0
Author: RRZE Webteam
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: rrze-faudir
Domain Path: /languages
Requires at least: 6.5
Requires PHP: 8.2
*/

defined('ABSPATH') || exit;

// Constants
const RRZE_PHP_VERSION = '8.2';
const RRZE_WP_VERSION = '6.5';

// System requirements check
function rrze_faudir_system_requirements() {
    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        $error = sprintf(
            /* translators: 1: Current PHP version 2: Required PHP version */
            __('Your PHP version (%1$s) is outdated. Please upgrade to PHP %2$s or higher.', 'rrze-faudir'),
            PHP_VERSION,
            RRZE_PHP_VERSION
        );
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        $error = sprintf(
            /* translators: 1: Current WordPress version 2: Required WordPress version */
            __('Your WordPress version (%1$s) is outdated. Please upgrade to WordPress %2$s or higher.', 'rrze-faudir'),
            $GLOBALS['wp_version'],
            RRZE_WP_VERSION
        );
    }
    
    if (!empty($error)) {
        add_action('admin_notices', function() use ($error) {
            printf('<div class="notice notice-error"><p>%s</p></div>', $error);
        });
        return false;
    }
    return true;
}

// Initialize plugin if system requirements are met
if (rrze_faudir_system_requirements()) {
    // Load plugin textdomain for translations
    function rrze_faudir_load_textdomain() {
        load_plugin_textdomain('rrze-faudir', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    add_action('plugins_loaded', 'rrze_faudir_load_textdomain');

    // Include necessary files
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/fau_dir_shortcode.php';
    require_once plugin_dir_path(__FILE__) . 'includes/blocks/fau_dir_block.php';
    require_once plugin_dir_path(__FILE__) . 'includes/utils/enqueue_scripts.php';
    require_once plugin_dir_path(__FILE__) . 'includes/utils/faudir_utils.php';
    require_once plugin_dir_path(__FILE__) . 'includes/utils/api-functions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/utils/Template.php';
    require_once plugin_dir_path(__FILE__) . 'includes/custom-post-type/custom-post-type.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin/settings-page.php';

    // Register and enqueue scripts
    EnqueueScripts::register();

    // AJAX search function
    add_action('wp_ajax_rrze_faudir_search_contacts', 'rrze_faudir_search_contacts');
    add_action('wp_ajax_nopriv_rrze_faudir_search_contacts', 'rrze_faudir_search_contacts');
    function rrze_faudir_search_contacts() {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');

        $identifier = sanitize_text_field($_POST['identifier']);

        global $wpdb;
        $contacts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}contacts WHERE identifier LIKE %s", '%' . $wpdb->esc_like($identifier) . '%'));

        if (!empty($contacts)) {
            $formatted_contacts = array_map(function($contact) {
                return [
                    'name' => $contact->name,
                    'identifier' => $contact->identifier,
                    'additional_info' => $contact->additional_info
                ];
            }, $contacts);
            wp_send_json_success($formatted_contacts);
        } else {
            wp_send_json_error(__('No contacts found with the provided identifier.', 'rrze-faudir'));
        }
    }
}

// Hook into 'init' to check plugin status and register the alias shortcode
add_action('init', 'register_kontakt_as_faudir_shortcode_alias');

function register_kontakt_as_faudir_shortcode_alias() {
    // Check if the FAU-person plugin is active
    if (!is_plugin_active('fau-person/fau-person.php')) { // Replace with the correct path to the FAU-person plugin file
        // Register the [kontakt] shortcode as an alias for [faudir]
        add_shortcode('kontakt', 'kontakt_to_faudir_shortcode_alias');
    }
}

// Function to handle the [kontakt] shortcode and redirect to [faudir]
function kontakt_to_faudir_shortcode_alias($atts, $content = null) {
    // Simply pass all attributes and content to the [faudir] shortcode
    return do_shortcode(shortcode_unautop('[faudir ' . shortcode_parse_atts_to_string($atts) . ']' . $content . '[/faudir]'));
}

// Helper function to convert attributes array to string
function shortcode_parse_atts_to_string($atts) {
    $output = '';
    foreach ($atts as $key => $value) {
        if (is_numeric($key)) {
            $output .= " $value";
        } else {
            $output .= sprintf(' %s="%s"', $key, esc_attr($value));
        }
    }
    return trim($output);
}
