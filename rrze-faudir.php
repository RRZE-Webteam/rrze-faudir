<?php
/*
Plugin Name: rrze-faudir
Description: Plugin for displaying the FAU person and institution directory on websites.
Version: 1.0.0
Author: Your Name
Text Domain: rrze-faudir
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin textdomain for translations
function rrze_faudir_load_textdomain()
{
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

// Include the new settings page file
require_once plugin_dir_path(__FILE__) . 'includes/admin/settings-page.php';

// Register and enqueue scripts
EnqueueScripts::register();
add_action('wp_ajax_rrze_faudir_search_contacts', 'rrze_faudir_search_contacts');
function rrze_faudir_search_contacts() {
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $identifier = sanitize_text_field($_POST['identifier']);

    // Example: Fetching contacts by identifier
    global $wpdb;
    $contacts = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}contacts WHERE identifier LIKE %s", '%' . $wpdb->esc_like($identifier) . '%'));

    if (!empty($contacts)) {
        // Format the results for the frontend
        $formatted_contacts = array();
        foreach ($contacts as $contact) {
            $formatted_contacts[] = array(
                'name' => $contact->name,
                'identifier' => $contact->identifier,
                'additional_info' => $contact->additional_info
            );
        }
        wp_send_json_success($formatted_contacts);
    } else {
        wp_send_json_error(__('No contacts found with the provided identifier.', 'rrze-faudir'));
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
