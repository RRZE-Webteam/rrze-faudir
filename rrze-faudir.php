<?php

/*
Plugin Name: RRZE FAUdir
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
const RRZE_WP_VERSION = '6.5';

// System requirements check
function rrze_faudir_system_requirements()
{
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
    require_once plugin_dir_path(__FILE__) . 'includes/admin/settings-page.php';
    require_once plugin_dir_path(__FILE__) . 'includes/config/icons.php';

    // Register and enqueue scripts
    EnqueueScripts::register();

    // Schedule the event on plugin activation
    register_activation_hook(__FILE__, 'schedule_check_person_availability');
    function schedule_check_person_availability()
    {
        if (!wp_next_scheduled('check_person_availability')) {
            wp_schedule_event(time(), 'every_hour', 'check_person_availability');
        }
    }

    // Add custom cron schedule for every minute
    add_filter('cron_schedules', 'add_every_hour_cron_schedule');
    function add_every_hour_cron_schedule($schedules)
    {
        $schedules['every_hour'] = array(
            'interval' => 3600,
            'display'  => __('Every Hour'),
        );
        return $schedules;
    }

    // Unschedule the event on plugin deactivation
    register_deactivation_hook(__FILE__, 'unschedule_check_person_availability');
    function unschedule_check_person_availability()
    {
        $timestamp = wp_next_scheduled('check_person_availability');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'check_person_availability');
        }
    }

    // Hook the function to check availability
    add_action('check_person_availability', 'check_api_person_availability');
    function check_api_person_availability()
    {
        $args = array(
            'post_type' => 'custom_person',
            'post_status' => 'any', // Include drafts and other statuses
            'posts_per_page' => -1,
        );
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $person_id = get_post_meta($post->ID, 'person_id', true);

            // If person_id is missing, set the post to draft
            if (empty($person_id)) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft',
                ));
                continue; // Skip to the next post
            }

            // Fetch person data from API
            $person_data = fetch_fau_person_by_id($person_id);

            // If API returns an error or empty data, set the post to draft
            if ($person_data === false || empty($person_data)) {
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
    function rrze_faudir_search_contacts()
    {
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
add_action('init', 'register_kontakt_as_faudir_shortcode_alias');

function register_kontakt_as_faudir_shortcode_alias()
{
    // Include the plugin.php file to ensure is_plugin_active() is available
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    // Check if the FAU-person plugin is not active
    if (!is_plugin_active('fau-person-master/fau-person.php')) { 
        add_shortcode('kontakt', 'kontakt_to_faudir_shortcode_alias');
        add_shortcode('kontaktliste', 'kontaktliste_to_faudir_shortcode_alias');
    }
}

// Function to handle the [kontakt] shortcode and redirect to [faudir]
function kontakt_to_faudir_shortcode_alias($atts, $content = null)
{
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
function kontaktliste_to_faudir_shortcode_alias($atts, $content = null)
{
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
function shortcode_parse_atts_to_string($atts)
{
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


function load_custom_person_template($template)
{
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

function migrate_person_data_on_activation()
{
    register_custom_taxonomy();

    $contact_posts = get_posts([
        'post_type' => 'person',
        'posts_per_page' => -1,
    ]);

    // Initialize counter
    $imported_count = 0;

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
                } else if (!empty($email)) {
                    $queryParts[] = 'email=' . $email;
                } else if (!empty($givenName) || !empty($familyName)) {
                    $queryParts[] = 'givenName=' . $givenName;
                    $queryParts[] = 'familyName=' . $familyName;
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
                                'person_nobility_name' => sanitize_text_field($person['titleOfNobility'] ?? ''),
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
                                error_log('[RRZE-FAUDIR] Existing term: ' . print_r($existing_term, true));
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
                    }
                }
            }
        }
    }

    // Store the count in a transient to display it later
    set_transient('rrze_faudir_imported_count', $imported_count, 60);

    // Add an action to display the notice
    add_action('admin_notices', 'rrze_faudir_display_import_notice');
}

// Add this new function to display the notice
function rrze_faudir_display_import_notice()
{
    // Only show on the plugins page
    $screen = get_current_screen();
    if ($screen->id !== 'plugins') {
        return;
    }

    $imported_count = get_transient('rrze_faudir_imported_count');
    if ($imported_count !== false) {
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

        // Slug configuration warning
        $slug_warning = __('You now have the option to set a custom slug for person pages in the settings. If you don\'t set a unique slug, existing person pages from the old plugin may be overridden by the new plugin\'s person pages. To prevent this, please ensure that you configure a custom slug in the settings if you want to keep the old pages intact.', 'rrze-faudir');

        // Display both messages
        printf(
            '<div class="notice notice-success"><p>%s</p></div><div class="notice notice-warning"><p>%s</p></div>',
            esc_html($import_message),
            esc_html($slug_warning)
        );

        delete_transient('rrze_faudir_imported_count');
    }
}
// Use a higher priority number (15) to make it appear after the default activation message (priority 10)
add_action('admin_notices', 'rrze_faudir_display_import_notice', 15);

function rrze_faudir_flush_rewrite_on_slug_change($old_value, $value, $option)
{
    if ($option === 'rrze_faudir_options' && $old_value['person_slug'] !== $value['person_slug']) {
        flush_rewrite_rules(); // Flush rewrite rules if the slug changes
    }
}
add_action('update_option_rrze_faudir_options', 'rrze_faudir_flush_rewrite_on_slug_change', 10, 3);

function rrze_faudir_save_permalink_settings()
{
    // Simulate visiting the Permalinks page to refresh rewrite rules
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}
add_action('admin_init', 'rrze_faudir_save_permalink_settings');

// Register the custom taxonomy before migration
function register_custom_person_taxonomies()
{
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
add_action('init', 'register_custom_person_taxonomies');

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
add_action('template_redirect', 'custom_cpt_404_message');

