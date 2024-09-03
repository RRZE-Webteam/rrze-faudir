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
