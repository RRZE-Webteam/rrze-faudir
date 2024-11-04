<?php

// Block handler for RRZE FAUDIR
class FaudirBlock {
    public static function register() {
        // Get the default output fields using the utility function
        $default_show_fields = FaudirUtils::getDefaultOutputFields();

        // Convert to comma-separated string
        $default_show = implode(', ', $default_show_fields);

        register_block_type('rrze/faudir-block', [
            'render_callback' => [self::class, 'render'],
            'attributes' => [

                'identifier' => ['type' => 'array', 'default' => []],
                'format' => ['type' => 'string', 'default' => 'list'],
                'url' => ['type' => 'string', 'default' => ''],
                'show' => ['type' => 'string', 'default' => $default_show],
                'hide' => ['type' => 'string', 'default' => ''],
                'image' => ['type' => 'number', 'default' => 0],
                'groupid' =>['type'=> 'string', 'default' => ''],
                'orgnr' =>['type'=> 'string', 'default' => ''],
            ],
            'supports' => [
                'html' => false
            ],
        ]);
    }

    public static function render($attributes) {
        // Ensure identifier is always an array
        $identifiers = isset($attributes['identifier']) 
            ? (array)$attributes['identifier'] 
            : [];

        // If no identifiers selected, show message
        if (empty($identifiers)) {
            return '<div class="wp-block-rrze-faudir-block">Please select at least one person.</div>';
        }

        $output = '';
        foreach ($identifiers as $identifier) {
            // Generate cache key for each person
            $cache_key = 'faudir_block_' . md5($identifier . serialize($attributes));
            
            // Check cache
            $cached_data = get_transient($cache_key);
            if ($cached_data !== false) {
                $output .= $cached_data;
                continue;
            }

            // Fetch and render person data
            $person_data = fetch_fau_data(array_merge($attributes, ['identifier' => $identifier]));
            if ($person_data) {
                set_transient($cache_key, $person_data, $cache_timeout);
                $output .= $person_data;
            }
        }

        return $output ?: '<div class="wp-block-rrze-faudir-block">No data found for selected persons.</div>';
    }
}


// Register the block on init
add_action('init', function() {
    FaudirBlock::register();
});



// Add this function to modify the REST API response
function add_person_id_to_rest($response, $post, $request) {
    if ($post->post_type === 'custom_person') {
        $person_id = get_post_meta($post->ID, 'person_id', true);
        $response->data['person_id'] = $person_id;
    }
    return $response;
}
add_filter('rest_prepare_custom_person', 'add_person_id_to_rest', 10, 3);

// Am Anfang der Datei
function register_faudir_block_assets() {
    // Register block script
    wp_register_script(
        'rrze-faudir-block',
        plugins_url('js/fau_dir_block.js', dirname(__FILE__)),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-api-fetch') // Added wp-api-fetch
    );

    // Register block style
    wp_register_style(
        'rrze-faudir-block-editor',
        plugins_url('css/fau_dir_block.css', dirname(__FILE__)),
        array('wp-edit-blocks')
    );

    // Register the block
    register_block_type('rrze/faudir-block', array(
        'editor_script' => 'rrze-faudir-block',
        'editor_style' => 'rrze-faudir-block-editor',
        'render_callback' => array('FaudirBlock', 'render'),
        'attributes' => array(
          
            'identifier' => array('type' => 'array', 'default' => array()),
            'format' => array('type' => 'string', 'default' => 'list'),
            'url' => array('type' => 'string', 'default' => ''),
            'show' => array('type' => 'string', 'default' => ''),
            'hide' => array('type' => 'string', 'default' => ''),
            'image' => array('type' => 'number', 'default' => 0),
            'groupid' => array('type' => 'string', 'default' => ''),
            'orgnr' => array('type' => 'string', 'default' => '')
        )
    ));
}
add_action('init', 'register_faudir_block_assets');

function add_faudir_block_category($categories) {
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
function add_person_meta_to_rest($response, $post, $request) {
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






?>