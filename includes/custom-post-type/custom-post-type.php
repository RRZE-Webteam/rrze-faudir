<?php

use RRZE\FAUdir\FaudirUtils;

// Register the Custom Post Type
function register_custom_person_post_type() {
    // Get the slug from the options; fallback to 'person' if not set.
    $options = get_option('rrze_faudir_options');
    $slug = isset($options['person_slug']) && !empty($options['person_slug'])
        ? sanitize_title($options['person_slug'])
        : 'person'; // Default to 'person'

    $args = array(
        'labels' => array(
            'name'               => __('Persons', 'rrze-faudir'),
            'singular_name'      => __('Person', 'rrze-faudir'),
            'menu_name'          => __('Persons', 'rrze-faudir'),
            'add_new_item'       => __('Add New Person', 'rrze-faudir'),
            'edit_item'          => __('Edit Person', 'rrze-faudir'),
            'view_item'          => __('View Person', 'rrze-faudir'),
            'all_items'          => __('All Persons', 'rrze-faudir'),
            'search_items'       => __('Search Persons', 'rrze-faudir'),
            'not_found'          => __('No persons found.', 'rrze-faudir'),
        ),
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array(
            'slug' => $slug, // Use dynamic slug
            'with_front' => false // Optional: Disable prefix (like /blog/)
        ),
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'taxonomies'         => array('custom_taxonomy'),
        'show_in_rest'       => true,
        'rest_base'          => 'custom_person',
        'menu_position'      => 5,
        'capability_type'    => 'post',
    	'menu_icon'         => 'dashicons-id'
    );

    register_post_type('custom_person', $args); // Keep 'custom_person' as the post type key
}
add_action('init', 'register_custom_person_post_type', 15);


function register_custom_taxonomy() {
    // Register the taxonomy
    if (!taxonomy_exists('custom_taxonomy')) {
        register_taxonomy(
            'custom_taxonomy', // Taxonomy slug
            'custom_person', // Custom Post Type to attach the taxonomy
            array(
                'labels' => array(
                    'name'              => __('Categories', 'text-domain'),
                    'singular_name'     => __('Category', 'text-domain'),
                    'search_items'      => __('Search Categories', 'text-domain'),
                    'all_items'         => __('All Categories', 'text-domain'),
                    'parent_item'       => __('Parent Category', 'text-domain'),
                    'parent_item_colon' => __('Parent Category', 'text-domain'),
                    'edit_item'         => __('Edit Category', 'text-domain'),
                    'update_item'       => __('Update Category', 'text-domain'),
                    'add_new_item'      => __('Add New Category', 'text-domain'),
                    'new_item_name'     => __('New Category Name', 'text-domain'),
                    'menu_name'         => __('Categories', 'text-domain'),
                ),
                'hierarchical'      => true, // Set true for a category-like taxonomy, false for tags.
                'public'            => true,
                'show_ui'           => true,
                'show_in_menu'      => true,
                'show_in_nav_menus' => true,
                'show_tagcloud'     => true,
                'show_in_quick_edit' => true,
                'meta_box_cb'       => null, // Use default meta box
                'show_admin_column' => true, // Show taxonomy in the admin list table.
                'query_var'         => true,
                'rewrite'           => array('slug' => 'custom-taxonomy'),
                'show_in_rest'      => true,
                'rest_base'         => 'custom_taxonomy',
                'rest_controller_class' => 'WP_REST_Terms_Controller',

            )
        );
    }
}
add_action('init', 'register_custom_taxonomy');

// Bug #119
/* function add_taxonomy_meta_box() {
    add_meta_box(
        'custom_taxonomydiv',
        __('Categories', 'text-domain'),
        'post_categories_meta_box',
        'custom_person',
        'side',
        'default',
        array('taxonomy' => 'custom_taxonomy')
    );
}
 * 
 */
// add_action('add_meta_boxes', 'add_taxonomy_meta_box');

