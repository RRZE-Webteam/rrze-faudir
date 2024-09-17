<?php
// Shortcode handler for RRZE FAUDIR

class FaudirShortcode {
    public static function register() {
        add_shortcode('faudir_shortcode', [self::class, 'render']);
    }

    public static function render($atts, $content = null) {
        return '<div class="faudir-shortcode">' . do_shortcode($content) . '</div>';
    }
}

include_once plugin_dir_path(__FILE__) . '../utils/Template.php';

// Shortcode function
function fetch_fau_data($atts) {
    // Extract the attributes from the shortcode
    $atts = shortcode_atts(
        array(
            'category' => '',
            'identifier' => '',
            'format' => 'list',
            'show' => 'name, email, phone, organization, function',
            'hide' => '',
            'image' => '',
        ),
        $atts
    );

    // Retrieve plugin options
    $options = get_option('rrze_faudir_options');
    $no_cache_logged_in = isset($options['no_cache_logged_in']) && $options['no_cache_logged_in'];

    // If user is logged in and no-cache option is enabled, always fetch fresh data
    if ($no_cache_logged_in && is_user_logged_in()) {
        return fetch_and_render_fau_data($atts);
    }

    // Generate a unique cache key based on the shortcode attributes
    $cache_key = 'faudir_shortcode_' . md5(serialize($atts));

    // Retrieve cache timeout from plugin settings (use default if not set)
    $cache_timeout = isset($options['cache_timeout']) ? intval($options['cache_timeout']) * 60 : 900; // Default to 15 minutes

    // Check if cached data exists
    $cached_data = get_transient($cache_key);
    if ($cached_data !== false) {
        return $cached_data; // Return cached data if available
    }

    // Fetch and render fresh data
    $output = fetch_and_render_fau_data($atts);

    // Cache the rendered output using Transients API
    set_transient($cache_key, $output, $cache_timeout);

    return $output;
}

// New function to fetch and render data
function fetch_and_render_fau_data($atts) {
    // Convert 'show' and 'hide' attributes into arrays
    $show_fields = array_map('trim', explode(',', $atts['show']));
    $hide_fields = array_map('trim', explode(',', $atts['hide']));

    // Prepare parameters for fetching data
    $identifiers = empty($atts['identifier']) ? [] : explode(',', $atts['identifier']);
    $category = $atts['category'];
    $image_id = $atts['image'];

    // Fetch data logic
    $persons = []; // This will hold the fetched data

    if (!empty($identifiers)) {
        // Fetch data by identifiers
        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);
            $params = ['identifier' => $identifier];
            $data = fetch_fau_persons_atributes(0, 0, $params);
            if (!empty($data['data'])) {
                $persons[] = $data['data'][0];
            }
        }
    } elseif (!empty($category)) {
        // Fetch data by category (organization name)
        $lq = 'contacts.organization.name[eq]=' . urlencode($category);
        $params = ['lq' => $lq];
        $data = fetch_fau_persons_atributes(0, 0, $params);
        if (!empty($data['data'])) {
            $persons = $data['data'];
        }
    } else {
        // Fetch all persons if no identifier or category is provided
        $data = fetch_fau_persons_atributes(0, 0);
        if (!empty($data['data'])) {
            $persons = $data['data'];
        }
    }

    // Fetch the image URL if an image ID is provided
    $image_url = '';
    if (!empty($image_id) && is_numeric($image_id)) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
    }

    // Load the template and pass the data
    $template_dir = plugin_dir_path(__FILE__) . '../../templates/';
    $template = new Template($template_dir);

    // Render the template based on the format
    return $template->render($atts['format'], [
        'show_fields' => $show_fields,
        'hide_fields' => $hide_fields,
        'persons' => $persons,
        'image_url' => $image_url,
    ]);
}

add_shortcode('faudir', 'fetch_fau_data');



?>