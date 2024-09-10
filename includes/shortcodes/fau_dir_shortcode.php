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
    }elseif ($atts['format'] === 'card') {
        $output .= '<div class="shortcode-contacts-wrapper">';
    }
    else {
        $output .= '<div>';
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
    $person_id = isset($person['identifier']) ? esc_html($person['identifier']) : 'N/A';
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
    }elseif($format ==='card'){
        $output .= '<div class="shortcode-contact-card">';
        $output .= '<img src="/wp-content/uploads/2024/09/V20210305LJ-0043-cropped-e1725968539245.webp">';
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .=  '<h2>' . $fullName . ' </h2>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<h3>' . $function. '</h3>';
        $output .= '</div>';
    }elseif($format ==='page' ){
        $output .= '<div class="contact-page"><div class="contact-page-img-container">';
        $output .= '<div style="flex-grow: 1; max-width:70%">'; 
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .=  '<h2>' . $fullName . ' </h2>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<h3>' . $function. '</h3>';
        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= '<p>Organization: ' . $organization_name . '</p>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<p>Email: ' . $email . '</p>';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<p>Phone: ' . $phone . '</p>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<p>Email: ' . $email . '</p>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<h3>Meine Sprechzeiten </h3>';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<p>Überall dieselbe alte Leier. Das Layout ist fertig, der Text lässt auf sich warten. Damit das Layout nun nicht nackt im Raume steht und sich klein und leer vorkommt, springe ich ein: der Blindtext.
        Täglich Mo, 08:00 - 10:00, Raum 00.456, Bitte vorher anmelden! ' . $phone . '</p>';$output .= '</div>'; 
        $output .= '<img src="/wp-content/uploads/2024/09//V20210305LJ-0043-cropped.webp">';
       $output .= '</div>';
       if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<h3>Mustertext Biographie:</h3>';
       if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<p>Überall dieselbe alte Leier. Das Layout ist fertig, der Text lässt auf sich warten. Damit das Layout nun nicht nackt im Raume steht und sich klein und leer vorkommt, springe ich ein: der Blindtext. Genau zu diesem Zwecke erschaffen, immer im Schatten meines großen Bruders »Lorem Ipsum«, freue ich mich jedes Mal, wenn Sie ein paar Zeilen lesen. Denn esse est percipi – Sein ist wahrgenommen werden. Und weil Sie nun schon die Güte haben, mich ein paar weitere Sätze lang zu begleiten, möchte ich diese Gelegenheit nutzen, Ihnen nicht nur als Lückenfüller zu dienen, sondern auf etwas hinzuweisen, das es ebenso verdient wahrgenommen zu werden: Webstandards nämlich. Sehen Sie, Webstandards sind das Regelwerk, auf dem Webseiten aufbauen. So gibt es Regeln für HTML, CSS, JavaScript oder auch XML; Worte, die Sie vielleicht schon einmal von Ihrem Entwickler gehört haben. Diese Standards sorgen dafür, dass alle Beteiligten aus einer Webseite den größten Nutzen ziehen. Im Gegensatz zu früheren Webseiten müssen wir zum Beispiel nicht mehr zwei verschiedene Webseiten für den Internet Explorer und einen anderen Browser programmieren. Es reicht eine Seite, die – richtig angelegt – sowohl auf verschiedenen Browsern im Netz funktioniert, aber ebenso gut für den Ausdruck oder die Darstellung auf einem Handy geeignet ist. Wohlgemerkt: Eine Seite für alle Formate. Was für eine Erleichterung. Standards sparen Zeit bei den Entwicklungskosten und sorgen dafür, dass sich Webseiten später leichter pflegen lassen. Natürlich nur dann, wenn sich alle an diese Standards halten. Das gilt für Browser wie Firefox, Opera: ' . $phone . '</p>';
       
       $output .= '</div>';
        
    }
    else{
        $output .= '<div  class="shortcode-contact-kompakt">';
        $output .= '<img src="/wp-content/uploads/2024/09/V20210305LJ-0043-cropped.webp">';
        $output .= '<div style="flex-grow: 1;">'; 
        if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) $output .=  '<h2>' . $fullName . ' </h2>';
        if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) $output .= '<h3>' . $function. '</h3>';
        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) $output .= '<p>Organization: ' . $organization_name . '</p>';
        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) $output .= '<p>Email: ' . $email . '</p>';
        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) $output .= '<p>Phone: ' . $phone . '</p>';
        //to be implemented after CPT
        $output .= '<a href="?id=' . $person_id . '"><button>More</button></a>';

        $output .= '</div>';
        $output .= '</div>';
    }
    return $output;
}

// Register the shortcode
add_shortcode('faudir', 'fetch_fau_data');

?>