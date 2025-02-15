<?php

/*
Plugin Name: RRZE FAUdir
Plugin URI: https://github.com/RRZE-Webteam/rrze-faudir
Description: Plugin for displaying the FAU person and institution directory on websites.
Version: 2.1.3-2
Author: RRZE Webteam
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: rrze-faudir
Domain Path: /languages
Requires at least: 6.7
Requires PHP: 8.2
*/


namespace RRZE\FAUdir;

use RRZE\FAUdir\Main;
use RRZE\FAUdir\EnqueueScripts;
use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Template;
use RRZE\FAUdir\Person;
use RRZE\FAUdir\Debug;
use Exception;

// Define plugin constants
define('RRZE_PLUGIN_FILE', __FILE__);
define('RRZE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RRZE_PLUGIN_URL', plugin_dir_url(__FILE__));

defined('ABSPATH') || exit;
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

// Load the plugin's text domain for localization.
add_action('init', fn() => load_plugin_textdomain('rrze-faudir', false, dirname(plugin_basename(__FILE__)) . '/languages'));



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
        add_action('admin_notices', function () use ($error) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($error));
        });
        return false;
    }
    return true;
}

// Initialize plugin if system requirements are met
if (rrze_faudir_system_requirements()) {

    
/*
 * 
 *  js file existiert nicht. Wozu braucht man das??
    function rrze_faudir_enqueue_scripts() {
        // Register your script with the dependencies
        wp_enqueue_script(
            'rrze-faudir-script',
            plugin_dir_url(__FILE__) . 'assets/js/script.js',
            ['wp-i18n', 'wp-element'], // Include wp-i18n for translations
            '1.0.0',
            true
        );

        // Load translations for your script
        wp_set_script_translations(
            'rrze-faudir-script', // Script handle
            'rrze-faudir',        // Text domain
            plugin_dir_path(__FILE__) . 'languages' // Path to your .json files
        );
    }
     add_action('wp_enqueue_scripts', 'rrze_faudir_enqueue_scripts');
    */

    // Include necessary files

    require_once plugin_dir_path(__FILE__) . 'includes/utils/api-functions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/custom-post-type/custom-post-type.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin/settings-page.php';

    
    $main = new Main(RRZE_PLUGIN_FILE);
    $main->onLoaded();
    




    // Schedule the event on plugin activation
    register_activation_hook(__FILE__,  __NAMESPACE__ . '\schedule_check_person_availability');
    function schedule_check_person_availability() {
        if (!wp_next_scheduled('check_person_availability')) {
            wp_schedule_event(time(), 'hourly', 'check_person_availability');
        }
    }

    // Unschedule the event on plugin deactivation
    register_deactivation_hook(__FILE__,  __NAMESPACE__ . '\unschedule_check_person_availability');
    function unschedule_check_person_availability()  {
        $timestamp = wp_next_scheduled('check_person_availability');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'check_person_availability');
        }
    }

    // Hook the function to check availability
    add_action('check_person_availability',  __NAMESPACE__ . '\check_api_person_availability');
    function check_api_person_availability()  {
        // Check if the job is already running
        if (get_transient('check_person_availability_running')) {
            // error_log('Cron job is already running.');
            return;
        }

        // Set a transient to indicate the job is running
        set_transient('check_person_availability_running', true, 60); // 60 seconds

        // Log the start of the cron job
        // error_log('Cron job check_person_availability started.');

        // Your existing code to check person availability
        $args = array(
            'post_type' => 'custom_person',
            'post_status' => 'any', // Change to 'any' to include drafts and other statuses
            'posts_per_page' => 1000,
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
            if ($person_data === false || empty($person_data)) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft',
                ));
            }
        }

        // Delete the transient to indicate the job is finished
        delete_transient('check_person_availability_running');

        // Log the completion of the cron job
        // error_log('Cron job check_person_availability completed.');
    }

    // AJAX search function
    add_action('wp_ajax_rrze_faudir_search_contacts',  __NAMESPACE__ . '\rrze_faudir_search_contacts');
    add_action('wp_ajax_nopriv_rrze_faudir_search_contacts',  __NAMESPACE__ . '\rrze_faudir_search_contacts');
    function rrze_faudir_search_contacts()   {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');

        if (isset($_POST['identifier'])) {
            $identifier = sanitize_text_field(wp_unslash($_POST['identifier']));
        }

        global $wpdb;
        $contacts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}contacts WHERE identifier LIKE %s", '%' . $wpdb->esc_like($identifier) . '%'));

        if (!empty($contacts)) {
            $formatted_contacts = array_map(function ($contact) {
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
add_action('init',  __NAMESPACE__ . '\register_kontakt_as_faudir_shortcode_alias');
function register_kontakt_as_faudir_shortcode_alias() {
    // Include the plugin.php file to ensure is_plugin_active() is available
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    // Check if the FAU-person plugin is not active
    if (!is_plugin_active('fau-person/fau-person.php')) {
        add_shortcode('kontakt',  __NAMESPACE__ . '\kontakt_to_faudir_shortcode_alias');
        add_shortcode('kontaktliste',  __NAMESPACE__ . '\kontaktliste_to_faudir_shortcode_alias');
    }
}

// Function to handle the [kontakt] shortcode and redirect to [faudir]
function kontakt_to_faudir_shortcode_alias($atts, $content = null) {
    // Pass all attributes and content to the [faudir] shortcode
    $atts_string = '';
    if (!empty($atts)) {
        foreach ($atts as $key => $value) {
            $atts_string .= $key . '="' . esc_attr($value) . '" ';
        }
    }

    return do_shortcode(shortcode_unautop('[faudir ' . trim($atts_string) . ']' . $content . '[/faudir]'));
}

// Function to handle the [kontaktliste] shortcode with specific format "list"
function kontaktliste_to_faudir_shortcode_alias($atts, $content = null) {
    // Ensure the format is set to "list"
    if (!isset($atts['format'])) {
        $atts['format'] = 'list';
    }

    // Convert attributes to string
    $atts_string = '';
    foreach ($atts as $key => $value) {
        $atts_string .= $key . '="' . esc_attr($value) . '" ';
    }

    return do_shortcode(shortcode_unautop('[faudir ' . trim($atts_string) . ']' . $content . '[/faudir]'));
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
            // error_log('Loading custom person template: ' . $plugin_template);
            return $plugin_template;
        } else {
            // error_log('Custom person template not found at: ' . $plugin_template);
        }
    }
    return $template;
}
add_filter('template_include',  __NAMESPACE__ . '\load_custom_person_template', 99);

// Hook into plugin activation
register_activation_hook(__FILE__,  __NAMESPACE__ . '\migrate_person_data_on_activation');
function migrate_person_data_on_activation() {
    register_custom_taxonomy();

    $contact_posts = get_posts([
        'post_type' => 'person',
        'posts_per_page' => -1,
    ]);

    // Initialize counters and reasons array
    $imported_count = 0;
    $not_imported_count = 0;
    $not_imported_reasons = [];

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
                $personId = null;

                if (!empty($identifier)) {
                    $queryParts[] = 'uid=' . $identifier;
                } else if (!empty($email)) {
                    // search for contacts with the email
                    $response = fetch_fau_contacts(1, 0, ['lq' => 'workplaces.mails=' . $email]);
                    // error_log('Response: ' . print_r($response, true));
                    // if no contact is found, search the persons
                    if (empty($response['data'])) {
                        $queryParts[] = 'email=' . $email;
                    } else {
                        // get the person id from the contact's person object
                        $personId = $response['data'][0]['person']['identifier'];
                        $queryParts[] = 'identifier=' . $personId;
                    }
                } else if (!empty($givenName) || !empty($familyName)) {
                    $queryParts[] = 'givenName=' . $givenName;
                    $queryParts[] = 'familyName=' . $familyName;
                }

                $params = [
                    'lq' => implode('&', $queryParts)
                ];
                $response = fetch_fau_persons(1, 0, $params);

                // error_log('Response: ' . print_r($response, true));

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
                            'post_title' => $post->post_title,
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
                                'person_nobility_title' => sanitize_text_field($person['titleOfNobility'] ?? ''),
                                'person_contacts' => $contacts,
                                'fau_person_faudir_synced' => $univisid,
                                'old_person_post_id' => $post->ID
                            ]
                        ]);

                        // the old post was of post type 'person' and had categories named 'persons_category'
                        // the new post is of post type 'custom_person' and has a category named 'custom_taxonomy'
                        // if the old post had categories, we need to add them to the new post type 'custom_person'

                        // first get the categories from the old post
                        $old_categories = wp_get_post_terms($post->ID, 'persons_category', array("fields" => "all"));

                        if (!empty($old_categories) && !is_wp_error($old_categories)) {
                            foreach ($old_categories as $old_category) {
                                // Check if a term with the same slug exists in the new taxonomy
                                $existing_term = term_exists($old_category->name, 'custom_taxonomy');

                                // log the existing term, which can be null, the term id, an array or 0                                
                                // error_log('[RRZE-FAUDIR] Existing term: ' . print_r($existing_term, true));
                                if (!$existing_term) {
                                    // Create new term in custom_taxonomy
                                    $new_term = wp_insert_term(
                                        $old_category->name,    // the term name
                                        'custom_taxonomy',      // the taxonomy
                                        array(
                                            'description' => $old_category->description,
                                            'slug' => $old_category->slug
                                        )
                                    );

                                    if (!is_wp_error($new_term)) {
                                        $term = get_term($new_term['term_id'], 'custom_taxonomy');
                                    }
                                } else {
                                    $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
                                    $term = get_term($term_id, 'custom_taxonomy');
                                }

                                // If we have a valid term, set it for the new post
                                if ($term && !is_wp_error($term)) {
                                    wp_set_object_terms(
                                        $new_post_id,           // post ID
                                        $term->name,            // use the term name instead of ID
                                        'custom_taxonomy',      // taxonomy
                                        true                    // append
                                    );
                                }
                            }
                        }

                        // Increment counter after successful import
                        if ($new_post_id && !is_wp_error($new_post_id)) {
                            $imported_count++;
                        }
                    } else {
                        // Separate messages for each case
                        if (empty($univisid)) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Missing Univis ID for person: ', 'rrze-faudir') . $post->post_title;
                        } else {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with Univis ID ', 'rrze-faudir') . $univisid . __(' already exists.', 'rrze-faudir');
                        }
                    }
                } else {
                    // Separate messages for each case
                    if (empty($univisid)) {
                        $not_imported_count++;
                        $not_imported_reasons[] = __('Missing Univis ID for person: ', 'rrze-faudir') . $post->post_title;
                    } else {
                        $not_imported_count++;
                        $not_imported_reasons[] = __('Person with Univis ID ', 'rrze-faudir') . $univisid . __(' already exists.', 'rrze-faudir');
                    }
                }
            } else {
                // Separate messages for each case
                if (empty($univisid)) {
                    $not_imported_count++;
                    $not_imported_reasons[] = __('Missing Univis ID for person: ', 'rrze-faudir') . $post->post_title;
                } else {
                    $not_imported_count++;
                    $not_imported_reasons[] = __('Person with Univis ID ', 'rrze-faudir') . $univisid . __(' already exists.', 'rrze-faudir');
                }
            }
        }
    }

    // Store the counts and reasons in transients to display them later
    set_transient('rrze_faudir_imported_count', $imported_count, 60);
    set_transient('rrze_faudir_not_imported_count', $not_imported_count, 60);
    set_transient('rrze_faudir_not_imported_reasons', $not_imported_reasons, 60);

    // Add an action to display the notice
    add_action('admin_notices',  __NAMESPACE__ . '\rrze_faudir_display_import_notice');
}

