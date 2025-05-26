<?php
// Shortcode handler for RRZE FAUDIR
// namespace RRZE\FAUdir;
namespace RRZE\FAUdir;

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Template;
use RRZE\FAUdir\Debug;
use RRZE\FAUdir\API;
use RRZE\FAUdir\Organization;

defined('ABSPATH') || exit;


class Shortcode { 

    protected static $config;
    
    public function __construct(Config $configdata) {
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
                'orgid'                 => '',
                'sort'                  => '',
                'function'              => '',
                'role'                  => '',
                'button-text'           => '',
                'format_displayname'    => '',
                'display'               => '',
                'lang'                  => ''
            ),
            $atts
        );
        if (empty($atts['display'])) {
            $atts['display'] = self::$config->get('default_display');
        }
        if (empty($atts['lang'])) {
            $atts['lang'] = $lang;
        } else {
            $lang = $atts['lang'];
        }
        
        if ((!empty($atts['function'])) && (empty( $atts['role']))) {
            $atts['role'] = $atts['function'];
        }
        // Convert explicitly set 'show' values to an array and merge with default fields
        $explicit_show_fields = array_filter(array_map('trim', explode(',', $atts['show'])));
        $merged_show_fields = array_unique(array_merge($default_show_fields, $explicit_show_fields));
        $atts['show'] = implode(', ', $merged_show_fields);

        // If user is logged in and no-cache option is enabled, always fetch fresh data
        $options = get_option('rrze_faudir_options');
        $no_cache_logged_in = isset($options['no_cache_logged_in']) && $options['no_cache_logged_in'];    
        if ($no_cache_logged_in && is_user_logged_in()) {
            $output = self::fetch_and_render_fau_data($atts);
            $output = do_shortcode(shortcode_unautop($output));
            return $output;
        }

        $cache_key = 'faudir_shortcode_' . md5(serialize($atts));
        $cache_timeout = isset($options['cache_timeout']) ? intval($options['cache_timeout']) * 60 : 900; // Default to 15 minutes

        // Check if cached data exists
        $cached_data = get_transient($cache_key);
        
     
        if ($cached_data !== false) {
            return  do_shortcode(shortcode_unautop($cached_data));
        }
        
        // Fetch and render fresh data
        $output = self::fetch_and_render_fau_data($atts);
       
        // Cache the rendered output using Transients API
        // Dont execute shortcodes here and safe the raw code! Cause they have to be executed on 
        // creating the website, due the fact that they might embed js oder css.
        set_transient($cache_key, $output, $cache_timeout);
                
        $output = do_shortcode(shortcode_unautop($output));
        return $output;
    }

    
    /*
     * Create Output for Shortcode 
     */
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

        if ($atts['display'] == 'org') {
            return self::createOrgOutput($atts, $show_fields, $hide_fields);
        } else {
            return self::createPersonOutput($atts, $show_fields, $hide_fields);
        }
    }

    
    /*
     * Create Output for Person Display
     */
    public static function createPersonOutput(array $atts, array $show_fields, array $hide_fields): string {     
        // Extract the attributes from the shortcode
        $identifiers = empty($atts['identifier']) ? [] : explode(',', $atts['identifier']);
            // FAUdir Identifier von einer oder mehreren Personen
        $category   = $atts['category'];     
            // Ausgabe nach Kategorie
        $role       = $atts['role'];
            // Ausgabe von Personen nach functionlabel und Sprachvarianten
        $url        = $atts['url'];
            // optionale URL der Person überschreiben
        $orgnr      = $atts['orgnr'];
            // ORGnr der Einrichtung, falls Personen danach angezeigt werden sollen
        $post_id    = $atts['id'];
            // Personen POst ID falls diese angezeigt werden soll
        $display    = $atts['display'];
            // Art der anzuzeigendenden Daten.  Default: Person. Alternativ: Org
        $format_displayname = $atts['format_displayname'];
            // optionale Formataenderung für die Darstellung des Namens

    
        $api = new API(self::$config);
        
        
        // Display persons by function
        $persons = [];
        $person = new Person();
        $person->setConfig(self::$config);

        if (!empty($role)) {
            // Display Persons by Role (FAUdir Function)
            
      //        Debug::log('Shortcode','error',"Look for function $role");
            if (Organization::isOrgnr($orgnr)) {
                // Case 1: Explicit orgnr is provided in shortcode - exact match for both org and function
                
                $orgdata = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr]);
          
                // TODO wir brauchen für den Fall der gekürzten OrgNr eine neue Schleife mit
                //         $orgdata = $api->getOrgList(20, 0, ['lq' => 'disambiguatingDescription[reg]=^' . $orgnr]);
                // Danach müssen wir aber über alle Orgs gehen un dnicht wie nachfolgend nur die erste nehmen!
                
                if (!empty($orgdata['data'])) {
                    $org = $orgdata['data'][0];
                        // Hier nimmt er nur den ersten!!
                    
                    $identifier = $org['identifier'];
                    $queryParts = [];
                    $queryParts[] = 'contacts.organization.identifier[eq]=' . $identifier;

                    $params = [
                        'lq' => implode('&', $queryParts)
                    ];

                    $result = $api->getPersons(60, 0, $params);                  
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
//                                Debug::log("unset person by search in $orgnr: ".$persondata['identifier']." ".$persondata['familyName']." (".$contact['organization']['identifier']."/".$identifier  );

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
                    $options = get_option('rrze_faudir_options', []);
                    $default_org = $options['default_organization'] ?? null;

                    if (!empty($default_org['orgnr'])) {
                        $ids = $default_org['ids'];
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
            
        } elseif (!empty($post_id) && empty($identifiers) && empty($category) && empty($orgnr)) {
            // display a single person by custom post id - mostly on the slug
           //  error_log("FAUdir\Shortcode (fetch_persons_by_post_id): Search By id=: {$post_id}");       
            $persons = self::fetchPersonsByPostId($post_id);
        } elseif (!empty($identifiers) || !empty($post_id)) {
            // display a single person by identifier or post id
            if (!empty($identifiers)) {
                $persons = self::process_persons_by_identifiers($identifiers);
            } elseif (!empty($post_id)) {                
                $persons = self::fetchPersonsByPostId($post_id);
            }
            // Apply category filter if category is set
            if (!empty($category)) {
                $persons = self::filterPersonsByCategory($persons, $category);
            }
            // Apply organization and group filters if set
            $persons = self::filterPersonsByOrganization($persons, $orgnr);
        } elseif (!empty($category)) {
            // get persons by category. 
            $person_identifiers = self::getPersonIdentifiersByCategory($category);
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
            return '';
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

        // Check if button text is set and not empty before passing it to the template
        $button_text = isset($atts['button-text']) && $atts['button-text'] !== '' ? $atts['button-text'] : '';

        // check and sanitize for format for displayname
        $format_displayname = wp_strip_all_tags($format_displayname);
        $format = self::validateFormat($atts['format'], $display);
        $templatefile = $display.'_'.$format;
        return $template->render($templatefile, [
            'show_fields'   => $show_fields,
            'hide_fields'   => $hide_fields,
            'format_displayname' => $format_displayname,
            'persons'       => $persons,
            'url'           => $url,
            'button_text'   => $button_text,
        ]);
    }
    
    
    /*
     * Create Output for Org Display
     */
    public static function createOrgOutput(array $atts, array $show_fields, array $hide_fields): string {     
        $orgnr      = $atts['orgnr'];
            // Org Nr der Einrichtung, die anzuzeigen ist
        $orgid      = $atts['orgid'];
            // Org Identifier der Einrichtung, die anzuzeigen ist
        $display    = $atts['display'];
            // Art der anzuzeigendenden Daten.   
        $url        = $atts['url'];
            // optionale URL der ORG überschreiben
        
        $org = new Organization();
        
        if (Organization::isOrgnr($orgnr)) {   
            $org->getOrgbyOrgnr($orgnr);
            $orgdata = $org->toArray();
        } elseif (!empty($orgid)) {
            $orgid = Organization::sanitizeOrgIdentifier($orgid);
            
            if (Organization::isOrgIdentifier($orgid)) {
                $org->getOrgbyAPI($orgid);
                $orgdata = $org->toArray();
            } else {
                return self::createErrorOut(__('Bad value for parameter orgid', 'rrze-faudir'), 'createOrgOutput');
            }

        } else {
            return self::createErrorOut(__('Invalid oder missing parameter orgnr or orgid', 'rrze-faudir'), 'createOrgOutput');
        }
        
        
           
        $template_dir = RRZE_PLUGIN_PATH . 'templates/';
        $template = new Template($template_dir);

        // check and sanitize for format for displayname
        $format = self::validateFormat($atts['format'], $display);
        $templatefile = $display.'_'.$format;
        return $template->render($templatefile, [
            'show_fields'   => $show_fields,
            'hide_fields'   => $hide_fields,
            'orgdata'       => $orgdata,
            'url'           => $url,
        ]);
       
        
        
    }
    
    
    /*
     * Create Error Output in cases it is not promptet within templates
     */
    public static function createErrorOut(string $error, string $errorloginfo = ''): string {
        if (empty($error)) {
            $error = __('Error on creating output', 'rrze-faudir');
        }
        error_log('FAUdir/Shortcode ('.$errorloginfo.'): '. $error);
        $out = '<div class="faudir">';  
        $config = new Config;
        $opt = $config->getOptions(); 
        if ($opt['show_error_message']) {
          $out .= '<div class="faudir-error">';
          $out .= $error;
          $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
    }
    
    
    
    /*
     * Check the given format for validity and return it if its avaible, otherwise 
     * the default format
     */
    public static function validateFormat(string $format = '', string $display = ''): string {
        $allformats =   self::$config->get('avaible_formats_by_display');
        if ((empty($display))  ||  (!isset($allformats[$display]))) {
            $display = self::$config->get('default_display');
        }

        if (empty($format)) {
            return  $allformats[$display][0];
        }
   
        // first fix for typo or old names:     
        if ($format === 'kompakt') {
            $format = 'compact';
        } elseif ($format === 'liste') {
            $format = 'list';
        }
        

        // Now check if its valid, otherwise return the default.
         // Wenn ein Format übergeben wurde, prüfen ob es gültig ist
        if (!empty($format) && in_array($format, $allformats[$display], true)) {
            return $format;
        }

        // Fallback
        return $allformats[$display][0];
    }
    
   /*
    * Hole personeneinträge aus dem CPT, die zu einer definierten Kategorie gehören
    */ 
    public static function getPersonIdentifiersByCategory(string $category): array {
        if (empty($category)) {
            return [];
        }

        $taxonomy = self::$config->get('person_taxonomy') ?? 'custom_taxonomy';
        $post_type = self::$config->get('person_post_type') ?? 'custom_person';

        $args = [
            'post_type'      => $post_type,
            'tax_query'      => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ],
            'posts_per_page' => -1,
        ];

        $person_posts = get_posts($args);
        $person_identifiers = [];

        foreach ($person_posts as $person_post) {
            $person_id = get_post_meta($person_post->ID, 'person_id', true);
            if (!empty($person_id)) {
                $person_identifiers[] = $person_id;
            }
        }

        return $person_identifiers;
    }


    /*
     * Filter Personen nach ihrer Org
     */
    public static function filterPersonsByOrganization(array $persons, string $orgnr): array {
        if (empty($orgnr)) {
            return $persons;
        }

        $api = new API(self::$config);
        $orgdata = $api->getOrgList(0, 0, [
            'lq' => 'disambiguatingDescription[eq]=' . $orgnr
        ]);

        if (!empty($orgdata['data'][0]['identifier'])) {
            $orgIdentifier = $orgdata['data'][0]['identifier'];

            $persons = array_filter($persons, function ($person) use ($orgIdentifier) {
                foreach ($person['contacts'] as $contact) {
                    if (
                        !empty($contact['organization']['identifier']) &&
                        $contact['organization']['identifier'] === $orgIdentifier
                    ) {
                        return true;
                    }
                }
                return false;
            });
        }

        return $persons;
    }


    /*
     * Rufe poersonen aus der gegebenen Kategorie ab
     */
    public static function filterPersonsByCategory(array $persons, string $category): array {
        if (empty($category)) {
            return $persons;
        }

        // Taxonomie-Slug aus der Konfiguration lesen (Fallback: 'custom_taxonomy')
        $taxonomy = self::$config->get('person_taxonomy') ?? 'custom_taxonomy';
        $post_type = self::$config->get('person_post_type') ?? 'custom_person';
        
        $args = [
            'post_type'      => $post_type,
            'tax_query'      => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ],
            'posts_per_page' => -1,
        ];

        $person_posts = get_posts($args);
        $category_person_ids = [];

        foreach ($person_posts as $person_post) {
            $person_id = get_post_meta($person_post->ID, 'person_id', true);
            if (!empty($person_id)) {
                $category_person_ids[] = $person_id;
            }
        }

        return array_filter($persons, function ($person) use ($category_person_ids) {
            return in_array($person['identifier'], $category_person_ids);
        });
    }

    
    /*
     * Abruf der Personendaten anhand der Post-ID
     */
    public static function fetchPersonsByPostId(int $post_id): array {
        $person_identifiers = [];
        $post_type = self::$config->get('person_post_type') ?? 'custom_person';
        
        $args = [
            'post_type'      => $post_type,
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
        if (!empty($person_posts)) {
            foreach ($person_posts as $person_post) {
                $person_id = get_post_meta($person_post->ID, 'person_id', true);
                if (!empty($person_id)) {
                    $person_identifiers[] = $person_id;
                }
            }
        } else {
            // 3. Fallback: Prüfe ob $post_id existiert und ein person_id-Feld besitzt
            $post = get_post($post_id);
            if ($post) {
                $fallback_person_id = get_post_meta($post_id, 'person_id', true);
                if (!empty($fallback_person_id)) {
                    $person_identifiers[] = $fallback_person_id;
                }
            }
        }
        return self::process_persons_by_identifiers($person_identifiers);
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