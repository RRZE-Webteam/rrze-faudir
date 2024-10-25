<?php 
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
        'supports'           => array('title', 'editor', 'thumbnail'),
        'taxonomies'         => array('custom_taxonomy'),
        'show_in_rest'       => true,
        'menu_position'      => 5,
        'capability_type'    => 'post',
    );

    register_post_type('custom_person', $args); // Keep 'custom_person' as the post type key
}
add_action('init', 'register_custom_person_post_type', 15);


function register_custom_taxonomy() {
    // Register the taxonomy
    register_taxonomy(
        'custom_taxonomy', // Taxonomy slug
        'custom_person', // Custom Post Type to attach the taxonomy
        array(
            'labels' => array(
                'name'              => __( 'Categories', 'text-domain' ),
                'singular_name'     => __( 'Category', 'text-domain' ),
                'search_items'      => __( 'Search Categories', 'text-domain' ),
                'all_items'         => __( 'All Categories', 'text-domain' ),
                'parent_item'       => __( 'Parent Category', 'text-domain' ),
                'parent_item_colon' => __( 'Parent Category:', 'text-domain' ),
                'edit_item'         => __( 'Edit Category', 'text-domain' ),
                'update_item'       => __( 'Update Category', 'text-domain' ),
                'add_new_item'      => __( 'Add New Category', 'text-domain' ),
                'new_item_name'     => __( 'New Category Name', 'text-domain' ),
                'menu_name'         => __( 'Categories', 'text-domain' ),
            ),
            'hierarchical'      => true, // Set true for a category-like taxonomy, false for tags.
            'public'            => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_quick_edit'=> true,
            'meta_box_cb'       => null, // Use default meta box
            'show_admin_column' => true, // Show taxonomy in the admin list table.
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'category' ),
        )
    );
}
add_action( 'init', 'register_custom_taxonomy' );