// Add this function to display the notice
function rrze_faudir_display_import_notice() {
    // Only show on the plugins page
    $screen = get_current_screen();
    if ($screen->id !== 'plugins') {
        return;
    }

    $imported_count = get_transient('rrze_faudir_imported_count');
    $not_imported_count = get_transient('rrze_faudir_not_imported_count');
    $not_imported_reasons = get_transient('rrze_faudir_not_imported_reasons');
    if ($imported_count !== false || $not_imported_count !== false) {
        // Import success message
        $import_message = sprintf(
            /* translators: %d: number of imported persons */
            _n(
                '%d person was successfully imported from the old plugin.',
                '%d persons were successfully imported from the old plugin.',
                $imported_count,
                'rrze-faudir'
            ),
            $imported_count
        );

        // Not imported message
        $not_imported_message = sprintf(
            /* translators: %d: number of not imported persons */
            _n(
                '%d person was not able to be imported from the old plugin.',
                '%d persons were not able to be imported from the old plugin.',
                $not_imported_count,
                'rrze-faudir'
            ),
            $not_imported_count
        );

        // Slug configuration warning
        $slug_warning = __('You now have the option to set a custom slug for person pages in the settings. If you don\'t set a unique slug, existing person pages from the old plugin may be overridden by the new plugin\'s person pages. To prevent this, please ensure that you configure a custom slug in the settings if you want to keep the old pages intact.', 'rrze-faudir');

        // Display all messages
        if ($imported_count > 0) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html($import_message)
            );
        }
        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            esc_html($slug_warning)
        );
        if ($not_imported_count > 0) {
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html($not_imported_message)
            );
        }

        // Display not imported reasons
        if (!empty($not_imported_reasons)) {
            foreach ($not_imported_reasons as $reason) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html($reason)
                );
            }
        }

        delete_transient('rrze_faudir_imported_count');
        delete_transient('rrze_faudir_not_imported_count');
        delete_transient('rrze_faudir_not_imported_reasons');
    }
}
// Use a higher priority number (15) to make it appear after the default activation message (priority 10)
add_action('admin_notices', __NAMESPACE__ . '\rrze_faudir_display_import_notice', 15);

