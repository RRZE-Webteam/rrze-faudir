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
        return fetch_fau_data($attributes); // Pass the attributes, including image, to the shortcode function
    }
}


// Register the block on init
add_action('init', function() {
    FaudirBlock::register();
});
?>