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
        
        // Haupt-Shortcode registrieren
        add_shortcode('faudir', [$this, 'fetch_fau_data']);
        
        // Alias-Shortcodes registrieren
        add_action('init', [$this, 'register_aliases']);
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
        $lang = FaudirUtils::getLang();

        
        // Extract the attributes from the shortcode
        $atts = shortcode_atts(
            array(
                'category'              => '',
                'identifier'            => '',
                'id'                    => '',
                'format'                => 'compact',
                'url'                   => '',
                'show'                  => '',
                'hide'                  => '',
                'orgnr'                 => '',
                'sort'                  => '',
                'function'              => '',
                'role'                  => '',
                'button-text'           => '',
                'format_displayname'     => ''
            ),
            $atts
        );

        if ((!empty($atts['function'])) && (empty( $atts['role']))) {
            $atts['role'] = $atts['function'];
        }
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
        
        // Map etwaige alte show/hide Args aus fau-person auf neue Parameternamen
        $mapping = self::$config->get('args_person_to_faudir') ?? [];
        $show_fields = array_map(function ($field) use ($mapping) {
            return $mapping[$field] ?? $field;
        }, array_map('trim', explode(',', $atts['show'] ?? '')));

        // $hide_fields mit Mapping umsetzen
        $hide_fields = array_map(function ($field) use ($mapping) {
            return $mapping[$field] ?? $field;
        }, array_map('trim', explode(',', $atts['hide'] ?? '')));

        // Remove duplicates and ensure arrays are unique
        $show_fields = array_unique($show_fields);
        $hide_fields = array_unique($hide_fields);

        // Extract the attributes from the shortcode
        $identifiers = empty($atts['identifier']) ? [] : explode(',', $atts['identifier']);
        $category   = $atts['category'];
         
        $role       = $atts['role'];
        $url        = $atts['url'];
        $orgnr      = $atts['orgnr'];
        $post_id    = $atts['id'];
        $format_displayname = $atts['format_displayname'];
        $persons    = [];

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
              //    error_log("FAUdir\Shortcode (): Got Persons Identifier: {$person_id}");       
                if (!empty($person_id)) {
                    $person_identifiers[] = $person_id;
                }
            }
           
            return self::process_persons_by_identifiers($person_identifiers);
        };

        // Closure to fetch persons by function and default organization
        $fetch_persons_by_function = function ($role) {
            $options = get_option('rrze_faudir_options', []);
            $default_org = $options['default_organization'] ?? null;
            $defaultOrgIdentifier = $default_org ? $default_org['id'] : '';
            $api = new API(self::$config);
            
            if (!empty($defaultOrgIdentifier)) {
                // Try English function label first
                $lq_en = 'contacts.functionLabel.en[eq]=' . urlencode($role) .
                    '&contacts.organization.identifier[eq]=' . urlencode($defaultOrgIdentifier);
     
                $response = $api->getPersons(0, 0, ['lq' => $lq_en]);
                
                // If no results, try German function label
                if (empty($response['data'])) {
                    $lq_de = 'contacts.functionLabel.de[eq]=' . urlencode($role) .
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
        $filter_persons = function ($persons, $orgnr) {
            if (!empty($orgnr)) {
                $api = new API(self::$config);
                $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr]);
                if (!empty($orgdata['data'])) {
                    $orgIdentifier = $orgdata['data'][0]['identifier'];
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
        $api = new API(self::$config);
        // Display persons by function
        $persons = [];
        $person = new Person();
        $person->setConfig(self::$config);

        if (!empty($role)) {
              Debug::log('Shortcode','error',"Look for function $role");
            if (!empty($orgnr)) {
                // Case 1: Explicit orgnr is provided in shortcode - exact match for both org and function
                
                $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr]);
                
                if (!empty($orgdata['data'])) {
                    $org = $orgdata['data'][0];
                    $identifier = $org['identifier'];

                    $queryParts = [];
                    $queryParts[] = 'contacts.organization.identifier[eq]=' . $identifier;

                    $params = [
                        'lq' => implode('&', $queryParts)
                    ];

                    $result = $api->getPersons(60, 0, $params);                  
                    Debug::log('Shortcode','error',"Look for function $role and orgnr $orgnr");
                    
                    // Process each person and filter contacts
                    foreach ($result['data'] as $key => &$persondata) {
                        // Enrich person data with full contact information
                        
                        $person->populateFromData($persondata);
                        $person->reloadContacts();
                        $persondata = $person->toArray();

                        // Filter contacts based on function
                        foreach ($persondata['contacts'] as $contactKey => $contact) {
                            if ($contact['function'] !== $role                                     
                                    && $contact['functionLabel']['de'] !== $role 
                                    && $contact['functionLabel']['en'] !== $role 
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
                                if ($contact['function'] !== $role 
                                        && $contact['functionLabel']['de'] !== $role 
                                        && $contact['functionLabel']['en'] !== $role 
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
            
        } elseif (!empty($post_id) && empty($identifiers) && empty($category) && empty($orgnr)) {
            // display a single person by custom post id - mostly on the slug
           //  error_log("FAUdir\Shortcode (fetch_persons_by_post_id): Search By id=: {$post_id}");       
            $persons = $fetch_persons_by_post_id($post_id);
        } elseif (!empty($identifiers) || !empty($post_id)) {
            // display a single person by identifier or post id
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
            $persons = $filter_persons($persons, $orgnr);
        } elseif (!empty($category)) {
            // get persons by category. 
            // categories come from existing custom posts only. 
            // Therfor we look there first for the identifiert
 
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
        } elseif (!empty($orgnr) && empty($post_id) && empty($identifiers) && empty($category) && empty($role)) {
            // get persons by orgnr
           // error_log("FAUdir\Shortcode (fetch_and_render_fau_data): By Orgnr: {$orgnr}");       
           $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr]);
           
            if (!empty($orgdata['data'])) {
                $orgid = $orgdata['data'][0]['identifier'];
                $lq = 'contacts.organization.identifier[eq]=' . $orgid;
                $persons = self::fetch_and_process_persons($lq);
            }
            
        } else {
            error_log('Invalid combination of attributes.');
            return;
        }

     
        // Sorting logic based on the specified sorting options
        $sort_option = $atts['sort'] ?? 'familyName'; // Default sorting by last name
        $collator = collator_create('de_DE'); // German locale for sorting

        // Sort the persons array
        usort($persons, function ($a, $b) use ($sort_option, $collator, $identifiers) {
            switch ($sort_option) {
                case 'title_familyName':
                    $academic_titles = ['Prof. Dr.', 'Dr.', 'Prof.', ''];
                    $a_title = $a['honorificPrefix'] ?? '';
                    $b_title = $b['honorificPrefix'] ?? '';
                    $a_title_pos = array_search($a_title, $academic_titles) !== false ? array_search($a_title, $academic_titles) : count($academic_titles);
                    $b_title_pos = array_search($b_title, $academic_titles) !== false ? array_search($b_title, $academic_titles) : count($academic_titles);
                    if ($a_title_pos !== $b_title_pos) {
                        return $a_title_pos - $b_title_pos;
                    }
                    return collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '');

                case 'head_first':
                    // Sort by 'head' in functionLabel
                    $a_is_head = false;
                    $b_is_head = false;

                    foreach ($a['contacts'] as $contact) {
                        if (isset($contact['function']) && (($contact['function'] === 'leader') || ($contact['function'] === 'deputy'))) {
                            $a_is_head = true;
                            break;
                        }
                    }

                    foreach ($b['contacts'] as $contact) {
                        if (isset($contact['function']) && $contact['function'] === 'professor') {
                            $b_is_head = true;
                            break;
                        }
                    }

                    return $a_is_head === $b_is_head ? collator_compare($collator, $a['familyName'] ?? '', $b['familyName'] ?? '') : ($a_is_head ? -1 : 1);

                case 'prof_first':
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
            $atts['format'] = 'compact';  // Use single = for assignment
        } elseif ($atts['format'] === 'kompakt') {
            $atts['format'] = 'compact';
        }
        if ($atts['format'] === 'liste') {
             $atts['format'] = 'list';
        }
        if (($atts['format'] === 'sidebar') || ($atts['format'] === 'sidebar')) {
             $atts['format'] = 'compact';
        }




        // Check if button text is set and not empty before passing it to the template
        $button_text = isset($atts['button-text']) && $atts['button-text'] !== '' ? $atts['button-text'] : '';

        // check and sanitize for format for displayname
        $format_displayname = wp_strip_all_tags($format_displayname);
        
        return $template->render($atts['format'], [
            'show_fields'   => $show_fields,
            'hide_fields'   => $hide_fields,
            'format_displayname' => $format_displayname,
            'persons'       => $persons,
            'url'           => $url,
            'button_text'   => $button_text,
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
        
        $persons = [];
        if (!empty($data['data'])) {
            foreach ($data['data'] as $persondata) {
          //      error_log("FAUdir\Shortcode (fetch_and_process_persons): Populate Persondata.");
                $person->populateFromData($persondata);
                $person->reloadContacts();
                $persons[] = $person->toArray();  
            }
        }

        return $persons;
    }


    public function register_aliases(): void {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (!is_plugin_active('fau-person/fau-person.php')) {
            add_shortcode('kontakt', [$this, 'kontakt_to_faudir']);
            add_shortcode('kontaktliste', [$this, 'kontaktliste_to_faudir']);
        }
    }

    public function kontakt_to_faudir($atts, $content = null): string {
        $atts_string = $this->atts_to_string($atts);
        return do_shortcode(shortcode_unautop('[faudir ' . $atts_string . ']' . $content . '[/faudir]'));
    }

    public function kontaktliste_to_faudir($atts, $content = null): string {
        if (!isset($atts['format'])) {
            $atts['format'] = 'list';
        }
        $atts_string = $this->atts_to_string($atts);
        return do_shortcode(shortcode_unautop('[faudir ' . $atts_string . ']' . $content . '[/faudir]'));
    }

    protected function atts_to_string(array $atts): string {
        $atts_string = '';
        foreach ($atts as $key => $value) {
            $atts_string .= $key . '="' . esc_attr($value) . '" ';
        }
        return trim($atts_string);
    }


}