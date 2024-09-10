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
function fetch_fau_data($atts) {
    // Extract the attributes from the shortcode
    $atts = shortcode_atts(
        array(
            'category' => '',  // Filter by organization name
            'identifier' => '',  // Filter by person identifiers (comma-separated)
            'format' => 'list',  // List or table format
            'show' => 'name, email, phone, organization, function',  // Default fields to show
            'hide' => '',  // Default no fields hidden
        ),
        $atts
    );

    // Convert show and hide fields from comma-separated strings to arrays
    $show_fields = array_map('trim', explode(',', $atts['show']));
    $hide_fields = array_map('trim', explode(',', $atts['hide']));

    // Explode the identifiers into an array if any are provided
    $identifiers = empty($atts['identifier']) ? array() : explode(',', $atts['identifier']);
    $category = $atts['category'];  // Get the category (organization name) to filter by

    $output = '';

    if ($atts['format'] === 'table') {
        $output .= '<table class="fau-contacts-table-custom">';
        $output .= '<thead><tr>';

        // Define table headers dynamically based on the 'show' fields, excluding those in 'hide' fields
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .= '<th>Name</th>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<th>Email</th>';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<th>Phone</th>';
        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= '<th>Organization</th>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<th>Function</th>';

        $output .= '</tr></thead><tbody>';
    } elseif ($atts['format'] === 'list')  {
        $output .= '<ul class="fau-contacts-list-custom">';
    }
    else {
        $output .= '<div class="shortcode-contacts-wrapper">';
    }

    // Fetch data based on identifier, category, or all if neither is provided
    if (!empty($identifiers)) {
        // Fetch by identifier
        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);  // Remove whitespace

            // Use the fetch_fau_persons_atributes function to get data
            $params = ['identifier' => $identifier];
            $data = fetch_fau_persons_atributes(0, 0, $params);

            if (empty($data) || empty($data['data'])) {
                $output .= '<p>No data found for ID: ' . esc_html($identifier) . '</p>';
                continue;
            }

            // Fetch details from the data
            $person = $data['data'][0];  // Assuming the first entry contains the person's info
            $output .= format_person_data($person, $show_fields, $hide_fields, $atts['format']);
        }
    } elseif (!empty($category)) {
        // Build the LHS query string for filtering by category (organization name)
        $lq = 'contacts.organization.name[eq]=' . urlencode($category);

        // Use the `lq` parameter to fetch data based on the category
        $params = ['lq' => $lq];
        $data = fetch_fau_persons_atributes(0, 0, $params);  // Fetch all data with the `lq` filter

        if (empty($data) || empty($data['data'])) {
            $output .= '<p>No data found for category: ' . esc_html($category) . '</p>';
        } else {
            // Loop through all fetched people and display them based on category
            foreach ($data['data'] as $person) {
                $output .= format_person_data($person, $show_fields, $hide_fields, $atts['format']);
            }
        }
    } else {
        // Fetch all people if no identifier or category is provided
        $data = fetch_fau_persons_atributes(0, 0);  // Fetch all data

        if (empty($data) || empty($data['data'])) {
            $output .= '<p>No data found.</p>';
        } else {
            // Loop through all fetched people
            foreach ($data['data'] as $person) {
                $output .= format_person_data($person, $show_fields, $hide_fields, $atts['format']);
            }
        }
    }

    if ($atts['format'] === 'table') {
        $output .= '</tbody></table>';
    } elseif ($atts['format'] === 'list')  {
        $output .= '</ul>';
    }else{
        $output .= '</div>';
    }

    return $output;
}

// Helper function to format person data
function format_person_data($person, $show_fields, $hide_fields, $format) {
    $givenName = isset($person['givenName']) ? esc_html($person['givenName']) : 'N/A';
    $familyName = isset($person['familyName']) ? esc_html($person['familyName']) : 'N/A';
    $personalTitle = isset($person['personalTitle']) ? esc_html($person['personalTitle']) : '';
    $personalTitleSuffix = isset($person['personalTitleSuffix']) ? esc_html($person['personalTitleSuffix']) : '';
    $fullName = trim("$personalTitle $givenName $familyName $personalTitleSuffix");

    $email = isset($person['email']) ? esc_html($person['email']) : 'N/A';
    $phone = isset($person['telephone']) ? esc_html($person['telephone']) : 'N/A';
    $organization_name = isset($person['contacts'][0]['organization']['name']) ? esc_html($person['contacts'][0]['organization']['name']) : 'N/A';
    $function = isset($person['contacts'][0]['functionLabel']['en']) ? esc_html($person['contacts'][0]['functionLabel']['en']) : 'N/A';

    $output = '';

    if ($format === 'table') {
        $output .= '<tr>';
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .= '<td><strong>' . $fullName . '</strong></td>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<td>' . $email . '</td>';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<td>' . $phone . '</td>';
        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= '<td>' . $organization_name . '</td>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<td>' . $function . '</td>';
        $output .= '</tr>';
    } elseif ($format === 'list'){
        $output .= '<li>';
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .= '<strong>' . $fullName . ' </strong>(';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= 'Email: ' . $email ;
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields) && in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= ', ';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= 'Phone: ' . $phone;
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields) || in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= ')<br />';

        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= 'Organization: ' . $organization_name . '<br />';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= 'Function: ' . $function;
        $output .= '</li>';
    }else{
        $output .= '<div class="shortcode-contact-card">';
        $output .= '<img src="/wp-content/uploads/2024/09/image.jpg">';
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .=  '<h2>' . $fullName . ' </h2>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<h3>' . $function. '</h3>';
        $output .= '</div>';
    }
    return $output;
}

// Register the shortcode
add_shortcode('faudir', 'fetch_fau_data');

?>