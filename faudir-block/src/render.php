<?php
/**
 * Block Render File
 *
 * Renders the shortcode output and ensures it works in both the block editor and front end.
 */

// Initialize shortcode parameters array
$shortcode_params = array();

// Map attributes to parameters
$attributes_map = [
    'selectedCategory'  => 'category',
    'selectedPersonIds' => 'identifier',
    'selectedFields'    => 'show',
    'selectedFormat'    => 'format',
    'groupId'           => 'groupid',
    'functionField'     => 'function',
    'organizationNr'    => 'orgnr',
    'url'               => 'url',
];

// Process and sanitize attributes
foreach ($attributes_map as $attr_key => $param_name) {
    if (!empty($attributes[$attr_key])) {
        $value = $attributes[$attr_key];
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $shortcode_params[] = $param_name . '=' . esc_attr($value);
    }
}

// Build the shortcode string
$shortcode = '[faudir ' . implode(' ', $shortcode_params) . ']';

// Render block content for both editor and front end
$shortcode_output = do_shortcode($shortcode);

// Check if we are in the block editor using a more compatible method
$is_block_editor = defined('REST_REQUEST') && REST_REQUEST && isset($_GET['context']) && $_GET['context'] === 'edit';

if ($is_block_editor) {
    // In the block editor, we'll still show the actual shortcode output
    // instead of just a loading message, for better preview
    $shortcode_output = do_shortcode($shortcode);
}

?>
<p <?php echo get_block_wrapper_attributes(); ?>>
    <?php 
    // Display the shortcode output for both editor and front end
    echo wp_kses_post($shortcode_output);
    ?>
</p>
