<?php 
// Register the Custom Post Type
function register_custom_person_post_type() {
    $args = array(
        'labels' => array(
            'name'               => __('Persons', 'text-domain'),
            'singular_name'      => __('Person', 'text-domain'),
            'menu_name'          => __('Persons', 'text-domain'),
            'add_new_item'       => __('Add New Person', 'text-domain'),
            'edit_item'          => __('Edit Person', 'text-domain'),
            'view_item'          => __('View Person', 'text-domain'),
            'all_items'          => __('All Persons', 'text-domain'),
            'search_items'       => __('Search Persons', 'text-domain'),
            'not_found'          => __('No persons found.', 'text-domain'),
        ),
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'person'),
        'supports'           => array('title', 'editor', 'thumbnail'),
        'show_in_rest'       => true,
        'menu_position'      => 5,
        'capability_type'    => 'post',
    );
    register_post_type('custom_person', $args);
}
add_action('init', 'register_custom_person_post_type');

// Add Meta Boxes
function add_custom_person_meta_boxes() {
    add_meta_box(
        'person_additional_fields',
        __('Additional Fields', 'text-domain'),
        'render_person_additional_fields',
        'custom_person',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_person_meta_boxes');

// Render Meta Box Fields
function render_person_additional_fields($post) {
    wp_nonce_field('save_person_additional_fields', 'person_additional_fields_nonce');

    $fields = [
        '_content_lang' => __('Content (Second Language)', 'text-domain'),
        '_teasertext_lang' => __('Teaser Text (Second Language)', 'text-domain'),
        'person_id' => __('Person ID', 'text-domain'),
        'person_name' => __('Name', 'text-domain'),
        'person_email' => __('Email', 'text-domain'),
        'person_telephone' => __('Telephone', 'text-domain'),
        'person_given_name' => __('Given Name', 'text-domain'),
        'person_family_name' => __('Family Name', 'text-domain'),
        'person_title' => __('Title', 'text-domain'),
        'person_pronoun' => __('Pronoun', 'text-domain'),
        'person_function' => __('Function', 'text-domain'),
    ];

    foreach ($fields as $meta_key => $label) {
        $value = get_post_meta($post->ID, $meta_key, true);
        echo "<label for='{$meta_key}'>{$label}</label>";
        echo "<input type='text' name='{$meta_key}' id='{$meta_key}' value='" . esc_attr($value) . "' style='width: 100%;' /><br><br>";
    }
}

function save_person_additional_fields($post_id) {
    // Verify nonce
    if (!isset($_POST['person_additional_fields_nonce']) || !wp_verify_nonce($_POST['person_additional_fields_nonce'], 'save_person_additional_fields')) {
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
    $person_id = sanitize_text_field($_POST['person_id'] ?? '');
    
    // If person_id is not empty, make an API call to fetch attributes
    if (!empty($person_id)) {
        // Set parameters for the API call
        $params = ['identifier' => $person_id];

        // Fetch person attributes using the custom function
        $response = fetch_fau_persons_atributes(60, 0, $params);

        // Check if the response is a valid array
        if (is_array($response) && isset($response['data'])) {
            $contact = $response['data'][0] ?? null; // Assuming you need the first contact from the response

            // If contact is found, update relevant post meta fields
            if ($contact) {
                update_post_meta($post_id, 'person_name', sanitize_text_field($contact['givenName'] . ' ' . $contact['familyName']));
                update_post_meta($post_id, 'person_email', sanitize_email($contact['email'] ?? ''));
                update_post_meta($post_id, 'person_title', sanitize_text_field($contact['personalTitle'] ?? ''));
                update_post_meta($post_id, 'person_function', sanitize_text_field($contact['functionLabel']['en'] ?? ''));

                // If more fields need updating based on API response, add them here
            }
        } else {
            // If API response is not successful, log error or notify the user as needed
            error_log(__('Error fetching person attributes: ' . json_encode($response), 'rrze-faudir'));
        }
    }

    // List of fields to save from the form
    $fields = [
        '_content_lang',
        '_teasertext_lang',
        'person_id',
        'person_name',
        'person_email',
        'person_telephone',
        'person_given_name',
        'person_family_name',
        'person_title',
        'person_pronoun',
        'person_function',
    ];

    // Save each field
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

add_action('save_post', 'save_person_additional_fields');

function enqueue_custom_person_scripts() {
    wp_enqueue_script('custom-person-script', get_template_directory_uri() . '/js/custom-person.js', array('jquery'), null, true);
    wp_localize_script('custom-person-script', 'customPerson', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('custom_person_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_custom_person_scripts');

function fetch_person_attributes() {
    check_ajax_referer('custom_person_nonce', 'nonce');

    $person_id = sanitize_text_field($_POST['person_id']);

    if (!empty($person_id)) {
        $params = ['identifier' => $person_id];
        $response = fetch_fau_persons_atributes(60, 0, $params);

        if (is_array($response) && isset($response['data'])) {
            $contact = $response['data'][0] ?? null;

            if ($contact) {
                wp_send_json_success(array(
                    'person_name' => sanitize_text_field($contact['givenName'] . ' ' . $contact['familyName']),
                    'person_email' => sanitize_email($contact['email'] ?? ''),
                    'person_title' => sanitize_text_field($contact['personalTitle'] ?? ''),
                    'person_function' => sanitize_text_field($contact['functionLabel']['en'] ?? ''),
                    // Add other fields as needed
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

?>