function rrze_faudir_flush_rewrite_on_slug_change($old_value, $value, $option) {
    if ($option === 'rrze_faudir_options' && $old_value['person_slug'] !== $value['person_slug']) {
        flush_rewrite_rules(); // Flush rewrite rules if the slug changes
    }
}
add_action('update_option_rrze_faudir_options',  __NAMESPACE__ . '\rrze_faudir_flush_rewrite_on_slug_change', 10, 3);

function rrze_faudir_save_permalink_settings() {
    // Simulate visiting the Permalinks page to refresh rewrite rules
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}
add_action('admin_init',  __NAMESPACE__ . '\rrze_faudir_save_permalink_settings');

// Register the custom taxonomy before migration
function register_custom_person_taxonomies() {
    $labels = array(
        'name'              => _x('Categories', 'taxonomy general name', 'rrze-faudir'),
        'singular_name'     => _x('Category', 'taxonomy singular name', 'rrze-faudir'),
        'search_items'      => __('Search Categories', 'rrze-faudir'),
        'all_items'         => __('All Categories', 'rrze-faudir'),
        'parent_item'       => __('Parent Category', 'rrze-faudir'),
        'parent_item_colon' => __('Parent Category:', 'rrze-faudir'),
        'edit_item'         => __('Edit Category', 'rrze-faudir'),
        'update_item'       => __('Update Category', 'rrze-faudir'),
        'add_new_item'      => __('Add New Category', 'rrze-faudir'),
        'new_item_name'     => __('New Category Name', 'rrze-faudir'),
        'menu_name'         => __('Categories', 'rrze-faudir'),
    );

    register_taxonomy('custom_taxonomy', 'custom_person', array(
        'hierarchical'      => true,
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'        => true,
        'rewrite'          => array('slug' => 'person-category'),
        'show_in_rest'     => true,
        'rest_base'        => 'custom_taxonomy',
    ));
}

