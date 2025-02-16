<?php
// Shortcode handler for RRZE FAUDIR
// namespace RRZE\FAUdir;
namespace RRZE\FAUdir;

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Template;
use RRZE\FAUdir\Debug;
use RRZE\FAUdir\API;

defined('ABSPATH') || exit;


class Shortcode { 

    protected static $config;
    
    public function __construct(Config $configdata) {
     //   $this->config = $configdata;
        self::$config = $configdata;
        add_shortcode('faudir', [$this, 'fetch_fau_data']);
    }
  
    // Shortcode function
    public static function fetch_fau_data($atts) {
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
            return self::fetch_and_render_fau_data($atts);
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
        $output = self::fetch_and_render_fau_data($atts);

        // Cache the rendered output using Transients API
        set_transient($cache_key, $output, $cache_timeout);

        return $output;
    }

    public static function fetch_and_render_fau_data($atts) {
        // Convert 'show' and 'hide' attributes into arrays
        $show_fields = array_map('trim', explode(',', $atts['show']));
        $hide_fields = array_map('trim', explode(',', $atts['hide']));
        

        // Handle name-related fields logic
        $name_fields = ['personalTitle', 'givenName', 'familyName', 'personalTitleSuffix', 'titleOfNobility'];

        // If displayName is in show_fields, add all name-related fields
        if (in_array('displayName', $show_fields)) {
            $show_fields = array_merge($show_fields, $name_fields);
        } else {
            // Only keep explicitly selected name fields
            foreach ($name_fields as $field) {
                if (!in_array($field, $show_fields)) {
                    $hide_fields[] = $field;
                }
            }
        }

        // Remove duplicates and ensure arrays are unique
        $show_fields = array_unique($show_fields);
        $hide_fields = array_unique($hide_fields);

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
        $fetch_persons_by_post_id = function ($post_id) {
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

            return self::process_persons_by_identifiers($person_identifiers);
        };

        // Closure to fetch persons by function and default organization
        $fetch_persons_by_function = function ($function) {
            $options = get_option('rrze_faudir_options', []);
            $default_org = $options['default_organization'] ?? null;
            $defaultOrgIdentifier = $default_org ? $default_org['id'] : '';
            $api = new API(self::$config);
            
            if (!empty($defaultOrgIdentifier)) {
                // Try English function label first
                $lq_en = 'contacts.functionLabel.en[eq]=' . urlencode($function) .
                    '&contacts.organization.identifier[eq]=' . urlencode($defaultOrgIdentifier);
     
                $response = $api->getPersons(0, 0, ['lq' => $lq_en]);
                
                // If no results, try German function label
                if (empty($response['data'])) {
                    $lq_de = 'contacts.functionLabel.de[eq]=' . urlencode($function) .
                        '&contacts.organization.identifier[eq]=' . urlencode($defaultOrgIdentifier);
                    $response = $api->getPersons(0, 0, ['lq' => $lq_de]);
                }

                if (!empty($response['data'])) {
                    $person_identifiers = array_map(function ($person) {
                        return $person['identifier'];
                    }, $response['data']);
                    return self::process_persons_by_identifiers($person_identifiers);
                }
            }
            return [];
        };

        // Closure to filter persons by organization and group ID
        $filter_persons = function ($persons, $orgnr, $groupid) {
            if (!empty($orgnr)) {
                $api = new API(self::$config);
                $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
                if (!empty($orgData['data'])) {
                    $orgIdentifier = $orgData['data'][0]['identifier'];
                    $persons = array_filter($persons, function ($person) use ($orgIdentifier) {
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
                $persons = array_filter($persons, function ($person) use ($groupid) {
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
        $filter_persons_by_category = function ($persons, $category) {
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

            return array_filter($persons, function ($person) use ($category_person_ids) {
                return in_array($person['identifier'], $category_person_ids);
            });
        };

        // Determine which logic to apply based on provided attributes
        if (!empty($function)) {
            $persons = [];
            $api = new API(self::$config);
            $person = new Person();
            $person->setConfig(self::$config);
            
            if (!empty($orgnr)) {
                // Case 1: Explicit orgnr is provided in shortcode - exact match for both org and function
                
                $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
                
                if (!empty($orgData['data'])) {
                    $org = $orgData['data'][0];
                    $identifier = $org['identifier'];

                    $queryParts = [];
                    $queryParts[] = 'contacts.organization.identifier[eq]=' . $identifier;

                    $params = [
                        'lq' => implode('&', $queryParts)
                    ];

                    $result = $api->getPersons(60, 0, $params);                  

                    // Process each person and filter contacts
                    foreach ($result['data'] as $key => &$persondata) {
                        // Enrich person data with full contact information
                        
                        $person->populateFromData($persondata);
                        $person->reloadContacts();
                        $persondata = $person->toArray();

                        // Filter contacts based on function
                        foreach ($persondata['contacts'] as $contactKey => $contact) {
                            if ($contact['functionLabel']['de'] !== $function 
                                    && $contact['functionLabel']['en'] !== $function 
                                    || $contact['organization']['identifier'] !== $identifier) {
                                unset($persondata['contacts'][$contactKey]);
                            }
                        }

                        // Remove person if no matching contacts remain
                        if (count($persondata['contacts']) === 0) {
                            unset($result['data'][$key]);
                        }
                    }

                    if (!empty($result['data'])) {
                        $persons = array_values($result['data']);
                    }
                }
            } else {
                // Case 2: Only function is specified - use default org prefix
                if (empty($identifiers) && empty($post_id) && empty($orgnr) && empty($category)) {
                    $options = get_option('rrze_faudir_options', []);
                    $default_org = $options['default_organization'] ?? null;

                    if (!empty($default_org['orgnr'])) {
                        $ids = $default_org['ids'];
                        // Extract first 6 digits for prefix matching

                        $queryParts[] = 'contacts.organization.identifier[reg]=^(' . implode('|', $ids) . ')$';

                        // Format the query according to the specified pattern
                        $params = [
                            'lq' => implode('&', $queryParts)
                        ];

                        $result = $api->getPersons(60, 0, $params);  
                        
                        // Process each person and filter contacts
                        foreach ($result['data'] as $key => &$persondata) {
                            
                            $person->populateFromData($persondata);
                            $person->reloadContacts();
                            $persondata = $person->toArray();
                            
                            foreach ($persondata['contacts'] as $contactKey => $contact) {
                                if ($contact['functionLabel']['de'] !== $function 
                                        && $contact['functionLabel']['en'] !== $function 
                                        || !in_array($contact['organization']['identifier'], $ids)) {
                                    unset($persondata['contacts'][$contactKey]);
                                }
                            }

                            if (count($persondata['contacts']) === 0) {
                                unset($result['data'][$key]);
                            }
                        }

                        if (!empty($result['data'])) {
                            $persons = array_values($result['data']);
                        }
                    }
                }
            }
        } elseif (!empty($post_id) && empty($identifiers) && empty($category) && empty($groupid) && empty($orgnr)) {
            $persons = $fetch_persons_by_post_id($post_id);
        } elseif (!empty($identifiers) || !empty($post_id)) {
            if (!empty($identifiers)) {
                $persons = self::process_persons_by_identifiers($identifiers);
            } elseif (!empty($post_id)) {
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
                $persons = self::process_persons_by_identifiers($person_identifiers);
            }
        } elseif (!empty($orgnr) && empty($post_id) && empty($identifiers) && empty($category) && empty($groupid) && empty($function)) {
            $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . urlencode($orgnr)]);
            if (!empty($orgData['data'])) {
                $orgname = $orgData['data'][0]['name'];
                $lq = 'contacts.organization.name[eq]=' . urlencode($orgname);
                $persons = self::fetch_and_process_persons($lq);
            }
        } else {
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
        $template_dir = RRZE_PLUGIN_PATH . 'templates/';
        $template = new Template($template_dir);

        // Fix format assignment when empty
        if ($atts['format'] === '') {
            $atts['format'] = 'kompakt';  // Use single = for assignment
        }
        if ($atts['format'] === 'liste') {
             $atts['format'] = 'list';
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
    public static function process_persons_by_identifiers($identifiers) {
        $persons = [];
        $errors = [];

        $person = new Person();
        $person->setConfig(self::$config);
                
        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);
            if (!empty($identifier)) {                
                $found = $person->getPersonbyAPI($identifier);
                if ($found) {
                    $person->reloadContacts();
                    $persons[] = $person->toArray();
                } else {
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
    public static function fetch_and_process_persons($lq = null) {
        $params = $lq ? ['lq' => $lq] : [];
        $api = new API(self::$config);
        $data = $api->getPersons(0, 0, $params);
        
        $person = new Person();
        $person->setConfig(self::$config);
        
        // $data = fetch_fau_persons(0, 0, $params);

        $persons = [];
        if (!empty($data['data'])) {
            foreach ($data['data'] as $person) {
                error_log("FAUdir\Shortcode (fetch_and_process_persons): Populate Persondata.");
                $person->populateFromData($person);
                $person->reloadContacts();
                $persons[] = $person->toArray();  
            }
        }

        return $persons;
    }




}