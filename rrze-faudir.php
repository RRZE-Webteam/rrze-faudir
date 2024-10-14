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

// Check if the function exists before using it
if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

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
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($error));
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

    // Schedule the event on plugin activation
    register_activation_hook(__FILE__, 'schedule_check_person_availability');
    function schedule_check_person_availability() {
        if (!wp_next_scheduled('check_person_availability')) {
            wp_schedule_event(time(), 'daily', 'check_person_availability');
        }
    }

    // Unschedule the event on plugin deactivation
    register_deactivation_hook(__FILE__, 'unschedule_check_person_availability');
    function unschedule_check_person_availability() {
        $timestamp = wp_next_scheduled('check_person_availability');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'check_person_availability');
        }
    }

    // Hook the function to check availability
    add_action('check_person_availability', 'check_api_person_availability');
    function check_api_person_availability() {
        $args = array(
            'post_type' => 'custom_person',
            'post_status' => 'any', // Change to 'any' to include drafts and other statuses
            'posts_per_page' => -1,
        );
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $person_id = get_post_meta($post->ID, 'person_id', true);


            // Check if person_id is empty
            if (empty($person_id)) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft',
                ));
                continue; // Skip to the next post
            }

            // Make API request to check if person is accessible
            $person_data = fetch_fau_person_by_id($person_id);

            // If the response indicates an error with status code 404, update the post to draft
            if (  $person_data === false || empty(  $person_data)) 
                {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft',
                ));
            }
        }
    }

    // AJAX search function
    add_action('wp_ajax_rrze_faudir_search_contacts', 'rrze_faudir_search_contacts');
    add_action('wp_ajax_nopriv_rrze_faudir_search_contacts', 'rrze_faudir_search_contacts');
    function rrze_faudir_search_contacts() {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');
        
        if (isset($_POST['identifier'])) {
            $identifier = sanitize_text_field(wp_unslash($_POST['identifier']));
        }

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


function load_custom_person_template($template) {
    if (get_query_var('custom_person') || is_singular('custom_person')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-custom_person.php';
        if (file_exists($plugin_template)) {
            //error_log('Loading custom person template: ' . $plugin_template);
            return $plugin_template;
        } else {
            //error_log('Custom person template not found at: ' . $plugin_template);
        }
    }
    return $template;
}
add_filter('template_include', 'load_custom_person_template', 99);

// Hook into plugin activation
register_activation_hook(__FILE__, 'migrate_person_data_on_activation');

function migrate_person_data_on_activation() {
    // Fetch all 'person' posts
    $contact_posts = get_posts([
        'post_type' => 'person',
        'posts_per_page' => -1,
    ]);

    if (!empty($contact_posts)) {
        foreach ($contact_posts as $post) {
            // Check if the post has a UnivIS ID, which will be used as the person_id
            $univisid = get_post_meta($post->ID, 'fau_person_univis_id', true);

            if ($univisid) {
                // Check if the person already exists in 'custom_person' based on person_id (UnivIS ID)
                $existing_person = get_posts([
                    'post_type' => 'custom_person',
                    'meta_key' => 'person_id',
                    'meta_value' => $univisid,
                    'posts_per_page' => 1,
                ]);

                if (!$existing_person) {
                    // API call to fetch additional data
                    $params = ['identifier' => $univisid];
                    $response = fetch_fau_persons_atributes(60, 0, $params);

                    if (is_array($response) && isset($response['data'])) {
                        $contact = $response['data'][0] ?? null; // Assuming first contact is needed

                        // Create a new 'custom_person' post
                        $new_post_id = wp_insert_post([
                            'post_type' => 'custom_person',
                            'post_title' => $post->post_title, // Use the title from the 'person' post
                            'post_content' => $post->post_content, // Copy the content from the 'person' post
                            'post_status' => 'publish',
                        ]);

                        if ($new_post_id && $contact) {
                            $short_description = get_post_meta($post->ID, 'fau_person_description', true);
                            if ($short_description) {
                                update_post_meta($new_post_id, '_teasertext_en', sanitize_text_field($short_description));
                            }
                            // Set the featured image if the original post has one
                            $thumbnail_id = get_post_thumbnail_id($post->ID);
                            if ($thumbnail_id) {
                                set_post_thumbnail($new_post_id, $thumbnail_id);
                            }

                            // Migrate all relevant meta fields from API to the new 'custom_person' post
                            update_post_meta($new_post_id, 'person_id', $univisid);
                            update_post_meta($new_post_id, 'person_name', sanitize_text_field($contact['givenName'] . ' ' . $contact['familyName']));
                            update_post_meta($new_post_id, 'person_given_name', sanitize_text_field($contact['givenName'] ?? ''));
                            update_post_meta($new_post_id, 'person_family_name', sanitize_text_field($contact['familyName'] ?? ''));
                            update_post_meta($new_post_id, 'person_email', sanitize_email($contact['email'] ?? ''));
                            update_post_meta($new_post_id, 'person_title', sanitize_text_field($contact['personalTitle'] ?? ''));
                            update_post_meta($new_post_id, 'person_function', sanitize_text_field($contact['functionLabel']['en'] ?? ''));
                            update_post_meta($new_post_id, 'person_organization', sanitize_text_field($contact['contacts'][0]['organization']['name'] ?? ''));
                          

                            // Optional: log success for debugging
                           // error_log("Successfully migrated person with UnivIS ID: $univisid to custom_person.");
                        } else {
                            // Optional: log failure for debugging
                            //error_log("Failed to create custom_person for UnivIS ID: $univisid.");
                        }
                    } else {
                        // Log API error if response isn't successful
                       // error_log("Error fetching person attributes for UnivIS ID: $univisid. Response: " . json_encode($response));
                    }
                } else {
                    // Optional: log that the person already exists
                   // error_log("Person with UnivIS ID: $univisid already exists in custom_person.");
                }
            } else {
                // Optional: log if UnivIS ID is missing
                //error_log("No UnivIS ID found for post ID: {$post->ID}");
            }
        }
    } else {
        // Optional: log if no contact posts were found
        //error_log('No posts found for post type "person".');
    }
}




