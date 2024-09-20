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
            'url' => '',
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

function fetch_and_render_fau_data($atts) {
    // Convert 'show' and 'hide' attributes into arrays
    $show_fields = array_map('trim', explode(',', $atts['show']));
    $hide_fields = array_map('trim', explode(',', $atts['hide']));

    // Prepare parameters for fetching data
    $identifiers = empty($atts['identifier']) ? [] : explode(',', $atts['identifier']);
    $category = $atts['category'];
    $image_id = $atts['image'];
    $url = $atts['url'];

    // Fetch data logic
    $persons = []; // This will hold the fetched data

    // Fetch data based on the given attributes
    if (!empty($identifiers)) {
        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);
            $params = ['identifier' => $identifier];
            $data = fetch_fau_persons_atributes(0, 0, $params);
            if (!empty($data['data'])) {
                $persons[] = $data['data'][0];
            }
        }
    } elseif (!empty($category)) {
        $lq = 'contacts.organization.name[eq]=' . urlencode($category);
        $params = ['lq' => $lq];
        $data = fetch_fau_persons_atributes(0, 0, $params);
        if (!empty($data['data'])) {
            $persons = $data['data'];
        }
    } else {
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

    // Sorting logic based on the specified sorting options
    $sort_option = $atts['sort'] ?? 'last_name'; // Default sorting option is by last name

    // Create a collator object for locale-based sorting
    $collator = collator_create('de_DE'); // German locale; adjust as needed

    // Sorting function based on the chosen option
    usort($persons, function ($a, $b) use ($sort_option, $collator, $identifiers) {
        switch ($sort_option) {
            case 'title_last_name':
                // Sorting first by academic titles, then by last name
                $academic_titles = ['Prof. Dr.', 'Dr.', 'Prof.', '']; // Define title order

                $a_title = $a['personalTitle'] ?? '';
                $b_title = $b['personalTitle'] ?? '';

                $a_title_pos = array_search($a_title, $academic_titles) !== false ? array_search($a_title, $academic_titles) : count($academic_titles);
                $b_title_pos = array_search($b_title, $academic_titles) !== false ? array_search($b_title, $academic_titles) : count($academic_titles);

                // First, compare academic titles
                if ($a_title_pos !== $b_title_pos) {
                    return $a_title_pos - $b_title_pos;
                }

                // If titles are the same, compare last names
                return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '');

            case 'identifier_order':
                // Sorting by the order of identifiers
                $a_index = array_search($a['identifier'] ?? '', $identifiers);
                $b_index = array_search($b['identifier'] ?? '', $identifiers);
                return $a_index - $b_index;

            default:
                // Default sorting by last name, considering special characters
                return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '');
        }
    });

    // Load the template and pass the sorted data
    $template_dir = plugin_dir_path(__FILE__) . '../../templates/';
    $template = new Template($template_dir);

    // Render the template based on the format
    return $template->render($atts['format'], [
        'show_fields' => $show_fields,
        'hide_fields' => $hide_fields,
        'persons' => $persons,
        'image_url' => $image_url,
        'url' => $url,
    ]);
}


add_shortcode('faudir', 'fetch_fau_data');



?>