function add_taxonomy_meta_box() {
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
add_action('add_meta_boxes', 'add_taxonomy_meta_box');

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

// Render Meta Box Fields
function render_person_additional_fields($post) {
    wp_nonce_field('save_person_additional_fields', 'person_additional_fields_nonce');

    $api_fields = [
        'person_name',
        'person_email',
        'person_given_name',
        'person_family_name',
        'person_title',
        'person_organization',
        'person_function',
    ];

    $fields = [
        '_content_en' => __('Content (English)', 'rrze-faudir'),
        '_teasertext_en' => __('Teaser Text (English)', 'rrze-faudir'),
        '_teasertext_de' => __('Teaser Text (German)', 'rrze-faudir'),
        'person_id' => __('Person ID', 'rrze-faudir'),
        'person_name' => __('Name', 'rrze-faudir'),
        'person_email' => __('Email', 'rrze-faudir'),
        'person_telephone' => __('Telephone', 'rrze-faudir'),
        'person_given_name' => __('Given Name', 'rrze-faudir'),
        'person_family_name' => __('Family Name', 'rrze-faudir'),
        'person_title' => __('Title', 'rrze-faudir'),
        'person_suffix' => __('Suffix', 'rrze-faudir'),
        'person_nobility_name' => __('Nobility Name', 'rrze-faudir'),
        'person_organization' => __('Organization', 'rrze-faudir'),
        'person_function' => __('Function (English)', 'rrze-faudir'),
    ];

    foreach ($fields as $meta_key => $label) {
        $value = get_post_meta($post->ID, $meta_key, true);

        // Determine if the field should be readonly
        $readonly = in_array($meta_key, $api_fields) ? 'readonly' : '';

        // Check if the field should be rendered as a textarea
        if (in_array($meta_key, ['_content_en', '_teasertext_en', '_teasertext_de'])) {
            echo "<label for='" . esc_attr($meta_key) . "'>" . esc_html($label) . "</label>";
            echo "<textarea name='" . esc_attr($meta_key) . "' id='" . esc_attr($meta_key) . "' style='width: 100%; height: 100px;' $readonly>" . esc_textarea($value) . "</textarea><br><br>";
        } else {
            // Render as a regular text input field
            echo "<label for='" . esc_attr($meta_key) . "'>" . esc_html($label) . "</label>";
            echo "<input type='text' name='" . esc_attr($meta_key) . "' id='" . esc_attr($meta_key) . "' value='" . esc_attr($value) . "' style='width: 100%;' $readonly /><br><br>";
        }
    }
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
            /* translators: %s: JSON-encoded response */
            /* error_log(sprintf(__('Error fetching person attributes: %s', 'rrze-faudir'), wp_json_encode($response)));*/

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
        'person_given_name',
        'person_family_name',
        'person_title',
        'person_suffix',
        'person_nobility_name',
        'person_organization',
        'person_function',
    ];

    // Save each field
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
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

    if (isset($_POST['person_id'])) {
        $person_id = sanitize_text_field(wp_unslash($_POST['person_id']));
    }    

    if (!empty($person_id)) {
        $params = ['identifier' => $person_id];
        $response = fetch_fau_persons_atributes(60, 0, $params);

        if (is_array($response) && isset($response['data'])) {
            $contact = $response['data'][0] ?? null;

            if ($contact) {
                wp_send_json_success(array(
                    'person_name' => sanitize_text_field($contact['givenName'] . ' ' . $contact['familyName']),
                    'person_email' => sanitize_email($contact['email'] ?? ''),
                    'person_given_name' => sanitize_text_field($contact['givenName'] ?? ''),
                    'person_family_name' => sanitize_text_field($contact['familyName'] ?? ''),
                    'person_title' => sanitize_text_field($contact['personalTitle'] ?? ''),
                    'person_organization' => sanitize_text_field($contact['contacts'][0]['organization']['name'] ?? ''),
                    'person_function' => sanitize_text_field($contact['contacts'][0]['functionLabel']['en'] ?? ''),
                    

                    // Add other fields as needed
                ));
            } else {
                wp_send_json_error(__('No contact found.', 'rrze-faudir'));
            }
        } else {
            wp_send_json_error(__('Error fetching person attributes.', 'rrze-faudir'));
        }
    } else {
        wp_send_json_error(__('Invalid person ID.', 'rrze-faudir'));
    }
}
add_action('wp_ajax_fetch_person_attributes', 'fetch_person_attributes');

// Add this function at the end of the file
function rrze_faudir_create_custom_person() {
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $person_name = isset($_POST['person_name']) ? sanitize_text_field($_POST['person_name']) : '';
    $person_id = isset($_POST['person_id']) ? sanitize_text_field($_POST['person_id']) : '';

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
    $response = fetch_fau_persons_atributes(60, 0, $params);

    if (is_array($response) && isset($response['data'])) {
        $contact = $response['data'][0] ?? null;

        if ($contact) {
            // Update post meta with fetched data
            update_post_meta($post_id, 'person_name', sanitize_text_field($contact['givenName'] . ' ' . $contact['familyName']));
            update_post_meta($post_id, 'person_email', sanitize_email($contact['email'] ?? ''));
            update_post_meta($post_id, 'person_given_name', sanitize_text_field($contact['givenName'] ?? ''));
            update_post_meta($post_id, 'person_family_name', sanitize_text_field($contact['familyName'] ?? ''));
            update_post_meta($post_id, 'person_title', sanitize_text_field($contact['personalTitle'] ?? ''));
            update_post_meta($post_id, 'person_organization', sanitize_text_field($contact['contacts'][0]['organization']['name'] ?? ''));
            update_post_meta($post_id, 'person_function', sanitize_text_field($contact['contacts'][0]['functionLabel']['en'] ?? ''));
        }
    }

    wp_send_json_success(array('post_id' => $post_id));
}
add_action('wp_ajax_rrze_faudir_create_custom_person', 'rrze_faudir_create_custom_person');
?>
