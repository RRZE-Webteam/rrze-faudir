<?php
// Shortcode handler for RRZE FAUDIR

class FaudirShortcode
{
    public static function register()
    {
        add_shortcode('faudir_shortcode', [self::class, 'render']);
    }

    public static function render($atts, $content = null)
    {
        return '<div class="faudir-shortcode">' . do_shortcode($content) . '</div>';
    }
}

include_once plugin_dir_path(__FILE__) . '../utils/Template.php';

// Shortcode function
function fetch_fau_data($atts)
{
    // Return early only if it's a shortcode call in admin area
    if (
        is_admin() ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    ) {
        return ''; // Return a placeholder for the editor/save operations
    }


    // Get the default output fields using the utility function
    $default_show_fields = FaudirUtils::getDefaultOutputFields();

    // Extract the attributes from the shortcode
    $atts = shortcode_atts(
        array(
            'category' => '',
            'identifier' => '',
            'format' => 'kompakt',
            'url' => '',
            'show' => '',
            'hide' => '',
            'image' => '',
            'groupid' => '',
            'orgnr' => '',
            'sort' => '',
            'button-text' => '',
        ),
        $atts
    );

    // Convert explicitly set 'show' values to an array and merge with default fields
    $explicit_show_fields = array_filter(array_map('trim', explode(',', $atts['show'])));
    $merged_show_fields = array_unique(array_merge($default_show_fields, $explicit_show_fields));
    $atts['show'] = implode(', ', $merged_show_fields);

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

function fetch_and_render_fau_data($atts)
{
    // Convert 'show' and 'hide' attributes into arrays
    $show_fields = array_map('trim', explode(',', $atts['show']));
    $hide_fields = array_map('trim', explode(',', $atts['hide']));

    // Prepare parameters for fetching data
    $identifiers = empty($atts['identifier']) ? [] : explode(',', $atts['identifier']);
    $category = $atts['category'];
    $image_id = $atts['image'];
    $url = $atts['url'];
    $groupid = $atts['groupid'];
    $orgnr = $atts['orgnr'];
    $persons = []; // This will hold the fetched data

    // Check for category in CPT first
    if (!empty($category)) {
        // Get persons from CPT with specified category
        $args = array(
            'post_type' => 'custom_person',
            'tax_query' => array(
                array(
                    'taxonomy' => 'custom_taxonomy',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            ),
            'posts_per_page' => -1,
        );

        $person_posts = get_posts($args);
        $person_identifiers = array();

        foreach ($person_posts as $person_post) {
            $person_id = get_post_meta($person_post->ID, 'person_id', true);
            if (!empty($person_id)) {
                $person_identifiers[] = $person_id;
            }
        }

        if (!empty($person_identifiers)) {
            $persons = process_persons_by_identifiers($person_identifiers);
        }
    } elseif (!empty($identifiers)) {
        $persons = process_persons_by_identifiers($identifiers);
    } elseif (!empty($groupid)) {
        $lq = 'contacts.organization.identifier[eq]=' . urlencode($groupid);
        $persons = fetch_and_process_persons($lq);
    } elseif (!empty($orgnr)) {
        $orgData = fetch_fau_organizations(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
        if (!empty($orgData['data'])) {
            $orgname = $orgData['data'][0]['name'];
            $lq = 'contacts.organization.name[eq]=' . urlencode($orgname);
            $persons = fetch_and_process_persons($lq);
        }
    } else {
        $persons = fetch_and_process_persons();
    }

    // Fetch the image URL if an image ID is provided
    $image_url = '';
    if (!empty($image_id) && is_numeric($image_id)) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
    }

    // Sorting logic based on the specified sorting options
    $sort_option = $atts['sort'] ?? 'last_name'; // Default sorting by last name
    $collator = collator_create('de_DE'); // German locale for sorting

    // Sort the persons array
    usort($persons, function ($a, $b) use ($sort_option, $collator, $identifiers) {
        switch ($sort_option) {
            case 'title_last_name':
                $academic_titles = ['Prof. Dr.', 'Dr.', 'Prof.', ''];
                $a_title = $a['personalTitle'] ?? '';
                $b_title = $b['personalTitle'] ?? '';
                $a_title_pos = array_search($a_title, $academic_titles) !== false ? array_search($a_title, $academic_titles) : count($academic_titles);
                $b_title_pos = array_search($b_title, $academic_titles) !== false ? array_search($b_title, $academic_titles) : count($academic_titles);
                if ($a_title_pos !== $b_title_pos) {
                    return $a_title_pos - $b_title_pos;
                }
                return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '');
    
                case 'function_head':
                    // Sort by 'head' in functionLabel
                    $a_is_head = false;
                    $b_is_head = false;
        
                    foreach ($a['contacts'] as $contact) {
                        if (isset($contact['function']) && $contact['function'] ==='leader') {
                            $a_is_professor = true;
                            break;
                            }
                        }
        
                    foreach ($b['contacts'] as $contact) {
                        if (isset($contact['function']) && $contact['function'] ==='professor') {
                            $b_is_professor = true;
                            break;
                        }
                    }
        
                    return $a_is_head === $b_is_head ? collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '') : ($a_is_head ? -1 : 1);
        
                case 'function_proffesor':
                    // Sort by 'professor' in functionLabel
                    $a_is_professor = false;
                    $b_is_professor = false;
        
                    foreach ($a['contacts'] as $contact) {
                        if (isset($contact['function']) && $contact['function'] ==='professor') {
                            $a_is_professor = true;
                            break;
                            }
                        }
                    
        
                    foreach ($b['contacts'] as $contact) {
                        if (isset($contact['function']) && $contact['function'] ==='professor') {
                                $b_is_professor = true;
                                break;
                            }
                        }
        
                    return $a_is_professor === $b_is_professor ? collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '') : ($a_is_professor ? -1 : 1);
        
            case 'identifier_order':
                if (!empty($identifiers)) {
                    $a_index = array_search($a['identifier'] ?? '', $identifiers);
                    $b_index = array_search($b['identifier'] ?? '', $identifiers);
                    if ($a_index === false && $b_index === false) {
                        return 0; // Both not found, consider equal
                    } elseif ($a_index === false) {
                        return 1; // a not found, b comes first
                    } elseif ($b_index === false) {
                        return -1; // b not found, a comes first
                    }
                    return $b_index - $a_index; // Sort based on found indices
                }
                return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? ''); // Fallback sorting
            default:
                return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '');
        }
    });
    

    // Load the template and pass the sorted data
    $template_dir = plugin_dir_path(__FILE__) . '../../templates/';
    $template = new Template($template_dir);

    // Fix format assignment when empty
    if ($atts['format'] === '') {
        $atts['format'] = 'kompakt';  // Use single = for assignment
    }

    // Check if button text is set and not empty before passing it to the template
    $button_text = isset($atts['button-text']) && $atts['button-text'] !== '' ? $atts['button-text'] : '';

    return $template->render($atts['format'], [
        'show_fields' => $show_fields,
        'hide_fields' => $hide_fields,
        'persons' => $persons,
        'image_url' => $image_url,
        'url' => $url,
        'button_text' => $button_text,
    ]);
}