// Add Meta Boxes
function add_custom_person_meta_boxes() {
    add_meta_box(
        'person_additional_fields',
        __('Additional Fields', 'rrze-faudir'),
        'render_person_additional_fields',
        'custom_person',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_person_meta_boxes');
function check_classic_editor_and_add_shortcode_button() {
    global $post;

    // Ensure we are in the admin area and on a post edit screen
    if (!is_admin() || !isset($post)) {
        return;
    }

    // Check if Classic Editor plugin is active
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    $is_classic_editor_active = is_plugin_active('classic-editor/classic-editor.php');

    // Target a specific custom post type
    if ($is_classic_editor_active && $post->post_type === 'custom_person') {

        // Add the button between the title and content editor
        add_action('edit_form_after_title', function () use ($post) {

            echo '<div class="generate-shortcode">
                    <input type="text" id="generated-shortcode" readonly value="[faudir identifier=\"person_id\"]">
                    <button type="button" id="copy-shortcode" class="button button-primary" >' . __('Copy shortcode to Clipboard', 'rrze-faudir') . '</button>
                  </div>';
        });
    }
}
add_action('admin_head', 'check_classic_editor_and_add_shortcode_button');
// Render Meta Box Fields
function render_person_additional_fields($post) {
    wp_nonce_field('save_person_additional_fields', 'person_additional_fields_nonce');

    $api_fields = [
        'person_name',
        'person_email',
        'person_phone',
        'person_givenName',
        'person_familyName',
        'person_honorificPrefix',
        'person_honorificSuffix',
        'person_titleOfNobility',
    ];

    $fields = [
        '_content_en' => __('Content (English)', 'rrze-faudir'),
        '_teasertext_en' => __('Teaser Text (English)', 'rrze-faudir'),
        '_teasertext_de' => __('Teaser Text (German)', 'rrze-faudir'),
        'person_id' => __('API Person Identifier', 'rrze-faudir'),
        'person_name' => __('Name', 'rrze-faudir'),
        'person_email' => __('Email', 'rrze-faudir'),
        'person_phone' => __('Telephone', 'rrze-faudir'),
        'person_givenName' => __('Given Name', 'rrze-faudir'),
        'person_familyName' => __('Family Name', 'rrze-faudir'),
        'person_honorificPrefix' => __('Title', 'rrze-faudir'),
        'person_honorificSuffix' => __('Suffix', 'rrze-faudir'),
        'person_titleOfNobility' => __('Nobility Title', 'rrze-faudir'),
    ];

    // Render regular fields
    foreach ($fields as $meta_key => $label) {
        $value = get_post_meta($post->ID, $meta_key, true);
        $readonly = in_array($meta_key, $api_fields) ? 'readonly' : '';

        // Check for 'person_id' to add descriptions
        if ($meta_key === 'person_id') {
            echo "<label for='" . esc_attr($meta_key) . "'>" . esc_html($label) . "</label>";
            echo "<p class='description'>" . __('Enter the internal "API Person Identification" of the person who can retrieve the data via FAU IdM here. The API person identifiers can view the persons themselves in the IdM portal in the view of the Personal Data. Contact persons and facility lines can access this value for other persons in their organization through the management of FAUdir. Alternatively, use the search for the settings under settings -> RRZE FAUdir -> API', 'rrze-faudir') . "</p>";
            echo "<input type='text' name='" . esc_attr($meta_key) . "' id='" . esc_attr($meta_key) . "' value='" . esc_attr($value) . "' style='width: 100%;' $readonly /><br><br>";
            echo '<p><strong>' . __('The following data comes from the FAU IdM portal. A change of data is only possible by the persons or the appointed contact persons in the IdM portal.', 'rrze-faudir') . '</strong></p>';
            echo '<hr>';
            echo '<h2>' . __('FAUdir', 'rrze-faudir').' ' .__('Data', 'rrze-faudir') . '</h2>';
            // Add a hidden input for person_id
            echo "<input type='hidden' id='hidden-person-id' value='" . esc_attr($value) . "' />";
        } elseif (in_array($meta_key, ['_content_en', '_teasertext_en', '_teasertext_de'])) {
            // Handle textarea fields
            echo "<label for='" . esc_attr($meta_key) . "'>" . esc_html($label) . "</label>";
            echo "<textarea name='" . esc_attr($meta_key) . "' id='" . esc_attr($meta_key) . "' style='width: 100%; height: 100px;' $readonly>" . esc_textarea($value) . "</textarea><br><br>";
        } else {
            // Handle other input fields
            echo "<label for='" . esc_attr($meta_key) . "'>" . esc_html($label) . "</label>";
            echo "<input type='text' name='" . esc_attr($meta_key) . "' id='" . esc_attr($meta_key) . "' value='" . esc_attr($value) . "' style='width: 100%;' $readonly /><br><br>";
        }
    }


    // Render contacts section
    echo '<div class="contacts-wrapper">';
    echo '<h3>'. __('FAUdir', 'rrze-faudir').' ' . __('Contacts', 'rrze-faudir') . '</h3>';

    $contacts = get_post_meta($post->ID, 'person_contacts', true) ?: array();
    
    $displayed_contacts = intval( get_post_meta( $post->ID, 'displayed_contacts', true ) );

    
    // $displayed_contacts = get_post_meta($post->ID, 'displayed_contacts', true) ?: array();
    // If displayed_contacts is not set, default to selecting the first contact
    if (empty($displayed_contacts) && !empty($contacts)) {
        $displayed_contacts = 0;
    }

    foreach ($contacts as $index => $contact) {
       
        $checked = $activeblock = '';
        if ($index === $displayed_contacts) {
            $checked = 'checked="checked"';
            $activeblock = ' activeblock';
        }
        
        echo '<div class="organization-block'.$activeblock.'">';
        echo '<div class="organization-header">';
        echo '<h3>' . __('Contact', 'rrze-faudir') . ' ' . ($index + 1) . '</h3>';
        echo '<label>';
        echo "<input type='radio' name='displayed_contacts' value='" . esc_attr($index) . "' $checked>";
        echo __('Display this contact', 'rrze-faudir');
        echo '</label>';
        echo '</div>';
        echo '<div class="organization-content'.$activeblock.'">';
        
        // Add organization field
        echo '<div class="organization-wrapper">';
        echo '<h4>' . __('Organization', 'rrze-faudir') . '</h4>';
        echo '<input type="text" name="person_contacts[' . $index . '][organization]" value="' . esc_attr($contact['organization']) . '" class="widefat" readonly />';
        echo '</div>';

        // English Function
        echo '<div class="function-wrapper">';
        echo '<h4>' . __('Function (English)', 'rrze-faudir') . '</h4>';
        echo '<input type="text" name="person_contacts[' . $index . '][function_en]" value="' . esc_attr($contact['function_en'] ?? '') . '" class="widefat" readonly />';
        echo '</div>';

        // German Function
        echo '<div class="function-wrapper">';
        echo '<h4>' . __('Function (German)', 'rrze-faudir') . '</h4>';
        echo '<input type="text" name="person_contacts[' . $index . '][function_de]" value="' . esc_attr($contact['function_de'] ?? '') . '" class="widefat" readonly />';
        echo '</div>';

        // Add socials field
        echo '<div class="socials-wrapper">';
        echo '<h4>' . __('Socials', 'rrze-faudir') . '</h4>';
        echo '<textarea name="person_contacts[' . $index . '][socials]" class="widefat" readonly rows="5">' . esc_textarea($contact['socials'] ?? '') . '</textarea>';
        echo '</div>';

        // Add workplace and address fields
        echo '<div class="workplace-wrapper">';
        echo '<h4>' . __('Workplace', 'rrze-faudir') . '</h4>';
        echo '<textarea name="person_contacts[' . $index . '][workplace]" class="widefat" readonly rows="6">' . esc_textarea($contact['workplace'] ?? '') . '</textarea>';
        echo '</div>';

        echo '</div>';
        echo '</div>'; // .organization-block
    }

    echo '</div>'; // .contacts-wrapper
}


function save_person_additional_fields($post_id) {
    // Verify nonce
    if (!isset($_POST['person_additional_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['person_additional_fields_nonce'])), 'save_person_additional_fields')) {
        return;
    }

    // Check if we are performing an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Get the person ID
    $person_id = sanitize_text_field(wp_unslash($_POST['person_id'] ?? ''));

    // If person_id is not empty, make an API call to fetch attributes
    if (!empty($person_id)) {
        // Set parameters for the API call
        $params = ['identifier' => $person_id];

        // Fetch person attributes using the custom function
        $response = fetch_fau_persons(60, 0, $params);

        // Check if the response is a valid array
        if (is_array($response) && isset($response['data'])) {
            $person = $response['data'][0] ?? null;

            // If contact is found, update relevant post meta fields
            if ($person) {
                // Update basic information
                update_post_meta($post_id, 'person_name', sanitize_text_field($person['givenName'] . ' ' . $person['familyName']));
                update_post_meta($post_id, 'person_email', sanitize_email($person['email'] ?? ''));
                update_post_meta($post_id, 'person_telephone', sanitize_text_field($person['telephone'] ?? ''));
                update_post_meta($post_id, 'person_givenName', sanitize_text_field($person['givenName'] ?? ''));
                update_post_meta($post_id, 'person_familyName', sanitize_text_field($person['familyName'] ?? ''));
                update_post_meta($post_id, 'person_honorificPrefix', sanitize_text_field($person['honorificPrefix'] ?? ''));
                update_post_meta($post_id, 'person_honorificSuffix', sanitize_text_field($person['honorificSuffix'] ?? ''));

                // Process organizations, functions, workplaces, and addresses
                $contacts = array();
                if (isset($person['contacts']) && is_array($person['contacts'])) {
                    foreach ($person['contacts'] as $contactInfo) {
                        $org_name = $contactInfo['organization']['name'] ?? '';
                        $org_identifier = $contactInfo['organization']['identifier'] ?? '';
                        $function_en = $contactInfo['functionLabel']['en'] ?? '';
                        $function_de = $contactInfo['functionLabel']['de'] ?? '';

                        // Fetch workplace, address, and socials for this contact
                        $workplace = fetch_and_format_workplaces($contactInfo['identifier'] ?? '');
                        $address = fetch_and_format_address($org_identifier);
                        $socials = fetch_and_format_socials($contactInfo['identifier'] ?? '');

                        // Add each organization as a separate entry
                        $contacts[] = array(
                            'organization' => $org_name,
                            'organization_id' => $org_identifier,
                            'function_en' => $function_en,
                            'function_de' => $function_de,
                            'workplace' => $workplace,
                            'address' => $address,
                            'socials' => $socials,
                        );
                    }
                }

                // Save the organizations array as post meta
                update_post_meta($post_id, 'person_contacts', $contacts);
            } else {
                // If API response is not successful, log error or notify the user as needed
                // error_log(sprintf(__('Error fetching person attributes: %s', 'rrze-faudir'), wp_json_encode($response)));
            }
        }
    }

    // List of fields to save from the form
    $fields = [
        '_content_en',
        '_teasertext_en',
        '_teasertext_de',
        'person_id',
        'person_name',
        'person_email',
        'person_telephone',
        'person_givenName',
        'person_familyName',
        'person_honorificPrefix',
        'person_honorificSuffix',
        'person_titleOfNobility',
    ];

    // Save each field
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }

    // Save displayed_contacts
    
    update_post_meta($post_id, 'displayed_contacts', intval($_POST['displayed_contacts']));
    /*
    
    if (isset($_POST['displayed_contacts']) && is_array($_POST['displayed_contacts'])) {
        // Sanitize and save the displayed contacts
        $displayed_contacts = array_map('intval', $_POST['displayed_contacts']);
        update_post_meta($post_id, 'displayed_contacts', $displayed_contacts);
    } else {
        // If no contacts are selected, save an empty array
        update_post_meta($post_id, 'displayed_contacts', []);
    }
     * 
     */
}


add_action('save_post', 'save_person_additional_fields');

// TODO:
// Take a look if this is not handled in admin.js - file js/custom-person.js isnt found anyway

function enqueue_custom_person_scripts($hook) {
    // Only load on post edit screens for our custom post type
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        global $post;
        if ($post && $post->post_type === 'custom_person') {
            wp_enqueue_script(
                'custom-person-script',
                plugins_url('/js/custom-person.js', dirname(__FILE__)),
                array('jquery'),
                null,
                true
            );
            wp_localize_script('custom-person-script', 'customPerson', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('custom_person_nonce')
            ));
        }
    }
}
// add_action('admin_enqueue_scripts', 'enqueue_custom_person_scripts');

function fetch_person_attributes() {
    check_ajax_referer('custom_person_nonce', 'nonce');

    $person_id = sanitize_text_field($_POST['person_id']);

    if (!empty($person_id)) {
        $params = ['identifier' => $person_id];
        $response = fetch_fau_persons(60, 0, $params);

        if (is_array($response) && isset($response['data'])) {
            $person = $response['data'][0] ?? null;

            if ($person) {
                // Process organizations and functions
                $contacts = array();
                if (isset($person['contacts']) && is_array($person['contacts'])) {
                    foreach ($person['contacts'] as $contactInfo) {
                        $org_name = $contactInfo['organization']['name'] ?? '';
                        $org_identifier = $contactInfo['organization']['identifier'] ?? '';
                        $function_en = $contactInfo['functionLabel']['en'] ?? '';
                        $function_de = $contactInfo['functionLabel']['de'] ?? '';

                        // Add each organization with its functions as a new entry
                        $contacts[] = array(
                            'organization' => $org_name,
                            'organization_id' => $org_identifier,
                            'function_en' => $function_en,
                            'function_de' => $function_de
                        );
                    }
                }

                wp_send_json_success(array(
                    'person_name' => sanitize_text_field($person['givenName'] . ' ' . $person['familyName']),
                    'person_email' => sanitize_email($person['email'] ?? ''),
                    'person_telephone' => sanitize_email($person['telephone'] ?? ''),
                    'person_givenName' => sanitize_text_field($person['givenName'] ?? ''),
                    'person_familyName' => sanitize_text_field($person['familyName'] ?? ''),
                    'person_honorificSuffix' => sanitize_text_field($person['honorificSuffix'] ?? ''),
                    'person_honorificPrefix' => sanitize_text_field($person['honorificPrefix'] ?? ''),
                    'organizations' => $contacts
                ));
            } else {
                wp_send_json_error(__('No contact found.', 'text-domain'));
            }
        } else {
            wp_send_json_error(__('Error fetching person attributes.', 'text-domain'));
        }
    } else {
        wp_send_json_error(__('Invalid person ID.', 'text-domain'));
    }
}
add_action('wp_ajax_fetch_person_attributes', 'fetch_person_attributes');

// Add this function at the end of the file
function rrze_faudir_create_custom_person() {
    // error_log('rrze_faudir_create_custom_person called');
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $person_name = isset($_POST['person_name']) ? sanitize_text_field($_POST['person_name']) : '';
    $person_id = isset($_POST['person_id']) ? sanitize_text_field($_POST['person_id']) : '';
    $includeDefaultOrg = isset($_POST['include_default_org']) && $_POST['include_default_org'] === '1';

    if (empty($person_name) || empty($person_id)) {
        wp_send_json_error('Invalid person data');
        return;
    }

    $post_id = wp_insert_post(array(
        'post_title'    => $person_name,
        'post_type'     => 'custom_person',
        'post_status'   => 'publish'
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error($post_id->get_error_message());
        return;
    }

    // Set the person_id meta field
    update_post_meta($post_id, 'person_id', $person_id);

    // Fetch additional person attributes
    $params = ['identifier' => $person_id];
    $response = fetch_fau_persons(60, 0, $params);

    if (is_array($response) && isset($response['data'])) {
        $person = $response['data'][0] ?? null;

        if ($person) {
            // Update basic post meta
            update_post_meta($post_id, 'person_name', sanitize_text_field($person['givenName'] . ' ' . $person['familyName']));
            update_post_meta($post_id, 'person_email', sanitize_email($person['email'] ?? ''));
            update_post_meta($post_id, 'person_telephone', sanitize_email($person['telephone'] ?? ''));
            update_post_meta($post_id, 'person_givenName', sanitize_text_field($person['givenName'] ?? ''));
            update_post_meta($post_id, 'person_familyName', sanitize_text_field($person['familyName'] ?? ''));
            update_post_meta($post_id, 'person_honorificPrefix', sanitize_text_field($person['honorificPrefix'] ?? ''));
            update_post_meta($post_id, 'person_honorificPrefix', sanitize_text_field($person['honorificPrefix'] ?? ''));
            update_post_meta($post_id, 'person_titleOfNobility', sanitize_text_field($person['titleOfNobility'] ?? ''));

            // Process organizations and functions
            $contacts = array();
            if (isset($person['contacts']) && is_array($person['contacts'])) {
                // Get default organization settings
                $options = get_option('rrze_faudir_options', array());
                $defaultOrg = $options['default_organization'] ?? null;
                $defaultOrgIds = $defaultOrg ? $defaultOrg['ids'] : [];

                // Filter contacts based on default organization
                $filteredContacts = FaudirUtils::filterContactsByCriteria(
                    $person['contacts'],
                    $includeDefaultOrg,
                    $defaultOrgIds,
                    '' // email (empty since we're not filtering by email here)
                );

                foreach ($filteredContacts as $contact) {
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
            }

            // Save the organizations array as post meta
            update_post_meta($post_id, 'person_contacts', $contacts);
            // Handle displayed_contacts
            
            /*
            if (isset($_POST['displayed_contacts']) && is_array($_POST['displayed_contacts'])) {
                // Sanitize and save the displayed contacts
                $displayed_contacts = array_map('intval', $_POST['displayed_contacts']);
            } else {
                // Default to the first contact if not provided
                $displayed_contacts = !empty($contacts) ? [0] : [];
            }

            update_post_meta($post_id, 'displayed_contacts', $displayed_contacts);
            */
            update_post_meta($post_id, 'displayed_contacts', intval($_POST['displayed_contacts']));

            // Return success with both post ID and edit URL
            wp_send_json_success(array(
                'post_id' => $post_id,
                'edit_url' => get_edit_post_link($post_id, 'url'), // Add the edit URL
                'message' => __('Custom person created successfully!', 'rrze-faudir')
            ));
            return;
        }
    }

    // If we get here, something went wrong with the API response
    wp_send_json_error(array(
        'message' => __('Error creating custom person: Failed to fetch person details.', 'rrze-faudir')
    ));
}
add_action('wp_ajax_rrze_faudir_create_custom_person', 'rrze_faudir_create_custom_person');

// Add this function to register the meta field for REST API
function register_person_meta()
{
    register_post_meta('custom_person', 'person_id', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
}
add_action('init', 'register_person_meta');
// Make sure categories are visible in REST API
// Update the REST API response to include custom taxonomy
function add_taxonomy_to_person_rest($response, $post, $request)
{
    if ($post->post_type === 'custom_person') {
        // Get custom taxonomy terms
        $terms = wp_get_object_terms($post->ID, 'custom_taxonomy');
        $term_ids = array_map(function ($term) {
            return $term->term_id;
        }, $terms);

        // Add taxonomy terms to response
        $response->data['custom_taxonomy'] = $term_ids;

        // Add other meta data
        $person_id = get_post_meta($post->ID, 'person_id', true);
        $person_name = get_post_meta($post->ID, 'person_name', true);

        $response->data['meta'] = array_merge(
            $response->data['meta'] ?? [],
            [
                'person_id' => $person_id,
                'person_name' => $person_name
            ]
        );
    }
    return $response;
}
add_filter('rest_prepare_custom_person', 'add_taxonomy_to_person_rest', 10, 3);