// Make sure to register the taxonomy on init as well
add_action('init',  __NAMESPACE__ . '\register_custom_person_taxonomies');

function custom_cpt_404_message() {
    global $wp_query;

    // Check query vars for custom_person post type
    if (isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === 'custom_person') {
        if (empty($wp_query->post)) {
            $wp_query->set_404();
            status_header(404);

            ob_start();

            add_action('shutdown', function () {
                $content = ob_get_clean();

                // Replace the hero-content section with your custom message
                $new_hero_content = '<div class="hero-container hero-content">'
                    . '<p class="presentationtitle">' . __('No contact entry could be found.', 'rrze-faudir') . '</p>'
                    . '</div>';

                // Replace the content of the hero section dynamically
                $updated_content = preg_replace(
                    '/<p class="presentationtitle">.*?<\/p>/s',
                    $new_hero_content,
                    $content
                );

                // Output the modified content
                echo $updated_content;
            }, 0);

            include get_404_template();
            exit;
        }
    } else {
        // Check the request URI for /person/ slug
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/person/') !== false) {
            $wp_query->set_404();
            status_header(404);

            ob_start();

            add_action('shutdown', function () {
                $content = ob_get_clean();

                $new_hero_content = '<div class="hero-container hero-content">'
                    . '<p class="presentationtitle">' . __('No contact entry could be found.', 'rrze-faudir') . '</p>'
                    . '</div>';

                // Replace the content of the hero section dynamically
                $updated_content = preg_replace(
                    '/<p class="presentationtitle">.*?<\/p>/s',
                    $new_hero_content,
                    $content
                );

                // Output the modified content
                echo $updated_content;
            }, 0);
            include get_404_template();
            exit;
        }
    }
}
add_action('template_redirect',  __NAMESPACE__ . '\custom_cpt_404_message');

