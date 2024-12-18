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
    // Only return early if it's a pure admin page, not the block editor
    if (
        is_admin() && 
        !(defined('REST_REQUEST') && REST_REQUEST) && // Allow REST requests (block editor)
        !(defined('DOING_AJAX') && DOING_AJAX) && // Allow AJAX calls
        !(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) // Allow autosave
    ) {
        return ''; 
    }

    // Get the default output fields using the utility function
    $default_show_fields = FaudirUtils::getDefaultOutputFields();

    // Extract the attributes from the shortcode
    $atts = shortcode_atts(
        array(
            'category' => '',
            'identifier' => '',
            'id' => '',
            'format' => 'kompakt',
            'url' => '',
            'show' => '',
            'hide' => '',
            'image' => '',
            'groupid' => '',
            'function' => '',
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

    // Extract the attributes from the shortcode
    $identifiers = empty($atts['identifier']) ? [] : explode(',', $atts['identifier']);
    $category = $atts['category'];
    $image_id = $atts['image'];
    $url = $atts['url'];
    $groupid = $atts['groupid'];
    $function = $atts['function'];
    $orgnr = $atts['orgnr'];
    $post_id = $atts['id'];
    $persons = [];

    // Closure to fetch persons by post ID
    $fetch_persons_by_post_id = function($post_id) {
        $args = [
            'post_type'      => 'custom_person',
            'meta_query'     => [
                [
                    'key'     => 'old_person_post_id',
                    'value'   => intval($post_id),
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
        ];
        
        $person_posts = get_posts($args);
        $person_identifiers = array();

        foreach ($person_posts as $person_post) {
            $person_id = get_post_meta($person_post->ID, 'person_id', true);
            if (!empty($person_id)) {
                $person_identifiers[] = $person_id;
            }
        }

        return process_persons_by_identifiers($person_identifiers);
    };

    // Closure to fetch persons by function and default organization
    $fetch_persons_by_function = function($function) {
        $options = get_option('rrze_faudir_options', []);
        $default_org = $options['default_organization'] ?? null;
        $defaultOrgIdentifier = $default_org ? $default_org['id'] : '';

        if (!empty($defaultOrgIdentifier)) {
            // Try English function label first
            $lq_en = 'contacts.functionLabel.en[eq]=' . urlencode($function) . 
                    '&contacts.organization.identifier[eq]=' . urlencode($defaultOrgIdentifier);
            $response = fetch_fau_persons(0, 0, ['lq' => $lq_en]);

            // If no results, try German function label
            if (empty($response['data'])) {
                $lq_de = 'contacts.functionLabel.de[eq]=' . urlencode($function) . 
                        '&contacts.organization.identifier[eq]=' . urlencode($defaultOrgIdentifier);
                $response = fetch_fau_persons(0, 0, ['lq' => $lq_de]);
            }

            if (!empty($response['data'])) {
                $person_identifiers = array_map(function($person) {
                    return $person['identifier'];
                }, $response['data']);
                return process_persons_by_identifiers($person_identifiers);
            }
        }
        return [];
    };

    // Closure to filter persons by organization and group ID
    $filter_persons = function($persons, $orgnr, $groupid) {
        if (!empty($orgnr)) {
            $orgData = fetch_fau_organizations(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
            if (!empty($orgData['data'])) {
                $orgIdentifier = $orgData['data'][0]['identifier'];
                $persons = array_filter($persons, function($person) use ($orgIdentifier) {
                    foreach ($person['contacts'] as $contact) {
                        if ($contact['organization']['identifier'] === $orgIdentifier) {
                            return true;
                        }
                    }
                    return false;
                });
            }
        }

        if (!empty($groupid)) {
            $persons = array_filter($persons, function($person) use ($groupid) {
                foreach ($person['contacts'] as $contact) {
                    if ($contact['organization']['identifier'] === $groupid) {
                        return true;
                    }
                }
                return false;
            });
        }

        return $persons;
    };

    // Closure to filter persons by category
    $filter_persons_by_category = function($persons, $category) {
        if (empty($category)) {
            return $persons;
        }

        $args = [
            'post_type' => 'custom_person',
            'tax_query' => [
                [
                    'taxonomy' => 'custom_taxonomy',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ],
            'posts_per_page' => -1,
        ];
        
        $person_posts = get_posts($args);
        $category_person_ids = array();

        foreach ($person_posts as $person_post) {
            $person_id = get_post_meta($person_post->ID, 'person_id', true);
            if (!empty($person_id)) {
                $category_person_ids[] = $person_id;
            }
        }

        return array_filter($persons, function($person) use ($category_person_ids) {
            return in_array($person['identifier'], $category_person_ids);
        });
    };

    // Determine which logic to apply based on provided attributes
    if (!empty($function)) {
        // Function must be accompanied by identifier, id, or orgnr
        if (empty($identifiers) && empty($post_id) && empty($orgnr) && empty($category)) {
            // Get default organization from settings
            $options = get_option('rrze_faudir_options', []);
            $default_org = $options['default_organization'] ?? null;
            
            if (empty($default_org)) {
                return __('Error: When using function parameter, you must also specify either identifier, id, orgnr, category, or set a default organization in settings.', 'rrze-faudir');
            }
            
            // Extract the orgnr from the default organization array
            $orgnr = '';
            if (is_array($default_org)) {
                $orgnr = $default_org['orgnr'] ?? '';  // Changed from 'id' to 'orgnr'
                if (empty($orgnr) && !empty($default_org['ids'])) {
                    $orgnr = reset($default_org['ids']); // Use first ID if orgnr not set
                }
            } else {
                $orgnr = $default_org;
            }
            
            if (empty($orgnr)) {
                return __('Error: Invalid default organization configuration.', 'rrze-faudir');
            }
        }
        
        // Fetch persons based on function and available parameters
        if (!empty($identifiers)) {
            $persons = process_persons_by_identifiers($identifiers);
            // Apply category filter if category is set
            if (!empty($category)) {
                $persons = $filter_persons_by_category($persons, $category);
            }
        } elseif (!empty($post_id)) {
            $persons = $fetch_persons_by_post_id($post_id);
            // Apply category filter if category is set
            if (!empty($category)) {
                $persons = $filter_persons_by_category($persons, $category);
            }
        } elseif (!empty($category)) {
            // Fetch all persons from category first
            $args = [
                'post_type' => 'custom_person',
                'tax_query' => [
                    [
                        'taxonomy' => 'custom_taxonomy',
                        'field'    => 'slug',
                        'terms'    => $category,
                    ],
                ],
                'posts_per_page' => -1,
            ];
            
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
                // Then filter by function
                $persons = array_filter($persons, function($person) use ($function) {
                    foreach ($person['contacts'] as $contact) {
                        if (isset($contact['functionLabel']['en']) && $contact['functionLabel']['en'] === $function) {
                            return true;
                        }
                        if (isset($contact['functionLabel']['de']) && $contact['functionLabel']['de'] === $function) {
                            return true;
                        }
                    }
                    return false;
                });
            }
        } elseif (!empty($orgnr)) {
            $orgData = fetch_fau_organizations(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
            if (!empty($orgData['data'])) {
                $orgIdentifier = $orgData['data'][0]['identifier'];
                error_log("Found organization: " . $orgIdentifier);
                
                // Fetch all persons for this organization first
                $lq = 'contacts.organization.identifier[eq]=' . urlencode($orgIdentifier);
                $persons = fetch_and_process_persons($lq);
                
                error_log("Total persons found for org: " . count($persons));
                
                // Filter by function with more flexible matching
                $persons = array_filter($persons, function($person) use ($function, $orgIdentifier) {
                    foreach ($person['contacts'] as $contact) {
                        if ($contact['organization']['identifier'] === $orgIdentifier) {
                            // Log the function labels we're comparing
                            error_log("Comparing - Required: " . $function);
                            error_log("Found EN: " . ($contact['functionLabel']['en'] ?? 'none'));
                            error_log("Found DE: " . ($contact['functionLabel']['de'] ?? 'none'));
                            
                            // More flexible matching including partial matches
                            $en_label = strtolower($contact['functionLabel']['en'] ?? '');
                            $de_label = strtolower($contact['functionLabel']['de'] ?? '');
                            $search_function = strtolower($function);
                            
                            // Check for exact or partial matches
                            if (strpos($en_label, $search_function) !== false ||
                                strpos($de_label, $search_function) !== false ||
                                strpos($search_function, $en_label) !== false ||
                                strpos($search_function, $de_label) !== false) {
                                return true;
                            }
                        }
                    }
                    return false;
                });
                
                error_log("Filtered persons count: " . count($persons));
            }
        }
    } elseif (!empty($post_id) && empty($identifiers) && empty($category) && empty($groupid) && empty($orgnr)) {
        $persons = $fetch_persons_by_post_id($post_id);
    } elseif (!empty($identifiers) || !empty($post_id)) {
        if(!empty($identifiers)){
        $persons = process_persons_by_identifiers($identifiers);
        } 
        elseif (!empty($post_id)){
            $persons = $fetch_persons_by_post_id($post_id);
        }
        // Apply category filter if category is set
        if (!empty($category)) {
            $persons = $filter_persons_by_category($persons, $category);
        }
        // Apply organization and group filters if set
        $persons = $filter_persons($persons, $orgnr, $groupid);
    } elseif (!empty($category)) {
            // Fetch by category
            $args = [
                'post_type' => 'custom_person',
                'tax_query' => [
                    [
                        'taxonomy' => 'custom_taxonomy',
                        'field'    => 'slug',
                        'terms'    => $category,
                    ],
                ],
                'posts_per_page' => -1,
            ];
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
    } elseif (!empty($orgnr) && empty($post_id) && empty($identifiers) && empty($category) && empty($groupid) && empty($function)) {
        $orgData = fetch_fau_organizations(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
        if (!empty($orgData['data'])) {
            $orgname = $orgData['data'][0]['name'];
            $lq = 'contacts.organization.name[eq]=' . urlencode($orgname);
            $persons = fetch_and_process_persons($lq);
        }
    }else {
        error_log('Invalid combination of attributes.');
        return;
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
                    if (isset($contact['function']) && $contact['function'] === 'leader') {
                        $a_is_professor = true;
                        break;
                    }
                }

                foreach ($b['contacts'] as $contact) {
                    if (isset($contact['function']) && $contact['function'] === 'professor') {
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
                    if (isset($contact['function']) && $contact['function'] === 'professor') {
                        $a_is_professor = true;
                        break;
                    }
                }

                foreach ($b['contacts'] as $contact) {
                    if (isset($contact['function']) && $contact['function'] === 'professor') {
                        $b_is_professor = true;
                        break;
                    }
                }

                return $a_is_professor === $b_is_professor ? collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '') : ($a_is_professor ? -1 : 1);

            case 'identifier_order':
                if (!empty($identifiers)) {
                    $a_index = array_search($a['identifier'] ?? '', $identifiers);
                    $b_index = array_search($b['identifier'] ?? '', $identifiers);
                    if ($a_index === false) $a_index = PHP_INT_MAX;
                    if ($b_index === false) $b_index = PHP_INT_MAX;

                    return $a_index - $b_index;
                }
                return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '');

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
    $data = fetch_fau_persons(0, 0, $params);

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
