<?php
declare(strict_types=1);
// Shortcode handler for RRZE FAUDIR
// namespace RRZE\FAUdir;
namespace RRZE\FAUdir;

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Template;
use RRZE\FAUdir\API;
use RRZE\FAUdir\Organization;

defined('ABSPATH') || exit;


class Shortcode { 
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
         
        // Haupt-Shortcode registrieren
        add_shortcode('faudir', [$this, 'render']);
        // Alias-Shortcodes registrieren
        add_action('init', [$this, 'register_aliases'], 15);
    }
  
    
    /*
     * Main render funktion: Für Shortcodes und Blocks
     */
    public function render($atts, $content = null): string {        
        // Only return early if it's a pure admin page, not the block editor        
        if (is_admin() && !wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST)) {
            return '';
        }
        $lang = FaudirUtils::getLang();

        
        // Extract the attributes from the shortcode
        $atts = shortcode_atts(
            array(
                'category'              => '',
                'identifier'            => '',
                'id'                    => '',
                'format'                => '',
                'url'                   => '',
                'show'                  => '',
                'hide'                  => '',
                'orgnr'                 => '',
                'orgid'                 => '',
                'sort'                  => '',
                'order'                 => 'asc',
                'function'              => '',
                'role'                  => '',
                'button-text'           => '',
                'format_displayname'    => '',
                'blockeditor'           => 'false',
                'display'               => 'person',
                'lang'                  => ''
            ),
            $atts
        );
       
          
        if (empty($atts['lang'])) {
            $atts['lang'] = $lang;
        } else {
            $lang = $atts['lang'];
        }
        
        if ((!empty($atts['function'])) && (empty( $atts['role']))) {
            $atts['role'] = $atts['function'];
        }
        
        
        $atts['display'] = $this->config->normalizeDisplay((string) ($atts['display'] ?? 'person'));
        $atts['format'] = $this->config->normalizeFormatForDisplay((string) ($atts['format'] ?? ''), $atts['display']);
        
       
                
        $show = $this->resolve_visible_fields_with_format($atts);
        $atts['show'] = implode(', ', $show);
        unset($atts['hide']);
        
        
      
        // do_action( 'rrze.log.info',"FAUdir\Shortcode (render): Args.",$atts);
        // If user is logged in and no-cache option is enabled, always fetch fresh data
        if (!empty($this->config->get('no_cache_logged_in')) && is_user_logged_in()) {
            $output = $this->fetch_and_render_fau_data($atts);
            $output = do_blocks($output);    
            $output = do_shortcode(shortcode_unautop($output));
            return $output;
        }
        

        ksort($atts);
        $cache_key = Constants::TRANSIENT_PREFIX_SHORTCODE . md5(wp_json_encode($atts));
        
        if (!empty($this->config->get('cache_timeout'))) {
            $cache_timeout = intval($this->config->get('cache_timeout')) * 60;
        }
        if ($cache_timeout < Constants::TRANSIENT_DEFAULT_TIMEOUT) {
            $cache_timeout = Constants::TRANSIENT_DEFAULT_TIMEOUT; //  = 900;
        }

        // Check if cached data exists
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            $output = do_blocks($cached_data);  
            return  do_shortcode(shortcode_unautop($output));
        }
        
        // Fetch and render fresh data
        $output = $this->fetch_and_render_fau_data($atts);
       
        // Cache the rendered output using Transients API
        set_transient($cache_key, $output, $cache_timeout);
        $output = do_blocks($output);            
        return do_shortcode(shortcode_unautop($output));

    }

    /*
    * Create Array for those fields we want to show
    */
   public function resolve_visible_fields_with_format(array $atts): array {
       // Block-Editor: Felder direkt übernehmen
       if (isset($atts['blockeditor']) && $atts['blockeditor'] === 'true') {

           $resolved_fields = FaudirUtils::normalizeStringArray(
               FaudirUtils::csvToArray((string) ($atts['show'] ?? ''))
           );

           if (($atts['display'] ?? '') === 'person') {
               $resolved_fields = $this->ensureDisplayNameFallback($resolved_fields);
           }

           return $resolved_fields;
       }


       // Felder aus Shortcode-Parametern lesen
       if (!empty($atts['show'])) {
           $show_fields = FaudirUtils::csvToArray((string) ($atts['show'] ?? ''));
           $show_fields = FaudirUtils::normalizeStringArray($show_fields);
       } else {
           $default_show_fields = $this->config->getDefaultFieldlistByFormat($atts['format'], $atts['display']);
           $show_fields = FaudirUtils::normalizeStringArray($default_show_fields);
       }

       $hide_fields = FaudirUtils::csvToArray((string) ($atts['hide'] ?? ''));
       $hide_fields = FaudirUtils::normalizeStringArray($hide_fields);

       // Alias-Mapping (fachlich → bleibt im Shortcode)
       $aliases = $this->config->get('args_person_to_faudir') ?? [];

       $show_fields = $this->mapFieldAliases($show_fields, $aliases);
       $hide_fields = $this->mapFieldAliases($hide_fields, $aliases);

       // Sichtbare Felder berechnen
       $fields = array_diff($show_fields, $hide_fields);
       $fields = array_values(array_unique($fields));

       // Nur gültige Felder für Format/Display
       $available = $this->config->getAvaibleFieldlistByFormat(
           $atts['format'],
           $atts['display']
       );

       $resolved_fields = array_values(array_intersect($available, $fields));

       if (($atts['display'] ?? '') === 'person') {
           $resolved_fields = $this->ensureDisplayNameFallback($resolved_fields);
       }

       // Abhängigkeiten entfernen (hide_on_parameter)
       $hide_on_parameter = $this->config->get('hide_on_parameter') ?? [];

       foreach ($hide_on_parameter as $trigger => $dependent_fields) {
           if (in_array($trigger, $resolved_fields, true)) {
               $resolved_fields = array_diff($resolved_fields, $dependent_fields);
           }
       }

       return array_values($resolved_fields);
   }
    
    /*
     * Check ob bei Namen mindestens displayname, vorname und nachnamen vorliegen und ergänze ggf.
     */
    private function ensureDisplayNameFallback(array $fields): array {
        $fieldsLower = array_map('strtolower', $fields);

        $hasNameField =
            in_array('displayname', $fieldsLower, true)
            || in_array('givenname', $fieldsLower, true)
            || in_array('familyname', $fieldsLower, true);

        if ($hasNameField) {
            return array_values(array_unique($fields));
        }

        if (!empty($this->config->get('default_fallback_displayname'))) {
            $fields[] = 'displayname';
        }

        return array_values(array_unique($fields));
    }
    
    /*
     * helperfunktion
     */
    private function mapFieldAliases(array $fields, array $aliases): array {
        $fields = FaudirUtils::normalizeStringArray($fields);
        $out = [];

        foreach ($fields as $field) {
            if (isset($aliases[$field]) && is_string($aliases[$field]) && $aliases[$field] !== '') {
                $field = $aliases[$field];
            }
            $out[] = $field;
        }

        return FaudirUtils::normalizeStringArray($out);
    }    
    
    /*
     * Create Output for Shortcode 
     */
    public function fetch_and_render_fau_data(array $atts): string {
        // Convert 'show' and 'hide' attributes into arrays
        $show_fields = array_map('trim', explode(',', $atts['show']));
       
      
        if ($atts['display'] == 'org') {
            return $this->createOrgOutput($atts, $show_fields);
        } else {
            return $this->createPersonOutput($atts, $show_fields);
        }
    }

    
    /*
     * Create Output for Person Display
     */
    public function createPersonOutput(array $atts, array $show_fields): string {                
        $args = $this->normalizePersonArgs($atts);
        $args = $this->applyRoleFallback($args);
            
        // do_action( 'rrze.log.info',"FAUdir\Shortcode (createPersonOutput): Args",$args);
        $persons = $this->fetchPersonsForPersonDisplay($args);
        
        
        // do_action( 'rrze.log.info',"FAUdir\Shortcode (createPersonOutput): Persons",$persons);
        if (empty($persons)) {
             return $this->createErrorOut(__('No people could be found for display using the specified parameters.', 'rrze-faudir'), 'createPersonOutput');
        }
        $persons = $this->applyPostFetchFilters($persons, $args);

        $persons = self::sortPersons(
            $persons,
            $args['sort_option'],
            $args['identifiers_for_order'],
            $args['order_option']
        );

        $template_dir = plugin()->getPath() . 'templates/';
        $template = new Template($this->config, $template_dir);

        $format_displayname = wp_strip_all_tags($args['format_displayname']);
        $templatefile = $args['display'] . '_' . $args['format'];
        
                     
        return $template->render($templatefile, [
            'show_fields' => $show_fields,
            'format_displayname' => $format_displayname,
            'persons' => $persons,
            'url' => $args['url'],
            'role' => $args['role']
        ]);
    }
    
    /*
     * Checke Input
     */
    private function normalizePersonArgs(array $atts): array {
        $args = [];

        $args['identifiers'] = FaudirUtils::normalizeStringArray(
            FaudirUtils::csvToArray((string) ($atts['identifier'] ?? ''))
        );

        $args['category'] = trim((string) ($atts['category'] ?? ''));
        $args['role'] = trim((string) ($atts['role'] ?? ''));
        $args['url'] = trim((string) ($atts['url'] ?? ''));
        $args['orgnr'] = trim((string) ($atts['orgnr'] ?? ''));
        $args['orgid'] = trim((string) ($atts['orgid'] ?? ''));
        $args['post_ids'] = trim((string) ($atts['id'] ?? ''));

        $args['display'] = (string) ($atts['display'] ?? 'person');
        $args['format'] = (string) ($atts['format'] ?? '');
        $args['format_displayname'] = (string) ($atts['format_displayname'] ?? '');

        $args['sort_option'] = (string) ($atts['sort'] ?? 'title_familyName');

        $sanitizedOrder = FaudirUtils::sanitizeOrderString((string) ($atts['order'] ?? 'asc'));
        $args['order_option'] = FaudirUtils::expandOrderStringForSort($sanitizedOrder, $args['sort_option']);

        // Für identifier_order Sortierung
        $args['identifiers_for_order'] = $args['identifiers'];

        return $args;
    }
    
    /*
     * Gewunschte Role für Ausgabe ermitteln
     */
    private function applyRoleFallback(array $args): array {
        if ($args['role'] === '') {
            return $args;
        }

        $hasDirectPersonSelection = (!empty($args['identifiers']) || !empty($args['post_ids']));
        $hasOrgScope = ($args['orgnr'] !== '' || $args['orgid'] !== '');

        if ($hasDirectPersonSelection || $hasOrgScope) {
            return $args;
        }

        $default_org = $this->config->get('default_organization');

        if (!empty($default_org['orgnr'])) {
            $args['orgnr'] = (string) $default_org['orgnr'];
            return $args;
        }

        // Keine Default-Orga -> Rolle deaktivieren, sonst kommt Quatsch raus.
        $args['role'] = '';
        return $args;
    }
    
    
    /*
     * Personendaten einsammeln
     */
    private function fetchPersonsForPersonDisplay(array $args): array {
        // 1) Direkte Personen (identifier oder post_id)
        if (!empty($args['identifiers']) || !empty($args['post_ids'])) {
            $persons = [];

            if (!empty($args['identifiers'])) {
                $persons = $this->process_persons_by_identifiers($args['identifiers']);
            } else {
                $persons = $this->fetchPersonsByPostId($args['post_ids']);
            }

            return $persons;
        }

        // 2) Kategorie
        if ($args['category'] !== '') {
            $person_identifiers = $this->getPersonIdentifiersByCategory($args['category']);
            if (empty($person_identifiers)) {
                return [];
            }

            return $this->process_persons_by_identifiers($person_identifiers);
        }

        // 3) Orgnr
        if ($args['orgnr'] !== '') {
            return $this->getPersonsByOrgNrs($args['orgnr'], $args['role']);
        }

        // 4) OrgId
        if ($args['orgid'] !== '') {
            return $this->getPersonsByFAUdirOrgId($args['orgid'], $args['role']);
        }

        return [];
    }
    
    
    /*
     * Filter der Personenliste nach Kategorie oder Orgnr
     */
    private function applyPostFetchFilters(array $persons, array $args): array {
        $hasDirectPersonSelection = !empty($args['identifiers']) || !empty($args['post_ids']);

        // Kategorie-Filter nur sinnvoll, wenn Personen nicht ohnehin aus Kategorie stammen
        if ($args['category'] !== '' && $hasDirectPersonSelection) {
            $persons = $this->filterPersonsByCategory($persons, $args['category']);
        }

        // Org-Filter nur sinnvoll, wenn Personen direkt selektiert wurden
        if ($args['orgnr'] !== '' && $hasDirectPersonSelection) {
            $persons = $this->filterPersonsByOrganization($persons, $args['orgnr']);
        }

        return $persons;
    }
    
        
    
    /*
     * Helper für das Rendering von Personenpages (CPT)
     */
    public function renderPersonPage(string $personId, array $showFields = []): string {
        $atts = [
            'display' => 'person',
            'format' => 'page',
            'identifier' => $personId,
        ];

        if (!empty($showFields)) {
            $atts['show'] = implode(', ', $showFields);
        }

        return $this->render($atts);
    }
    
    
    /*
     * Create Output for Org Display
     */
    public function createOrgOutput(array $atts, array $show_fields): string {     
        $orgnr      = $atts['orgnr'];
            // Org Nr der Einrichtung, die anzuzeigen ist
        $orgid      = $atts['orgid'];
            // Org Identifier der Einrichtung, die anzuzeigen ist
        $display    = $atts['display'];
            // Art der anzuzeigendenden Daten.   
        $url        = $atts['url'];
            // optionale URL der ORG überschreiben
        
        $org = new Organization();
        $org->setConfig($this->config);
        if (FaudirUtils::isValidOrgnr($orgnr)) {   
            
            $id = $org->getIdentifierbyOrgnr($orgnr);
            $orgid = FaudirUtils::sanitizeOrganizationId($id);
            if ($orgid !== null) {
                $org->getOrgbyAPI($orgid);
                $orgdata = $org->toArray();
            } else {
                return $this->createErrorOut(__('Bad value for parameter orgnr', 'rrze-faudir'), 'createOrgOutput');
            }
        } elseif (!empty($orgid)) {
            $oid = FaudirUtils::sanitizeOrganizationId($orgid);
            if ($oid !== null) {
                $org->getOrgbyAPI($oid);
                $orgdata = $org->toArray();
            } else {
                return $this->createErrorOut(__('Bad value for parameter orgid', 'rrze-faudir'), 'createOrgOutput');
            }

        } else {
            return $this->createErrorOut(__('Invalid oder missing parameter orgnr or orgid', 'rrze-faudir'), 'createOrgOutput');
        }
        
        
           
        $template_dir = plugin()->getPath() . 'templates/';
        $template = new Template($this->config, $template_dir);
     

        // check and sanitize for format for displayname
        $templatefile = $display.'_'.$atts['format'];
        return $template->render($templatefile, [
            'show_fields'   => $show_fields,
            'orgdata'       => $orgdata,
            'url'           => $url,
        ]);
       
        
        
    }
    
    
    /*
     * Create Error Output in cases it is not promptet within templates
     */
    public function createErrorOut(string $error, string $errorloginfo = ''): string {
        if (empty($error)) {
            $error = __('Error on creating output', 'rrze-faudir');
        }
        do_action( 'rrze.log.error',"FAUdir\Shortcode: Error in {$errorloginfo}", $error);
        $out = '';
        
        if (!empty($this->config->get('show_error_message'))) {
          $out = '<div class="faudir-error">';
          $out .= $error;
          $out .= '</div>';
        }
        return $out;
    }

    
   /*
    * Hole Personeneinträge aus dem CPT, die zu einer definierten Kategorie gehören
    */ 
    public function getPersonIdentifiersByCategory(string $category): array {
        if (empty($category)) {
            return [];
        }

        $taxonomy = $this->config->get('person_taxonomy') ?? 'custom_taxonomy';
        $post_type = $this->config->get('person_post_type') ?? 'custom_person';

        $categories = array_map('trim', explode(',', $category));
        $person_identifiers = [];

        foreach ($categories as $cat_term) {
            // Versuche den Begriff über Slug oder Name zu finden
            $term = get_term_by('slug', $cat_term, $taxonomy);
            if (!$term) {
                $term = get_term_by('name', $cat_term, $taxonomy);
            }

            if ($term && !is_wp_error($term)) {
                $args = [
                    'post_type'      => $post_type,
                    'tax_query'      => [
                        [
                            'taxonomy' => $taxonomy,
                            'field'    => 'term_id',
                            'terms'    => $term->term_id,
                        ],
                    ],
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                ];

                $posts = get_posts($args);

                foreach ($posts as $post_id) {
                    $person_id = get_post_meta($post_id, 'person_id', true);
                    if (!empty($person_id)) {
                        $person_identifiers[] = $person_id;
                    }
                }
            }
        }

        // Entferne doppelte Einträge
        return array_values(array_unique($person_identifiers));
    }

    
    /**
    * Holt Personen zu mehreren Organisations-Nummern (Komma-getrennt) und führt die Ergebnisse zusammen.
    * Eingabe:
    *   - $orgnrs (string|array|int): z. B. "123456, 1234567890" oder [123456, 1234567890] oder 1234567890.
    *   - $role (string|null): optional, kommaseparierte Rollenbezeichnungen zur Filterung (z. B. "Professor,Postdoc").
    * Rückgabe:
    *   - array: zusammengeführte Personenliste (kontakt-merge bei Mehrtreffern).
    */
   public function getPersonsByOrgNrs(string|array|int $orgnrs, ?string $role = null): array {
       // Eingabe normalisieren → Array von Strings (ohne Leerzeichen, ohne leere Einträge)
       if (is_int($orgnrs)) {
           $orgList = [(string) $orgnrs];
       } elseif (is_string($orgnrs)) {
           $orgList = array_values(array_filter(array_map('trim', explode(',', $orgnrs)), 'strlen'));
       } else { // array
           $orgList = array_values(array_filter(array_map(
               fn($v) => is_string($v) ? trim($v) : (string) $v,
               $orgnrs
           ), 'strlen'));
       }

       if (empty($orgList)) {
           return [];
       }

       // Personen nach Identifier sammeln (für Duplikat-Check & Merge)
       $byId = [];

       foreach ($orgList as $oneOrgNr) {
           $list = $this->getPersonsByOrgNr($oneOrgNr, $role);
           if (empty($list) || !is_array($list)) {
               continue;
           }

           foreach ($list as $person) {
               $pid = $person['identifier'] ?? null;
               if (!$pid) {
                   continue;
               }

               if (!isset($byId[$pid])) {
                   // Erstes Auftauchen dieser Person
                   $byId[$pid] = $person;
                   continue;
               }

               // Person existiert schon → Kontakte zusammenführen (einfaches De-Duping)
               if (isset($person['contacts']) && is_array($person['contacts'])) {
                   if (!isset($byId[$pid]['contacts']) || !is_array($byId[$pid]['contacts'])) {
                       $byId[$pid]['contacts'] = [];
                   }

                   // Index bestehender Kontakte aufbauen (nach Org-ID + Funktion)
                   $existingKeys = [];
                   foreach ($byId[$pid]['contacts'] as $c) {
                       $key = (string)($c['organization']['identifier'] ?? '');
                       $fn  = $c['function'] ?? ($c['functionLabel']['de'] ?? ($c['functionLabel']['en'] ?? ''));
                       $existingKeys[$key . '|' . (string)$fn] = true;
                   }

                   // Neue Kontakte hinzufügen, falls noch nicht vorhanden
                   foreach ($person['contacts'] as $c) {
                       $key = (string)($c['organization']['identifier'] ?? '');
                       $fn  = $c['function'] ?? ($c['functionLabel']['de'] ?? ($c['functionLabel']['en'] ?? ''));
                       $idx = $key . '|' . (string)$fn;
                       if (!isset($existingKeys[$idx])) {
                           $byId[$pid]['contacts'][] = $c;
                           $existingKeys[$idx] = true;
                       }
                   }
               }
           }
       }

       return array_values($byId);
   }


   /**
    * Liefert Personen zu einer Organisations-Nr (10-stellig exakt oder 6-stelliger Präfix).
    * Optional:
    *   - $role (string|null): kommaseparierte Rollen (z. B. "Professor, Postdoc").
    * Verhalten:
    *   - 10-stellig: Kontakte müssen in genau dieser Organisation sein; isRole prüft Org-ID exakt.
    *   - 6-stellig: Kontakte werden auf die per Präfix gefundenen Orgs gefiltert; isRole prüft NUR die Rolle.
    * Rückgabe:
    *   - array: Personen (nur mit passenden Kontakten), [] wenn keine Treffer.
    */
   public function getPersonsByOrgNr(string|int $orgnr, ?string $role = null): array {
        // Nur Ziffern verwenden
        $digits = preg_replace('/\D+/', '', (string) $orgnr);
        $orgParams = [];
        $enforceOrgInIsRole = false;
        $exact = FaudirUtils::sanitizeOrgnr($digits);
        if ($exact !== null) {
            // exakt 10-stellig -> "normale" Suche wie bisher
            $orgParams['orgnr'] = $exact;
            $enforceOrgInIsRole = true;
        } else {
            $prefix = FaudirUtils::sanitizeOrgnrPrefix($digits);
            if ($prefix !== null) {
                // 6-9-stelliger Präfix -> Teilsuche via lq[reg]
                $orgParams['lq'] = 'disambiguatingDescription[reg]=^' . $prefix;
            } else {
                return [];
            }
       }

       // API
       $api    = new API($this->config);

       // Organisation(en) abrufen
       $orgdata = $api->getOrgList(0, 0, $orgParams);
       if (empty($orgdata['data'])) {
           return [];
       }

       // Org-IDs einsammeln
       $orgIds = array_values(array_unique(array_filter(array_map(
           static fn($o) => $o['identifier'] ?? null,
           $orgdata['data']
       ))));
       if (empty($orgIds)) {
           return [];
       }
       $orgIdSet = array_flip(array_map('strval', $orgIds)); // schneller Lookup

       // LQ für Personen
       if (count($orgIds) === 1) {
           $lq = 'contacts.organization.identifier[eq]=' . $orgIds[0];
       } else {
           $safeIds = array_map(static fn($id) => preg_quote((string) $id, '/'), $orgIds);
           $lq = 'contacts.organization.identifier[reg]=^(' . implode('|', $safeIds) . ')$';
       }

       // Personen abrufen
       $params = ['lq' => $lq];
       $data   = $api->getPersons(0, 0, $params);

       $person  = new Person();
       $person->setConfig($this->config);

       $persons = [];
       $hasRoleFilter = (is_string($role) && trim($role) !== '');

       if (!empty($data['data'])) {
           foreach ($data['data'] as $persondata) {
               $person->populateFromData($persondata);
               $person->reloadContacts(); // $person->contacts: Array von Kontakt-Arrays
               // Kein Rollenfilter → Person komplett übernehmen
               if (!$hasRoleFilter) {
                   $persons[] = $person->toArray();
                   continue;
               }

               // Mit Rollenfilter: nur passende Kontakte übernehmen
               $matchedContacts = [];
               if (!empty($person->contacts) && is_array($person->contacts)) {
                   foreach ($person->contacts as $contactData) {
                       if (!is_array($contactData)) {
                           continue;
                       }
                       $contactOrgId = $contactData['organization']['identifier'] ?? null;

                       // Immer: Kontakt muss zu einer der gefundenen Orgs gehören (auch bei 6-stelligem Präfix)
                       if (!$contactOrgId || !isset($orgIdSet[(string) $contactOrgId])) {                          
                           continue;
                       }

                       $contact = new Contact($contactData);
                       $contact->setConfig($this->config);

                       // Nur bei 10-stelliger Orgnr die exakte Org-ID an isRole übergeben; sonst NULL
                       $orgArgForIsRole = $enforceOrgInIsRole ? (string) $contactOrgId : null;
                      
                       
                       if ($contact->isRole($role, $orgArgForIsRole)) {
                           $matchedContacts[] = $contact->toArray();
                       }
                       
                   }
               }

               if (!empty($matchedContacts)) {
                   $personArr = $person->toArray();
                   $personArr['contacts'] = $matchedContacts;
                   $persons[] = $personArr;
               }
           }
       }

       return $persons ?: [];
   }

    /**
     * Liefert Personen über einen FAUdir-Organisations-Identifier (oder passende URL).
     * Optional:
     *   - $role (string|null): kommaseparierte Rollen (z. B. "Professor, Postdoc").
     *
     * Verhalten:
     *   - Akzeptiert als $faudir_orgid entweder einen alphanumerischen Identifier
     *     ODER eine URL: https://faudir.fau.de/public/org/<Identifier>[/]
     *   - Baut LQ direkt als: contacts.organization.identifier[eq]=<identifier>
     *   - Ohne $role: Person wird mit allen Kontakten übernommen.
     *   - Mit $role: Pro Person werden nur die Kontakte übernommen, die:
     *       * zur angegebenen Organisation gehören (Identifier exakt gleich) UND
     *       * eine der Rollen matchen (function / functionLabel.de / functionLabel.en).
     *
     * @param string   $faudir_orgid  FAUdir-Org-Identifier ODER vollständige URL dazu
     * @param string|null  $role          Optional: kommaseparierte Rollen
     * @return array                      Liste passender Personen (jeweils als Array); [] wenn keine Treffer
     */
    public function getPersonsByFAUdirOrgId(string $faudir_orgid, ?string $role = null): array {
        // Identifier aus URL extrahieren, falls nötig
        $id = trim((string) $faudir_orgid);

        if (preg_match('#^https?://faudir\.fau\.de/public/org/([A-Za-z0-9]+)(?:/)?$#i', $id, $m)) {
            $id = $m[1];
        }

        $orgid = FaudirUtils::sanitizeOrganizationId($id);
        if ($orgid === null) {
            do_action('rrze.log.warning', "FAUdir\Shortcode (getPersonsByFAUdirOrgId): Invalid OrgId {$id}.");
            return [];
        }
        


        // API-Setup
        $api    = new API($this->config);

        // LQ direkt mit exakter Org-ID
        $lq     = 'contacts.organization.identifier[eq]=' . $orgid;
        $params = ['lq' => $lq];

        $data = $api->getPersons(0, 0, $params);

        $person = new Person();
        $person->setConfig($this->config);

        $persons        = [];
        $hasRoleFilter  = (is_string($role) && trim($role) !== '');

        if (!empty($data['data'])) {
            foreach ($data['data'] as $persondata) {
                $person->populateFromData($persondata);
                $person->reloadContacts(); // füllt $person->contacts

                // Kein Rollenfilter → Person komplett übernehmen
                if (!$hasRoleFilter) {
                    $persons[] = $person->toArray();
                    continue;
                }

                // Mit Rollenfilter: nur Kontakte aus dieser Org + passender Rolle übernehmen
                $matchedContacts = [];
                if (!empty($person->contacts) && is_array($person->contacts)) {
                    foreach ($person->contacts as $contactData) {
                        if (!is_array($contactData)) {
                            continue;
                        }

                        $contactOrgId = $contactData['organization']['identifier'] ?? null;
                        if (!$contactOrgId || (string) $contactOrgId !== $orgid) {
                            // Dieser Kontakt gehört nicht zu der gesuchten Organisation
                            continue;
                        }

                        $contact = new Contact($contactData);
                        $contact->setConfig($this->config);

                        // isRole prüft NUR diesen Kontakt; exakte Org-ID mitgeben
                        if ($contact->isRole($role, $orgid)) {
                            $matchedContacts[] = $contact->toArray();
                        }
                    }
                }

                if (!empty($matchedContacts)) {
                    $personArr = $person->toArray();
                    $personArr['contacts'] = $matchedContacts; // nur die passenden Kontakte
                    $persons[] = $personArr;
                }
            }
        }

        return $persons ?: [];
    }

    
    
    /*
     * Filter Personen nach ihrer Org
     */
    public function filterPersonsByOrganization(array $persons, ?string $orgnr): array {
        if (empty($orgnr)) {
            return $persons;
        }

        $api = new API($this->config);
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
    public function filterPersonsByCategory(array $persons, string $category): array {
        if (empty($category)) {
            return $persons;
        }

        // Taxonomie-Slug aus der Konfiguration lesen (Fallback: 'custom_taxonomy')
        $taxonomy = $this->config->get('person_taxonomy') ?? 'custom_taxonomy';
        $post_type = $this->config->get('person_post_type') ?? 'custom_person';
        
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
     * Abruf der Personendaten anhand einer Post-ID oder einer kommaseparierten Liste von Post-IDs.
     * Optionaler Input: $post_ids (int|string) – z.B. 123 oder "123, 456,789".
     * Rückgabe: array – Ergebnis von $this->process_persons_by_identifiers($person_identifiers).
     */
    public function fetchPersonsByPostId(int|string $post_ids): array {
        $person_identifiers = [];
        $post_type = $this->config->get('person_post_type') ?? 'custom_person';

        // Eingabe normalisieren: einzelne ID → Array; CSV-String → Array
        if (is_string($post_ids)) {
            $ids = preg_split('/\s*,\s*/', $post_ids, -1, PREG_SPLIT_NO_EMPTY);
            $ids = array_map('absint', $ids);
        } else {
            $ids = [absint($post_ids)];
        }

        // Pro Post-ID wie bisher verfahren
        foreach ($ids as $post_id) {
            if ($post_id <= 0) {
                continue;
            }

            $post = get_post($post_id);

            if ($post && ($post_type === $post->post_type)) {
                // Direkter Treffer im Ziel-CPT
                $faudir_id = get_post_meta($post_id, 'person_id', true);
                if (!empty($faudir_id)) {
                    $person_identifiers[] = $faudir_id;
                }
            } else {
                // Prüfe auf alte IDs (Migration): Mapping finden
                $args = [
                    'post_type'      => $post_type,
                    'meta_query'     => [
                        [
                            'key'     => 'old_person_post_id',
                            'value'   => $post_id,
                            'compare' => '=',
                        ],
                    ],
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                ];

                $person_posts = get_posts($args);

                if (!empty($person_posts)) {
                    foreach ($person_posts as $person_post_id) {
                        $faudir_id = get_post_meta($person_post_id, 'person_id', true);
                        if (!empty($faudir_id)) {
       //                     do_action( 'rrze.log.info',"FAUdir\Shortcode (fetchPersonsByPostId): Found FAUdir Id:  ". $faudir_id." in Post for ".$post_id);
                                                       
                            $person_identifiers[] = $faudir_id;
                        }
                    }
                }
            }
        }

        // Duplikate entfernen
        $person_identifiers = array_values(array_unique(array_filter($person_identifiers)));

        return $this->process_persons_by_identifiers($person_identifiers);
    }


    /**
     * Process persons by a list of identifiers.
     */
    public function process_persons_by_identifiers(array $identifiers): array {
        $persons = [];

        $person = new Person();
        $person->setConfig($this->config);
                
        foreach ($identifiers as $identifier) {
            $personId = FaudirUtils::sanitizePersonId($identifier);
            if ($personId !== null) {               
                $found = $person->getPersonbyAPI($personId);
                if ($found) {
                    $person->reloadContacts();
                    $persons[] = $person->toArray();
                } else {
                    $persons[] = [
                        'error' => true,
                        /* translators: 1: FAUdir Identifier Key  */
                        'message' => sprintf(__('Person with ID %s does not exist', 'rrze-faudir'), $personId)
                    ];
                }
            } else {
                do_action('rrze.log.warning', "FAUdir\Shortcode (process_persons_by_identifiers): Invalid personId {$identifier}.");
            }
        }

        return $persons;
    }

    /**
     * Fetch and process persons based on query parameters.
     */
    public function fetch_and_process_persons(?string $lq = null): array {
        $params = $lq ? ['lq' => $lq] : [];
        $api = new API($this->config);
        $data = $api->getPersons(0, 0, $params);
        
        $person = new Person();
        $person->setConfig($this->config);
        
        $persons = [];
        if (!empty($data['data'])) {
            foreach ($data['data'] as $persondata) {
                $person->populateFromData($persondata);
                $person->reloadContacts();
                $persons[] = $person->toArray();  
            }
        }

        return $persons;
    }

    /*
     * Übernehme alte Shortcodes von FAU Person, sofern FAU-Person nicht mehr aktiv ist.
     */
    public function register_aliases(): void {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!is_plugin_active('fau-person/fau-person.php')) {
            add_shortcode('kontakt', [$this, 'kontakt_to_faudir']);
            add_shortcode('kontaktliste', [$this, 'kontaktliste_to_faudir']);
        }
    }

    public function kontakt_to_faudir($atts, $content = null): string {
        $atts_string = $this->atts_to_string($atts);
        EnqueueScripts::enqueue_frontend();
        return do_shortcode(shortcode_unautop('[faudir ' . $atts_string . ']' . $content . '[/faudir]'));
    }

    public function kontaktliste_to_faudir($atts, $content = null): string {
        if (!isset($atts['format'])) {
            $atts['format'] = 'list';
        }
        $atts_string = $this->atts_to_string($atts);
        EnqueueScripts::enqueue_frontend();
        return do_shortcode(shortcode_unautop('[faudir ' . $atts_string . ']' . $content . '[/faudir]'));
    }

    protected function atts_to_string(array $atts): string {
        $atts_string = '';
        foreach ($atts as $key => $value) {
            $atts_string .= $key . '="' . esc_attr($value) . '" ';
        }
        return trim($atts_string);
    }

   /**
    * Sortiert ein Personen-Array gruppiert/lexikographisch nach mehreren Kriterien.
    * Optionale Eingaben: $sortOption (komma/leerzeichen-separiert, inkl. Legacy "title_familyName"),
    * $identifiers (für identifier_order), $order (pro Kriterium asc|desc).
    * Rückgabe: sortiertes Personen-Array (stabil).
    */
   public static function sortPersons(array $persons, string $sortOption = 'familyName', array $identifiers = [], string $order = 'asc'): array {
       // 1) Nichts zu tun bei 0/1 Element
       if (count($persons) < 2) {
           return $persons;
       }
     //   do_action( 'rrze.log.info',"FAUdir\Shortcode (sortPersons): Sortoption $sortOption, order: $order");

       // 2) Kriterien parsen (inkl. Abwärtskompatibilität)
       $rawTokens = preg_split('/[,\s]+/', trim($sortOption), -1, PREG_SPLIT_NO_EMPTY) ?: ['familyName'];
       $criteria  = [];
       $seen      = [];

       foreach ($rawTokens as $tokenRaw) {
           $t = strtolower($tokenRaw);

           // Legacy-Mehrfachkriterium: "title_familyName" -> honorificprefix, familyname
           if ($t === 'title_familyname') {
               foreach (['honorificprefix', 'familyname'] as $tt) {
                   if (!isset($seen[$tt])) {
                       $seen[$tt] = true;
                       $criteria[] = $tt;
                   }
               }
               continue;
           }

           // Einfache Aliase (Abwärtskompatibilität)
           $t = match ($t) {
               'title', 'prof_first' => 'honorificprefix',
               'head_first'          => 'role',
               'surname'             => 'familyname',
               'firstname'           => 'givenname',
               'identifier'          => 'identifier_order',
               default               => $t,
           };

           if (!isset($seen[$t])) {
               $seen[$t] = true;
               $criteria[] = $t;
           }
       }

       // 3) Falls identifier_order verlangt ist → nur danach sortieren
       if (in_array('identifier_order', $criteria, true)) {
           foreach ($persons as $i => &$p) { $p['__idx'] = $i; }
           unset($p);

           $index = array_flip($identifiers);
           usort($persons, function($a, $b) use ($index) {
               $pa = $index[$a['identifier'] ?? ''] ?? PHP_INT_MAX;
               $pb = $index[$b['identifier'] ?? ''] ?? PHP_INT_MAX;
               if ($pa !== $pb) return $pa <=> $pb;
               return ($a['__idx'] ?? 0) <=> ($b['__idx'] ?? 0);
           });
           foreach ($persons as &$p) { unset($p['__idx']); }
           unset($p);
           return $persons;
       }

       // 4) Orders parsen (pro Kriterium), fehlende = 'asc'
       $rawOrders = preg_split('/[,\s]+/', trim($order), -1, PREG_SPLIT_NO_EMPTY) ?: ['asc'];
       $orders    = [];
       $oi = 0;
       foreach ($criteria as $_) {
           $dir = strtolower($rawOrders[$oi] ?? 'asc');
           $orders[] = ($dir === 'desc') ? 'desc' : 'asc';
           if ($oi < count($rawOrders) - 1) { $oi++; }
       }

       // 5) Stabilitätsindex vormerken
       foreach ($persons as $i => &$p) { $p['__idx'] = $i; }
       unset($p);

       // 6) Gruppierte Rekursion anwenden
       $sorted = self::sortByCriteriaBuckets($persons, $criteria, $orders, $identifiers);

       // 7) Aufräumen
       foreach ($sorted as &$p) { unset($p['__idx']); }
       unset($p);

       return $sorted;
   }


   /**
    * Gruppiert nach erstem Kriterium und sortiert rekursiv mit verbleibenden Kriterien.
    * Optionale Eingaben: keine (intern verwendet).
    * Rückgabe: array (stabil sortiert).
    */
   private static function sortByCriteriaBuckets(array $persons, array $criteria, array $orders, array $identifiers): array {
       if (empty($criteria) || count($persons) < 2) {
           // Letzte Feinsortierung nur zur deterministischen Stabilität (FamilyName, GivenName, Fallback: Ursprungsindex)
           $coll = collator_create('de_DE');
           usort($persons, function($a, $b) use ($coll) {
               $c = collator_compare($coll, (string)($a['familyName'] ?? ''), (string)($b['familyName'] ?? ''));
               if ($c !== 0) return $c;
               $c = collator_compare($coll, (string)($a['givenName'] ?? ''), (string)($b['givenName'] ?? ''));
               if ($c !== 0) return $c;
               return ($a['__idx'] ?? 0) <=> ($b['__idx'] ?? 0);
           });
           return $persons;
       }

       $first    = array_shift($criteria);
       $dir      = array_shift($orders) ?: 'asc';

       [$buckets, $orderKeys] = self::partitionByCriterion($persons, $first, $dir, $identifiers);

       $out = [];
       foreach ($orderKeys as $k) {
           $chunk = $buckets[$k] ?? [];
           if (empty($chunk)) { continue; }
           $chunk = self::sortByCriteriaBuckets($chunk, $criteria, $orders, $identifiers);
           $out   = array_merge($out, $chunk);
       }
       return $out;
   }

   /**
    * Bildet Buckets + deren Reihenfolge für EIN Kriterium (inkl. ASC/DESC).
    * Unterstützte Kriterien: honorificprefix, role, familyname, givenname, email, identifier_order.
    * Rückgabe: [ array $buckets, array $orderedKeys ]
    */
   private static function partitionByCriterion(array $persons, string $criterion, string $direction, array $identifiers): array {
       $buckets = [];
       $order   = [];

       switch ($criterion) {
           case 'honorificprefix': {
               // Bucket pro sortorder (Titel) bzw. „no_title“
               $keySet = [];
               foreach ($persons as $p) {
                   $norm = \RRZE\FAUdir\FaudirUtils::normalizeAcademicTitle((string)($p['honorificPrefix'] ?? ''));
                   if (!empty($norm['key'])) {
                       // z.B. t:0001 … (fixe Breite für sortierbare Strings)
                       $k = 't:' . str_pad((string)($norm['sortorder'] ?? 9999), 4, '0', STR_PAD_LEFT);
                   } else {
                       $k = 'z:no_title';
                   }
                   $buckets[$k][] = $p;
                   $keySet[$k] = true;
               }
               $order = array_keys($keySet);
               sort($order, SORT_STRING);                // Titel aufsteigend nach sortorder
               if ($direction === 'desc') { $order = array_reverse($order); }
               break;
           }

           case 'role': {
               // Heads (leader/deputy) vor anderen
               foreach ($persons as $p) {
                   $isHead = self::hasHeadRole($p['contacts'] ?? []);
                   $k = $isHead ? 'a:head' : 'b:other';
                   $buckets[$k][] = $p;
               }
               $order = ['a:head', 'b:other'];
               if ($direction === 'desc') { $order = array_reverse($order); }
               break;
           }

           case 'identifier_order': {
               // (Normalerweise bereits oben exklusiv behandelt – hier fallback)
               $index = array_flip($identifiers);
               foreach ($persons as $p) {
                   $pos = $index[$p['identifier'] ?? ''] ?? PHP_INT_MAX;
                   $k = 'i:' . str_pad((string)$pos, 12, '0', STR_PAD_LEFT);
                   $buckets[$k][] = $p;
               }
               $order = array_keys($buckets);
               sort($order, SORT_STRING);
               if ($direction === 'desc') { $order = array_reverse($order); }
               break;
           }

           case 'familyname':
           case 'givenname': {
                $fieldMap = [
                    'familyname' => 'familyName',
                    'givenname'  => 'givenName'
                ];
                $field = $fieldMap[$criterion] ?? $criterion;
            
               // Gleichheits-Buckets nach Feldwert (case-insensitive)
          //     $field = $criterion; // wie benannt gespeichert
               foreach ($persons as $p) {
                   $val = (string)($p[$field] ?? '');
                   $key = 'v:' . FaudirUtils::lower($val);
                   $buckets[$key][] = $p;
               }
               // Reihenfolge der Buckets nach sichtbarem Wert sortieren
               $coll = collator_create('de_DE');
               $order = array_keys($buckets);
               usort($order, function($ka, $kb) use ($buckets, $coll, $field) {
                   $va = (string)($buckets[$ka][0][$field] ?? '');
                   $vb = (string)($buckets[$kb][0][$field] ?? '');
                   return collator_compare($coll, $va, $vb);
               });
               if ($direction === 'desc') { $order = array_reverse($order); }
               break;
           }
           case 'email': {
                // Bucket pro (kanonisierter) E-Mail. Leere E-Mails als eigener Bucket.
                $keyValue = []; // Map: Bucket-Key -> echter sortierbarer E-Mail-String (lowercased)
                foreach ($persons as $p) {
                    // <-- Falls getSortableEmail() woanders liegt, ggf. voll qualifizieren:
                    // $email = \RRZE\FAUdir\Shortcode::getSortableEmail($p);
                    $email = (string) self::getSortableEmail($p);
                    $email = mb_strtolower(trim($email), 'UTF-8');

                    // Leere E-Mails in separaten Bucket, der bei ASC ans Ende soll
                    $bucketKey = ($email === '') ? 'e:__no_email__' : 'e:' . $email;

                    $buckets[$bucketKey][] = $p;
                    $keyValue[$bucketKey] = $email; // für die Sortierung der Buckets
                }

                // Bucket-Reihenfolge nach E-Mail sortieren ('' zuletzt bei ASC)
                $order = array_keys($buckets);
                usort($order, function($ka, $kb) use ($keyValue) {
                    $ea = $keyValue[$ka] ?? '';
                    $eb = $keyValue[$kb] ?? '';
                    if ($ea === '' && $eb !== '') return 1;   // '' nach hinten
                    if ($ea !== '' && $eb === '') return -1;  // echte Mails nach vorne
                    return strcmp($ea, $eb);                  // lexikografisch
                });

                if ($direction === 'desc') {
                    // Bei DESC einfach umdrehen: '' landet dann vorn
                    $order = array_reverse($order);
                }
                break;
            }
           default: {
               // Unbekannt → ein Bucket (keine Gruppierung)
               $buckets['all'] = $persons;
               $order = ['all'];
               break;
           }
       }

       return [$buckets, $order];
   }

   /**
    * Prüft, ob in den Kontakten eine Head-Rolle vorkommt (leader/deputy).
    * Optionale Eingaben: keine.
    * Rückgabe: bool
    */
   private static function hasHeadRole(array $contacts): bool {
       foreach ($contacts as $c) {
           $fn = strtolower((string)($c['function'] ?? ''));
           if ($fn === 'leader' || $fn === 'deputy' || $fn === 'professor') { return true; }
       }
       return false;
   }

   
   /**
    * Liefert eine kanonische E-Mail für die Sortierung.
    * Optionale Eingabe: keine (Person kann E-Mail auch in Contacts/Workplaces haben).
    * Rückgabe: string (lowercased; '' falls nichts gefunden).
    */
   private static function getSortableEmail(array $person): string {
       $candidates = [];

       // 1) Top-Level
       if (!empty($person['email']) && is_string($person['email'])) {
           $candidates[] = $person['email'];
       }
       if (!empty($person['mails']) && is_array($person['mails'])) {
           $candidates = array_merge($candidates, $person['mails']);
       }

       // 2) Contacts + Workplaces
       if (!empty($person['contacts']) && is_array($person['contacts'])) {
           foreach ($person['contacts'] as $c) {
               if (!empty($c['mails']) && is_array($c['mails'])) {
                   $candidates = array_merge($candidates, $c['mails']);
               }
               if (!empty($c['workplaces']) && is_array($c['workplaces'])) {
                   foreach ($c['workplaces'] as $wp) {
                       if (!empty($wp['mails']) && is_array($wp['mails'])) {
                           $candidates = array_merge($candidates, $wp['mails']);
                       }
                   }
               }
           }
       }

       // filtern & normalisieren
       $candidates = array_values(array_filter(array_map('strval', $candidates), static function($e) {
           $e = trim($e);
           // grobe E-Mail-Heuristik – reicht für Sortierung
           return $e !== '' && strpos($e, '@') !== false;
       }));

       if (empty($candidates)) {
           return '';
       }

       // lowercased lexikografische Sortierung und den "kleinsten" Wert nehmen
       $norm = array_map(static fn($e) => strtolower(trim($e)), $candidates);
       sort($norm, SORT_STRING);
       return $norm[0];
   }

  

}