// Register FAUdir Block
function register_faudir_block() {   
    register_block_type(plugin_dir_path(__FILE__) . '/faudir-block/build', [
        'render_callback' =>  __NAMESPACE__ .'\\render_faudir_block',
        'skip_inner_blocks' => true
    ]);
}
add_action('init',  __NAMESPACE__ . '\register_faudir_block');

// Render callback function for FAUdir block
function render_faudir_block($attributes) {
    try {
   //     error_log('FAUDIR Block render started with attributes: ' . print_r($attributes, true));

        if (!shortcode_exists('faudir')) {
            throw new Exception('FAUDIR shortcode is not registered');
        }

        // Get default organization from options with proper checks
        $options = get_option('rrze_faudir_options', []);
        $default_org = isset($options['default_organization']) && is_array($options['default_organization']) 
            ? $options['default_organization'] 
            : [];
        $defaultOrgIdentifier = isset($default_org['id']) ? $default_org['id'] : '';

        // First check if we have function and orgnr
        if (!empty($attributes['function'])) {
            $shortcode_atts = [
                'format' => $attributes['selectedFormat'] ?? 'kompakt',
                'function' => $attributes['function'],
                'orgnr' => !empty($attributes['orgnr']) ? $attributes['orgnr'] : $defaultOrgIdentifier
            ];
        } 
        // Then check for category
        else if (!empty($attributes['selectedCategory'])) {
            $shortcode_atts = [
                'format' => $attributes['selectedFormat'] ?? 'kompakt',
                'category' => $attributes['selectedCategory']
            ];
            
            // Only add identifiers if they're specifically selected for this category
            if (!empty($attributes['selectedPersonIds'])) {
                $shortcode_atts['identifier'] = implode(',', $attributes['selectedPersonIds']);
            }
        }
        // Finally check for selectedPersonIds without category
        else if (!empty($attributes['selectedPersonIds'])) {
            $shortcode_atts = [
                'format' => $attributes['selectedFormat'] ?? 'kompakt',
                'identifier' => is_array($attributes['selectedPersonIds']) ? 
                    implode(',', $attributes['selectedPersonIds']) : 
                    $attributes['selectedPersonIds']
            ];
        }
        else {
            throw new Exception(__('Neither person IDs, function+orgnr, nor category were provided', 'rrze-faudir'));
        }

        // Add optional attributes
        if (!empty($attributes['selectedFields'])) {
            $shortcode_atts['show'] = implode(',', $attributes['selectedFields']);
        }
        
        if (!empty($attributes['hideFields'])) {
            $shortcode_atts['hide'] = implode(',', $attributes['hideFields']);
        }

        if (!empty($attributes['buttonText'])) {
            $shortcode_atts['button-text'] = $attributes['buttonText'];
        }

        if (!empty($attributes['groupId'])) {
            $shortcode_atts['groupid'] = $attributes['groupId'];
        }

        if (!empty($attributes['url'])) {
            $shortcode_atts['url'] = $attributes['url'];
        }

        if (!empty($attributes['sort'])) {
            $shortcode_atts['sort'] = $attributes['sort'];
        }

        // Build shortcode string
        $shortcode = '[faudir';
        foreach ($shortcode_atts as $key => $value) {
            if (!empty($value)) {
                $shortcode .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
            }
        }
        $shortcode .= ']';

     //   error_log('Generated shortcode: ' . $shortcode);

        // Execute shortcode
        $output = do_shortcode($shortcode);

        if (empty(trim($output))) {
            throw new Exception('Shortcode returned empty content');
        }

        return $output;

    } catch (Exception $e) {
        error_log('FAUDIR Block Error: ' . $e->getMessage());
        return sprintf(
            '<div class="faudir-error">%s</div>',
            esc_html($e->getMessage())
        );
    }
}