/**
 * Process persons by a list of identifiers.
 */
function process_persons_by_identifiers($identifiers)
{
    $persons = [];
    $errors = [];

    foreach ($identifiers as $identifier) {
        $identifier = trim($identifier);
        if (!empty($identifier)) {
            $personData = fetch_fau_person_by_id($identifier);
            if (!empty($personData)) {
                $persons[] = enrich_person_with_contacts($personData);
            } else {
                // Create a "person" entry that's a ctually an error message
                $persons[] = [
                    'error' => true,
                    'message' => sprintf(__('Person with ID %s does not exist', 'rrze-faudir'), $identifier)
                ];
            }
        }
    }

    return $persons;
}

/**
 * Fetch and process persons based on query parameters.
 */
function fetch_and_process_persons($lq = null)
{
    $params = $lq ? ['lq' => $lq] : [];
    $data = fetch_fau_persons_atributes(0, 0, $params);

    $persons = [];
    if (!empty($data['data'])) {
        foreach ($data['data'] as $person) {
            $persons[] = enrich_person_with_contacts($person);
        }
    }

    return $persons;
}

/**
 * Enrich a person's data with their contacts and organization details.
 */
function enrich_person_with_contacts($person)
{
    $personContacts = [];

    if (!empty($person['contacts'])) {
        foreach ($person['contacts'] as $contact) {
            $contactIdentifier = $contact['identifier'] ?? null;
            if ($contactIdentifier) {
                $contactData = fetch_fau_contacts(0, 0, ['identifier' => $contactIdentifier]);
                if (!empty($contactData['data'])) {
                    $contact = $contactData['data'][0];
                    $organizationId = $contact['organization']['identifier'] ?? null;
                    $organizationAddress = null;

                    if ($organizationId) {
                        $organizationData = fetch_fau_organization_by_id($organizationId);
                        $organizationAddress = $organizationData['address'] ?? 'Address not available';
                    }

                    $contact['organization_address'] = $organizationAddress;
                    $personContacts[] = $contact;
                }
            }
        }
    }

    $person['contacts'] = $personContacts;
    return $person;
}



add_shortcode('faudir', 'fetch_fau_data');
