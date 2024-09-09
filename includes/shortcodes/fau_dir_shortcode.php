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
function display_fau_contacts_custom_with_icons($atts) {
    // Attributes for the shortcode
    $atts = shortcode_atts(
        array(
            'category' => '',  // Filter by organization name
            'format' => 'list',  // List or table format
            'show' => 'name, prefix, suffix, email, mobile',  // Default fields to show
            'hide' => '',  // Default no fields hidden
            'limit' => 20,  // Number of items per page
            'offset' => 0,   // Starting point for the contacts list (for pagination)
        ),
        $atts,
        'fau_contacts_custom'
    );

    // Get the current page number from query parameter, default is 1
    $current_page = isset($_GET['page_num']) ? absint($_GET['page_num']) : 1;
    $offset = ($current_page - 1) * $atts['limit'];

    // Fetch contacts from the FAU API with pagination
    $contacts = fetch_fau_persons($atts['limit'], $offset);

    // Check if the data was retrieved successfully
    if (!is_array($contacts) || empty($contacts['data'])) {
        return '<p>No contacts found.</p>';
    }

    // Convert the 'show' and 'hide' attributes into arrays
    $show_fields = array_map('trim', explode(',', $atts['show']));
    $hide_fields = array_map('trim', explode(',', $atts['hide']));

    // Filter contacts based on category, if provided
    $filtered_contacts = array_filter($contacts['data'], function($contact) use ($atts) {
        $organization_name = isset($contact['contacts'][0]['organization']['name']) ? esc_html($contact['contacts'][0]['organization']['name']) : '';
        return empty($atts['category']) || $organization_name === $atts['category'];
    });

    // If no contacts match the category, return a message
    if (empty($filtered_contacts)) {
        return '<p>No contacts found for the selected category.</p>';
    }

    // Prepare output based on the format
    $output = '';
    if ($atts['format'] === 'table') {
        $output .= '<table class="fau-contacts-table-custom">';
        $output .= '<thead><tr>';

        // Define table headers dynamically based on the 'show' fields
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .= '<th>Name</th>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<th>Email</th>';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<th>Phone</th>';
        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= '<th>Organization</th>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<th>Function</th>';

        $output .= '</tr></thead><tbody>';
    } else {
        $output .= '<ul class="fau-contacts-list-custom">';
    }

    // Loop through the filtered contacts and display them
    foreach ($filtered_contacts as $contact) {
        $givenName = isset($contact['givenName']) ? esc_html($contact['givenName']) : 'N/A';
        $familyName = isset($contact['familyName']) ? esc_html($contact['familyName']) : 'N/A';

        $personalTitle = isset($contact['personalTitle']) ? esc_html($contact['personalTitle']) : '';
        $personalTitleSuffix = isset($contact['personalTitleSuffix']) ? esc_html($contact['personalTitleSuffix']) : '';
        $fullName = trim("$personalTitle $givenName $familyName $personalTitleSuffix");

        $email = isset($contact['email']) ? esc_html($contact['email']) : 'N/A';
        $phone = isset($contact['telephone']) ? esc_html($contact['telephone']) : 'N/A';

        $organization_name = isset($contact['contacts'][0]['organization']['name']) ? esc_html($contact['contacts'][0]['organization']['name']) : 'N/A';
        $function = isset($contact['contacts'][0]['functionLabel']['en']) ? esc_html($contact['contacts'][0]['functionLabel']['en']) : 'N/A';

        // Generate the output based on the format (list or table)
        if ($atts['format'] === 'table') {
            $output .= '<tr>';
            if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .= '<td><strong>' . $fullName . '</strong></td>';
            if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<td>' . $email . '</td>';
            if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<td>' . $phone . '</td>';
            if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= '<td>' . $organization_name . '</td>';
            if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<td>' . $function . '</td>';
            $output .= '</tr>';
        } else {
            $output .= '<li>';
            if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .= '<strong>' . $fullName . ' </strong>(';
            if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= 'Email: ' . $email ;
            if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= ', Phone: ' . $phone ;
            if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= ')<br />Organization: ' . $organization_name . '<br />';
            if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= 'Function: ' . $function ;
            $output .= '</li>';
        }
    }

    if ($atts['format'] === 'table') {
        $output .= '</tbody></table>';
    } else {
        $output .= '</ul>';
    }

    // Pagination logic
    $total_contacts_in_category = count($filtered_contacts); // Count of contacts in the filtered category
    $total_contacts = $contacts['pagination']['total']; // Total number of contacts

    // Show pagination only if there's no category or if more than 1 page of contacts exists in the category
    if (empty($atts['category']) || $total_contacts_in_category > $atts['limit']) { 
        $total_pages = ceil($total_contacts / $atts['limit']);
        $output .= '<div class="pagination">';
        if ($current_page > 1) {
            $output .= '<a href="?page_num=' . ($current_page - 1) . '">&laquo; Previous</a> ';
        }
        if ($current_page < $total_pages) {
            $output .= '<a href="?page_num=' . ($current_page + 1) . '">Next &raquo;</a>';
        }
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('faudir', 'display_fau_contacts_custom_with_icons');

?>