// Add editor assets
add_action('enqueue_block_editor_assets', function() {
    // Get the file paths    
    $js_path = plugin_dir_path(__FILE__) . 'faudir-block/build/index.js';
    $css_path = plugin_dir_path(__FILE__) . 'faudir-block/build/style.css';

    // Only register and enqueue if files exist
    wp_register_script(
        'rrze-faudir-block-script',
        plugins_url('faudir-block/build/index.js', __FILE__),
        ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'],
        file_exists($js_path) ? filemtime($js_path) : '1.0.0',
        true
    );

    // Check if style file exists before registering
    if (file_exists($css_path)) {
        wp_register_style(
            'rrze-faudir-block-style',
            plugins_url('faudir-block/build/style.css', __FILE__),
            [],
            filemtime($css_path)
        );
        wp_enqueue_style('rrze-faudir-block-style');
    }
    
    wp_set_script_translations('rrze-faudir-block-script', 'rrze-faudir', plugin_dir_path(__FILE__) . 'languages');
    wp_enqueue_script('rrze-faudir-block-script');
});

// Add this to your existing plugin file where other REST routes are registered
add_action('rest_api_init', function () {
    register_rest_route('wp/v2/settings', 'rrze_faudir_options', array(
        'methods' => 'GET',
        'callback' => function () {
            $options = get_option('rrze_faudir_options', []);
            return [
                'default_output_fields' => isset($options['default_output_fields']) ? 
                    $options['default_output_fields'] : 
                    [],
                'business_card_title' => isset($options['business_card_title']) ? 
                    $options['business_card_title'] : 
                    __('More Information', 'rrze-faudir'),
                'default_organization' => isset($options['default_organization']) ? 
                    $options['default_organization'] : 
                    null
            ];
        },
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
});
add_filter( 'block_categories_all' , function( $categories ) {

    // Adding a new category.
	$categories[] = array(
		'slug'  => 'custom-fau-category',
		'title' => 'Fau'
	);

	return $categories;
} );