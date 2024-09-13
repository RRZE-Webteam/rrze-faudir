<?php

// Block handler for RRZE FAUDIR
class FaudirBlock {
    public static function register() {
        register_block_type('rrze/faudir-block', [
            'render_callback' => [self::class, 'render'],
            'attributes' => [
                'category' => ['type' => 'string', 'default' => ''],
                'identifier' => ['type' => 'string', 'default' => ''],
                'format' => ['type' => 'string', 'default' => 'list'],
                'show' => ['type' => 'string', 'default' => 'name, email, phone, organization, function'],
                'hide' => ['type' => 'string', 'default' => ''],
                'image' => ['type' => 'number', 'default' => 0], // Add the image attribute
            ],
        ]);
    }

    public static function render($attributes) {
        // Generate a unique cache key based on block attributes
        $cache_key = 'faudir_block_' . md5(serialize($attributes));

        // Retrieve cache timeout from plugin settings (default to 15 minutes if not set)
        $options = get_option('rrze_faudir_options');
        $cache_timeout = isset($options['cache_timeout']) ? intval($options['cache_timeout']) * 60 : 900; // Cache timeout in seconds

        // Check if cached data exists
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data; // Return cached data if available
        }

        // Call the function from shortcode to fetch data
        $output = fetch_fau_data($attributes);

        // Cache the rendered output using Transients API
        set_transient($cache_key, $output, $cache_timeout);

        return $output;
    }
}


// Register the block on init
add_action('init', function() {
    FaudirBlock::register();
});

?>