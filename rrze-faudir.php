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
    $contact_posts = get_posts([
        'post_type' => 'person',
        'posts_per_page' => -1,
    ]);

    if (!empty($contact_posts)) {
        foreach ($contact_posts as $post) {

            // Get Univis ID from old post
            $univisid = get_post_meta($post->ID, 'fau_person_univis_id', true);

            // Check if a custom_person with this UnivIS ID and identifier already exists
            $existing_person = get_posts([
                'post_type' => 'custom_person',
                'meta_query' => [
                    [
                        'key' => 'fau_person_faudir_synced',
                        'value' => $univisid,
                        'compare' => '=',
                    ],
                ],
                'posts_per_page' => 1,
            ]);

            if ($univisid && !$existing_person) {

                // Make Univis api call using Univis ID
                $url = 'http://univis.uni-erlangen.de/prg?search=persons&id=' . $univisid . '&show=json';
                $response = wp_remote_get($url);
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                // Extract person data
                $person = $data['Person'][0];

                // Get identifier
                $identifier = $person['idm_id'] ?? null;
                $email = $person['location'][0]['email'] ?? null;
                $givenName = $person['firstname'] ?? null;
                $familyName = $person['lastname'] ?? null;

                // Determine search parameters
                $queryParts = [];

                if (!empty($identifier)) {
                    $queryParts[] = 'uid=' . $identifier;
                }
                if (!empty($givenName)) {
                    $queryParts[] = 'givenName=' . $givenName;
                }
                if (!empty($familyName)) {
                    $queryParts[] = 'familyName=' . $familyName;
                }
                if (!empty($email)) {
                    $queryParts[] = 'email=' . urlencode($email);
                }

                $params = [
                    'lq' => implode('&', $queryParts)
                ];

                // fetch data from api using univis api data
                $response = fetch_fau_persons_atributes(60, 0, $params);

                if (is_array($response) && isset($response['data'])) {
                    $person = $response['data'][0] ?? null; // there should only be one match

                    if ($person) {

                        // Get data from old post
                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                        $short_description = get_post_meta($post->ID, 'fau_person_description', true);
                        $description = !empty($short_description) ? $short_description : get_post_meta($post->ID, 'fau_person_small_description', true);

                        // Get all contacts for the person
                        $contacts = array();
                        foreach ($person['contacts'] as $contact) {
                            // Get the identifier
                            $contactIdentifier = $contact['identifier'];
                            $organizationIdentifier = $contact['organization']['identifier'];
                                
                            $contacts[] = array(
                                'organization' => sanitize_text_field($contact['organization']['name'] ?? ''),
                                'socials' => fetch_and_format_socials($contactIdentifier),
                                'workplace' => fetch_and_format_workplaces($contactIdentifier),
                                'address' => fetch_and_format_address($organizationIdentifier),
                                'function_en' => $contact['functionLabel']['en'] ?? '',
                                'function_de' => $contact['functionLabel']['de'] ?? '',
                            ); 
                        }

                        // Create a new 'custom_person' post
                        $new_post_id = wp_insert_post([
                            'post_type' => 'custom_person',
                            'post_title' => $post->post_name,
                            'post_content' => $post->post_content,
                            'post_status' => 'publish',
                            'meta_input' => [
                                '_thumbnail_id' => $thumbnail_id ?: '',
                                '_teasertext_en' => sanitize_text_field($description),
                                'person_id' => sanitize_text_field($person['identifier']),
                                'person_name' => sanitize_text_field($person['givenName'] . ' ' . $person['familyName']),
                                'person_email' => sanitize_email($person['email'] ?? ''),
                                'person_telephone' => sanitize_text_field($person['telephone'] ?? ''),
                                'person_given_name' => sanitize_text_field($person['givenName'] ?? ''),
                                'person_family_name' => sanitize_text_field($person['familyName'] ?? ''),
                                'person_title' => sanitize_text_field($person['personalTitle'] ?? ''),
                                'person_suffix' => sanitize_text_field($person['personalTitleSuffix'] ?? ''),
                                'person_nobility_name' => sanitize_text_field($person['titleOfNobility'] ?? ''),
                                'person_contacts' => $contacts,
                                'fau_person_faudir_synced' => $univisid
                            ]
                        ]);

                        if ($new_post_id) {
                            
                            // Get the categories from the old post
                            $categories = wp_get_post_terms($post->ID, 'persons_category', array("fields" => "all"));
                            // Log the category information
                            error_log('Categories for post ID ' . $post->ID . ': ' . print_r($categories, true));
                            // Go through categories and create new categories if they don't exist
                            foreach ($categories as $category) {

                                // Log the category information
                                error_log('Category: ' . $category->name);

                                if (!term_exists($category->name, 'custom_taxonomy')) {
                                    // Create new category
                                    $term = wp_insert_term($category->name, 'custom_taxonomy');
                                    
                                    // Log the term ID
                                    error_log('Term ID: ' . $term['term_id']);
                                    
                                    // Add the category to the new post
                                    wp_set_post_terms($new_post_id, $term['term_id'], 'custom_taxonomy', false);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

function rrze_faudir_flush_rewrite_on_slug_change($old_value, $value, $option) {
    if ($option === 'rrze_faudir_options' && $old_value['person_slug'] !== $value['person_slug']) {
        flush_rewrite_rules(); // Flush rewrite rules if the slug changes
    }
}
add_action('update_option_rrze_faudir_options', 'rrze_faudir_flush_rewrite_on_slug_change', 10, 3);

function rrze_faudir_save_permalink_settings() {
    // Simulate visiting the Permalinks page to refresh rewrite rules
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}
add_action('admin_init', 'rrze_faudir_save_permalink_settings');

function rrze_faudir_activate() {
    register_custom_person_post_type(); // Register your post type
    flush_rewrite_rules(); // Clear and regenerate rewrite rules
}
register_activation_hook(__FILE__, 'rrze_faudir_activate');
