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

// Get the output of the shortcode
$shortcode_output = do_shortcode($shortcode);

// Render block content for both editor and front end
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
    <?php 
    // Display the shortcode output for both editor and front end
    echo wp_kses_post($shortcode_output);
    ?>
</p>
