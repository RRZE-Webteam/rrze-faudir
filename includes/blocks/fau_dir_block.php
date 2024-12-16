<?php

// Block handler for RRZE FAUDIR
class FaudirBlock
{
    public static function register()
    {
        // Get the default output fields using the utility function
        $default_show_fields = FaudirUtils::getDefaultOutputFields();

        // Convert to comma-separated string
        $default_show = implode(', ', $default_show_fields);
    }

    public static function render($attributes)
    {
        // error_log('[RRZE-FAUDIR] Rendering block');

        // Fetch and render data for the block
        $output = fetch_fau_data_for_block($attributes);

        return $output;
    }
}

// New function for fetching FAU data specifically for the block
function fetch_fau_data_for_block($attributes)
{
    // Ensure 'show' and 'hide' attributes are strings before exploding
    $show = is_string($attributes['show']) ? $attributes['show'] : '';
    $hide = is_string($attributes['hide']) ? $attributes['hide'] : '';

    // Convert 'show' and 'hide' attributes into arrays
    $show_fields = array_map('trim', explode(',', $show));
    $hide_fields = array_map('trim', explode(',', $hide));
    $url = $attributes['url'];
    // Prepare parameters for fetching data
    $identifiers = empty($attributes['identifier']) ? [] : (is_array($attributes['identifier']) ? $attributes['identifier'] : explode(',', $attributes['identifier']));
    $persons = []; // This will hold the fetched data

    // Skip fetching data if it's a REST, AJAX request, or if the block is being edited
    if (!(defined('REST_REQUEST') && REST_REQUEST) && !wp_doing_ajax() && !is_admin()) {
        // Fetch persons based on identifiers
        if (!empty($identifiers)) {
            $persons = process_persons_by_identifiers($identifiers);
        } else {
            $persons = fetch_and_process_persons();
        }
    }

    // Load the template and pass the sorted data
    $template_dir = plugin_dir_path(__FILE__) . '../../templates/';
    $template = new Template($template_dir);

    // Fix format assignment when empty
    if ($attributes['format'] === '') {
        $attributes['format'] = 'kompakt';  // Use single = for assignment
    }
    return $template->render($attributes['format'], [
        'show_fields' => $show_fields,
        'hide_fields' => $hide_fields,
        'url' => $url,
        'persons' => $persons,
    ]);
}

// Register the block on init
add_action('init', function () {
    FaudirBlock::register();
});

// Add this function to modify the REST API response
function add_person_id_to_rest($response, $post, $request)
{
    if ($post->post_type === 'custom_person') {
        $person_id = get_post_meta($post->ID, 'person_id', true);
        $response->data['person_id'] = $person_id;
    }
    return $response;
}
add_filter('rest_prepare_custom_person', 'add_person_id_to_rest', 10, 3);

// Am Anfang der Datei
function register_faudir_block_assets()
{
    // Register block script
    wp_register_script(
        'rrze-faudir-block',
        plugins_url('src/js/blocks/fau_dir_block.js', dirname(dirname(__FILE__))),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-api-fetch', 'wp-i18n')
    );

    wp_set_script_translations('rrze-faudir-block', 'rrze-faudir', plugin_dir_path(__FILE__) . '../../languages');

    // Register block style
    wp_register_style(
        'rrze-faudir-block-editor',
        plugins_url('css/fau_dir_block.css', dirname(__FILE__)),
        array('wp-edit-blocks')
    );

    // Register the block
    register_block_type('rrze/faudir-block', array(
        'api_version' => 3,
        'editor_script' => 'rrze-faudir-block',
        'editor_style' => 'rrze-faudir-block-editor',
        'render_callback' => array('FaudirBlock', 'render'),
        'supports' => array(
            'html' => false,
            'reusable' => true,
            'multiple' => true,
            'inserter' => true,
            'lock' => false,
        ),
        'attributes' => array(
            'identifier' => array('type' => 'array', 'default' => array()),
            'format' => array('type' => 'string', 'default' => 'kompakt'),
            'url' => array('type' => 'string', 'default' => ''),
            'show' => array('type' => 'string', 'default' => ''),
            'hide' => array('type' => 'string', 'default' => ''),
            'image' => array('type' => 'number', 'default' => 0),
            'groupid' => array('type' => 'string', 'default' => ''),
            'orgnr' => array('type' => 'string', 'default' => '')
        ),
        'example' => [
            'attributes' => [
                'format' => 'list',
                'show' => 'givenName, familyName, email',
                'identifier' => 'preview'
            ],
        ],
    ));
}
add_action('init', 'register_faudir_block_assets');

function add_faudir_block_category($categories)
{
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'rrze-blocks',
                'title' => __('RRZE Blocks', 'rrze-faudir'),
            ),
        )
    );
}
add_filter('block_categories_all', 'add_faudir_block_category');

// Update the REST API response to include person_name
function add_person_meta_to_rest($response, $post, $request)
{
    if ($post->post_type === 'custom_person') {
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
add_filter('rest_prepare_custom_person', 'add_person_meta_to_rest', 10, 3);
