<?php


namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Config {
    private string $optionName = 'rrze_faudir_options';
    private array $config = [
        'version'                           => 12,  // please count this up any time we change the config array
        'person_slug'                       => 'faudir',
        'redirect_to_canonicals'            => false,
        'redirect_archivpage_uri'           => '',
        'api_key'                           => '',
        'api-baseurl'                       => 'https://api.fau.de/pub/v1/opendir/',
        'faudir-url'                        => 'https://faudir.fau.de/',
        'no_cache_logged_in'                => true,
        'cache_timeout'                     => 120, // Minimum 15 minutes
        'transient_time_for_org_id'         => 1, // Minimum 1 day
        'show_error_message'                => false,
        'fallback_link_faudir'              => true,
        'default_normalize_honorificPrefix' => false,
        'default_visible_copyrightmeta'     => false,
        'default_visible_bildunterschrift'  => false,
        'default_placeholder_image_with_signature'  => true,
        'enable_history'                    => 0,   // History & Revisions for CPT
        'button_link_title'                 => '',
        'default_display_order'     => [
            'table' => ['image', 'displayname', 'familyName', 'givenName', 'jobTitle', 'organization', 'phone', 'email', 'url', 'socialmedia','address', 'room', 'floor', 'faumap', 'teasertext', 'link'],
            'list'  => ['displayname', 'familyName', 'givenName', 'jobTitle', 'url', 'email', 'socialmedia', 'room', 'floor', 'address','faumap', 'link']
        ],
        'avaible_fields_byformat'   => [
            'table'         => ['image', 'displayname','honorificPrefix','honorificSuffix', 'givenName',  'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link', 'format_displayname'],
            'list'          => ['displayname', 'honorificPrefix','honorificSuffix', 'givenName', 'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax',  'url', 'email', 'socialmedia', 'organization','address', 'room', 'floor', 'city', 'faumap', 'link', 'format_displayname'],
            'compact'       => ['image', 'displayname', 'honorificPrefix','honorificSuffix', 'givenName', 'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link', 'format_displayname'],
            'page'          => ['image', 'displayname', 'honorificPrefix','honorificSuffix', 'givenName', 'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor', 'faumap', 'teasertext', 'content', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'format_displayname'],            
            'card'          => ['image', 'displayname', 'givenName',  'familyName', 'jobTitle', 'phone', 'fax', 'organization', 'url', 'email', 'socialmedia', 'link', 'format_displayname'],
            'org-compact'   => [ 'name', 'alternateName', 'phone', 'fax', 'email', 'url', 'socialmedia','address', 'postalAddress', 'faumap', 'officehours', 'consultationhours', 'text'],
            'org-default'   => [ 'name', 'alternateName', 'phone', 'fax', 'email', 'url', 'socialmedia','address', 'postalAddress', 'faumap', 'officehours', 'consultationhours', 'text'],
        ],
        'default_fields_byformat'   => [
            'default'       => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'],
            'page'           => [
                'image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor',
                'teasertext', 'content', 'officehours', 'consultationhours'
            ],
            'list'          => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'],
            'compact'       => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'],
            'table'         => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'],
            'card'          => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'],
            'org-compact'   => ['name', 'phone', 'fax', 'email', 'url', 'socialmedia', 'address', 'faumap', 'officehours', 'consultationhours', 'text'],
            'org-default'   => ['name', 'phone', 'fax', 'email', 'url', 'socialmedia', 'address', 'faumap', 'officehours', 'consultationhours', 'text'],
        ],
        
        'default_format'    => 'compact',
        'default_display'   => 'person',
        'avaible_formats_by_display'   => [
            'person'    => ['default', 'compact', 'table', 'list',  'page', 'card'],
            'org'       => ['default', 'compact'],
        ],

        
        'args_person_to_faudir' => [
            "titel"         => 'honorificPrefix',
            "name"          => 'displayname',
            "suffix"        => 'honorificSuffix',
            "workLocation"  => 'city',
            "organisation"  => 'organization',
            "worksFor"      => 'organization',
            "abteilung"     => 'organization',
            "telefon"       => 'phone',
            "mail"          => 'email',
            "webseite"      => 'url',
            "sprechzeiten"  => 'consultationhours',
            "adresse"       => 'address',
            "bild"          => 'image',
            "permalink"     => 'link',
            "description"   => 'content',
            "department"    => 'organization',
            "kurzauszug"    => 'teasertext',
            "raum"          => 'room',
            "position"      => 'jobTitle',
        ],
        'jobtitle_format'   => '#functionlabel#',
        'person_taxonomy'   => 'custom_taxonomy',
            // TODO: Change to a non generic name!
        'person_post_type'  => 'custom_person',
            // TODO: Change to a non generic name!
        'fau-person_post_type'  => 'person',
            // Post Type von FAU Person, hierin wird geschaut zum importieren
        'hide_on_parameter' => [
            'address' => [
                 // If the show-params contains address the following fields will be hidden
                'street', 'zip', 'city'
            ],
            'displayname'   => [
                 // If the show-params contains displayname the following fields will be hidden
                'honorificPrefix','honorificSuffix', 'givenName', 'titleOfNobility', 'familyName'
            ]
        ],

        'overwriteable_as_option' => [
            'version',
            'api_key',
            'cache_timeout',
            'show_error_message',
            'fallback_link_faudir',
            'jobtitle_format',
            'default_normalize_honorificPrefix',
            'redirect_to_canonicals',
            'default_visible_copyrightmeta',
            'default_visible_bildunterschrift',
            'default_placeholder_image_with_signature',
            'enable_history',
            'person_slug',
            'redirect_archivpage_uri',
            'show_output_fields_person_default',
            'show_output_fields_person_page',
            'show_output_fields_org_default',
            'default_organization',
            'button_link_title'
        ]

    ];
    


    public function __construct() {
        $this->config['avaible_fields'] = [
            'image'             => __('Image', 'rrze-faudir'),
            'displayname'       => __('Display Name', 'rrze-faudir'),
            'honorificPrefix'   => __('Academic Title', 'rrze-faudir'),
            'honorificSuffix'   => __('Academic Suffix', 'rrze-faudir'),
            'givenName'         => __('First Name', 'rrze-faudir'),
            'titleOfNobility'   => __('Title of Nobility', 'rrze-faudir'),
            'familyName'        => __('Family Name', 'rrze-faudir'),
            'email'             => __('Email', 'rrze-faudir'),
            'phone'             => __('Phone', 'rrze-faudir'),
            'fax'               => __('Fax', 'rrze-faudir'),
            'organization'      => __('Organization', 'rrze-faudir'),
            'jobTitle'          => __('Jobtitle', 'rrze-faudir'),
            'url'               => __('URL', 'rrze-faudir'),
            'content'           => __('Content', 'rrze-faudir'),
            'teasertext'        => __('Teasertext', 'rrze-faudir'),
            'socialmedia'       => __('Social Media and Websites', 'rrze-faudir'),
            'room'              => __('Room', 'rrze-faudir'),
            'floor'             => __('Floor', 'rrze-faudir'),
            'address'           => __('Address', 'rrze-faudir'),
            'street'            => __('Street', 'rrze-faudir'),
            'zip'               => __('ZIP Code', 'rrze-faudir'),
            'city'              => __('City', 'rrze-faudir'),
            'faumap'            => __('FAU Map', 'rrze-faudir'),
            'officehours'       => __('Office Hours', 'rrze-faudir'),
            'consultationhours' => __('Consultation Hours', 'rrze-faudir'),
            'link'              => __('Link to Profil', 'rrze-faudir'),
            'alternateName'     => __('Alternate Name', 'rrze-faudir'),
            'text'              => __('Text', 'rrze-faudir'),
            'postalAddress'     => __('Postal Address', 'rrze-faudir'),
 //           'name'              => __('Name', 'rrze-faudir'),
        ];


        $this->config['avaible_fields_org'] = [
            'name'              => __('Name', 'rrze-faudir'),
            'alternateName'     => __('Alternate Name', 'rrze-faudir'),
            'disambiguatingDescription'     => __('Disambiguating Description', 'rrze-faudir'),
            'longDescription'   => __('Description', 'rrze-faudir'),

            // as part of address array:
            'email'             => __('Email', 'rrze-faudir'),
            'phone'             => __('Phone', 'rrze-faudir'),
            'fax'               => __('Fax', 'rrze-faudir'),
            'faumap'            => __('FAU Map', 'rrze-faudir'),
            'url'               => __('URL', 'rrze-faudir'),

            // in content:
            'text'              => __('Text', 'rrze-faudir'),
            'socialmedia'       => __('Social Media and Websites', 'rrze-faudir'),

            'address'           => __('Address', 'rrze-faudir'),
            'postalAddress'     => __('Postal Address', 'rrze-faudir'),
            'internalAddress'   => __('Internal Address', 'rrze-faudir'),

            'officehours'       => __('Office Hours', 'rrze-faudir'),
            'consultationhours' => __('Consultation Hours', 'rrze-faudir'),

        ];


        $this->config['person_roles'] = [
            'administrative_employee'   => __('Administrative Employee', 'rrze-faudir'),
            'adjunct_professor'         => __('Adjunct professors', 'rrze-faudir'),
            'assistentprofessor'        => __('Privat dozent', 'rrze-faudir'),
            'campo_administrator'       => __('Campo administrator', 'rrze-faudir'),
            'deputy'                    => __('Deputy leader', 'rrze-faudir'),
            'doctoratecandidate_without_contract'   => __('Doctoral candidate', 'rrze-faudir'),
            'employee'                  => __('Employee', 'rrze-faudir'),
            'guestlecturer'             => __('Visiting lecturers', 'rrze-faudir'),
            'guestresearcher'           => __('Visiting researchers', 'rrze-faudir'),
            'honorary_professor'        => __('Honorary professors', 'rrze-faudir'),
            'idm_coordinator'           => __('IdM coordinator', 'rrze-faudir'),
            'it_support_staff'          => __('IT support staff', 'rrze-faudir'),
            'junior_professor'          => __('Assistant professors', 'rrze-faudir'),
            'leader'                    => __('Head', 'rrze-faudir'),
            'professor'                 => __('Professors', 'rrze-faudir'),
            'researchassistant'         => __('Research assistant', 'rrze-faudir'),
            'retired_professor'         => __('Retired professors', 'rrze-faudir'),
            'scholarshipholder'         => __('Scholarship holder', 'rrze-faudir'),
            'scientific_employee'       => __('Research associates', 'rrze-faudir'),
            'secretary'                 => __('Secretary', 'rrze-faudir'),
            'studentemployee'           => __('Student employee', 'rrze-faudir'),
            'technical_employee'        => __('Technical employee', 'rrze-faudir'),
            'trainee'                   => __('Trainee', 'rrze-faudir'),
            'visitinglecturer'          => __('Visiting lecturers', 'rrze-faudir'),
            'workplace_manager'         => __('Workplace manager', 'rrze-faudir'),
        ];

        $this->config['formatnames'] = [
            'list'      => __( 'List', 'rrze-faudir' ),
            'table'     => __( 'Table', 'rrze-faudir' ),
            'card'      => __( 'Card', 'rrze-faudir' ),
            'compact'   => __( 'Compact', 'rrze-faudir' ),
            'page'      => __( 'Page', 'rrze-faudir' ),
            'org-compact'   => __( 'Compact', 'rrze-faudir' ),
        ];

    }



    /**
     * Gibt die konfigurierten akademischen Titel inkl. Label, Sortierung und Aliases zurück.
     * Optionale Eingaben: keine.
     * Rückgabe: array [ '<kanonischer Key>' => ['label'=>string,'sortorder'=>int,'aliases'=>string[]] ].
     */
    public function getAcademicPrefixes(): array {
        return [
            // --- Basisvarianten ---
            'Prof. Dr.' => [
                'label'     => __('Professor Doctor', 'rrze-faudir'),
                'sortorder' => 10,
                'aliases'   => [
                    'prof dr', 'professor dr', 'professor doktor', 'prof. dr', 'prof.dr.',
                    'prof. dr.', 'professor doktorin', // tolerant
                ],
            ],
            'Prof. Dr. em.' => [
                'label'     => __('Professor Doctor (Emeritus)', 'rrze-faudir'),
                'sortorder' => 11,
                'aliases'   => [
                    'prof dr em', 'professor dr em', 'prof. dr. em', 'professor emeritus dr',
                    'professorin emerita dr',
                ],
            ],
            'Prof.' => [
                'label'     => __('Professor', 'rrze-faudir'),
                'sortorder' => 20,
                'aliases'   => ['prof', 'professor', 'professorin', 'prof.'],
            ],
            'Prof. em.' => [
                'label'     => __('Professor (Emeritus)', 'rrze-faudir'),
                'sortorder' => 21,
                'aliases'   => ['prof em', 'prof. em', 'professor emeritus', 'professorin emerita'],
            ],
            'Dr.' => [
                'label'     => __('Doctor', 'rrze-faudir'),
                'sortorder' => 30,
                'aliases'   => ['dr', 'doktor', 'doktorin', 'dr.'],
            ],
            'PD Dr.' => [
                'label'     => __('Doctor Private lecturer', 'rrze-faudir'),
                'sortorder' => 25,
                'aliases'   => [
                    'dr pd', 'priv.-doz. dr', 'priv dozent dr', 'privatdozent dr', 'privatdozentin dr',
                    'privdoz dr', 'priv dozentin dr',
                ],
            ],
            'PD' => [
                'label'     => __('Private lecturer', 'rrze-faudir'),
                'sortorder' => 26,
                'aliases'   => ['pd', 'priv.-doz.', 'priv dozent', 'privatdozent', 'privatdozentin', 'privdoz'],
            ],

            // --- "mult."-Varianten (immer VOR Basis einsortieren) ---
            'Prof. mult. Dr.' => [
                'label'     => __('Professor Doctor (multiple)', 'rrze-faudir'),
                'sortorder' => 9,
                'aliases'   => ['prof mult dr', 'prof. mult. dr.', 'professor mult dr'],
            ],
            'Prof. mult.' => [
                'label'     => __('Professor (multiple)', 'rrze-faudir'),
                'sortorder' => 19,
                'aliases'   => ['prof mult', 'prof. mult.', 'professor mult'],
            ],
            'Dr. mult.' => [
                'label'     => __('Doctor (multiple)', 'rrze-faudir'),
                'sortorder' => 29,
                'aliases'   => ['dr mult', 'dr. mult.', 'doktor mult'],
            ],
            // Optional: Kombi mit emeritiertem Status
            'Prof. mult. Dr. em.' => [
                'label'     => __('Professor Doctor (Emeritus, multiple)', 'rrze-faudir'),
                'sortorder' => 8,
                'aliases'   => ['prof mult dr em', 'prof. mult. dr. em.'],
            ],
        ];
    }

    /**
     * Liefert die Liste von Zusätzen, die in akademischen Titeln ignoriert/entfernt werden sollen.
     * Optionale Eingaben: keine.
     * Rückgabe: array<string>.
     */
    public function getAcademicIgnoreTokens(): array {
        // Liste der Tokens und Strings, die oftmals in den akademischen Titeln vorkommen, 
        // jedoch kein offizieller Teil davon sind. In Fall von MA sind diese sogar falsch, da sie in den Namenszusatz gehören und kein Titel sind

        $tokens = [
            'univ',
            'univ.',   // mit Punkt-Variante
            'dipl.',
            'dipl',
            'ing.',
            'm.a.',
            'msc.',
            'm.sc.'

        ];

        /**
         * Filter erlaubt Anpassungen durch Themes/Plugins:
         * add_filter('faudir_academic_ignore_tokens', function(array $tokens){ ...; return $tokens; });
         */
        return apply_filters('faudir_academic_ignore_tokens', $tokens);
    }


    /**
     * Abrufen eines Konfigurationswertes
     *
     * @param string $key Der Schlüssel des Konfigurationswertes
     * @return mixed|null Der Wert oder null, wenn der Schlüssel nicht existiert
     */
    public function get(string $key): mixed {
        if (in_array($key, $this->getOverwriteableOptionKeys(), true)) {
            $options = $this->getRawOptions();

            if (array_key_exists($key, $options)) {
                return $options[$key];
            }
        }

        return $this->config[$key] ?? null;
    }
    
    /*
     * Get Options Version
     */
    public function getConfigVersion(): int {
        return (int) ($this->config['version'] ?? 0);
    }

    /*
     * Setzen eines Keys
     * @param string $key Der Schlüssel des Konfigurationswertes und $value als Wert
     * @return $value|false Der Wert oder false, wenn der Schlüssel nicht existiert* 
     */
    public function set(string $key, mixed $value): mixed {
        if (!empty($key)) {
            $this->config[$key] = $value;
            return $value;
        }
        return false;
    }


    /**
     * Alle Konfigurationswerte abrufen
     */
    public function getAll(): array {
        return $this->config;
    }
    
    /*
     * Daten aus der DB lesen
     */
    public function getRawOptions(): array {
        $options = get_option($this->optionName, []);

        return is_array($options) ? $options : [];
    }

    /*
     * Get Array of those options with config content, that are overwiteable
     * We use these values as default values for the first init
     */
    public function getOverwriteableOptionKeys(): array {
        $keys = $this->config['overwriteable_as_option'] ?? [];
        return is_array($keys) ? array_values($keys) : [];
    }
    
    public function getOverwriteableOptionDefaults(): array {
        $res = [];

        foreach ($this->getOverwriteableOptionKeys() as $key) {
            if (array_key_exists($key, $this->config)) {
                $res[$key] = $this->config[$key];
            }
        }

        $res['show_output_fields_person_default'] = $this->getDefaultFieldlistByFormat('default', 'person');
        $res['show_output_fields_org_default']    = $this->getDefaultFieldlistByFormat('default', 'org');
        $res['show_output_fields_person_page']    = $this->getDefaultFieldlistByFormat('page', 'person');

        return $res;
    }
    
    public function filterAllowedOptions(array $options): array {
        $allowed = array_flip($this->getOverwriteableOptionKeys());

        return array_intersect_key($options, $allowed);
    }
    
    /*
     * Get allowed fields for format
     */
    public function getFieldsByFormat(string $format = '', ?string $display = ''): array {
        if ((!empty($display)) && ($display === 'org')) {
            $format = 'org-'.$format;
        }


        if ((empty($format)) || (!isset($this->config['avaible_fields_byformat'][$format]))) {
            return $this->config['avaible_fields'];
        }

        $fieldlist = $this->config['avaible_fields_byformat'][$format];
        $all = $this->config['avaible_fields'];
        $res = [];

        foreach ($fieldlist as $num => $val) {
            if ((!empty($val)) && (isset($all[$val]))) {
                $res[$val] = $all[$val];
            }
        }
        return $res;

    }
    public function getAvaibleFieldlist(): array {
        return $this->config['avaible_fields_byformat'];
    }


    /* 
     * Prüfe und normalisiere format
     */
    public function normalizeFormatForDisplay(string $format = '', string $display = 'person'): string {
        $display = trim(strtolower($display));
        $format = trim(strtolower($format));

        $available = $this->config['avaible_formats_by_display'] ?? [];
        if (!isset($available[$display]) || !is_array($available[$display]) || empty($available[$display])) {
            return 'default';
        }

        $allowedFormats = array_values(array_filter(
            $available[$display],
            static function ($value): bool {
                return is_string($value) && $value !== '';
            }
        ));

        if ($format === '' || !in_array($format, $allowedFormats, true)) {
            return in_array('default', $allowedFormats, true) ? 'default' : (string) $allowedFormats[0];
        }

        return $format;
    }
    
    /*
     * Prüfe und Normalisiere display
     */
    public function normalizeDisplay(string $display = 'person'): string {
        $display = trim(strtolower($display));

        $available = $this->config['avaible_formats_by_display'] ?? [];
        if (isset($available[$display]) && is_array($available[$display])) {
            return $display;
        }

        return 'person';
    }
    
    /*
     * Get complete list of avaible output fields by format
     */
    public function getAvaibleFieldlistByFormat(string $format = '', ?string $display = 'person'): array {
        $display = $this->normalizeDisplay((string) $display);
        $format = $this->normalizeFormatForDisplay($format, $display);

        $configKey = $display === 'org' && $format !== 'default'
            ? 'org-' . $format
            : ($display === 'org' ? 'org-compact' : $format);

        if (isset($this->config['avaible_fields_byformat'][$configKey])) {
            return $this->config['avaible_fields_byformat'][$configKey];
        }

        if ($display === 'org') {
            return $this->config['avaible_fields_byformat']['org-compact'] ?? [];
        }

        return $this->config['avaible_fields_byformat']['compact'] ?? [];
    }
    
    
    /*
     * Get Default Output Fields by Format
     */
    public function getDefaultFieldlistByFormat(string $format = '', ?string $display = ''): array {
        $display = $this->normalizeDisplay((string) $display);
        $format = $this->normalizeFormatForDisplay($format, $display);

        $optionKey = null;
        $configKey = $format;

        if ($display === 'org') {
            if ($format === 'default') {
                $optionKey = 'show_output_fields_org_default';
                $configKey = 'org-default';
            } else {
                $configKey = 'org-' . $format;
            }
        } else {
            if ($format === 'default') {
                $optionKey = 'show_output_fields_person_default';
                $configKey = 'default';
            } elseif ($format === 'page') {
                $optionKey = 'show_output_fields_person_page';
                $configKey = 'page';
            }
        }

        if ($optionKey !== null) {
            $stored = $this->get($optionKey);
            if (is_array($stored) && !empty($stored)) {
                return $stored;
            }
        }

        if (isset($this->config['default_fields_byformat'][$configKey])) {
            return $this->config['default_fields_byformat'][$configKey];
        }

        if ($display === 'org') {
            return $this->config['default_fields_byformat']['org-default'] ?? [];
        }

        return $this->config['default_fields_byformat']['default'] ?? [];
    }
    
    
    

    /*
     * Get Site Option and merge with CONFIG if needed
     */
    public function getOptions(): array {
        $options = wp_parse_args($this->getRawOptions(), $this->getAll());

        if (!isset($options['show_output_fields_person_default'])) {
            $options['show_output_fields_person_default'] = $this->getDefaultFieldlistByFormat('default', 'person');
        }

        if (!isset($options['show_output_fields_person_page'])) {
            $options['show_output_fields_person_page'] = $this->getDefaultFieldlistByFormat('page', 'person');
        }

        if (!isset($options['show_output_fields_org_default'])) {
            $options['show_output_fields_org_default'] = $this->getDefaultFieldlistByFormat('default', 'org');
        }

        return $options;
    }



    /*
     * Alle Options speichern, die wir in der DB speichern wollen und ggf. die Option Version speichern
     */
    public function saveOptions(array $options): bool {
        $filtered = $this->filterAllowedOptions($options);

        if (!array_key_exists('version', $filtered)) {
            $raw = $this->getRawOptions();
            if (isset($raw['version'])) {
                $filtered['version'] = (int) $raw['version'];
            } else {
                $filtered['version'] = $this->getConfigVersion();
            }
        }

        return update_option($this->optionName, $filtered);
    }
    
    /*
     * Einzelne Option setzen
     */
    public function setOption(string $key, mixed $value): bool {
        $options = $this->getRawOptions();

        if (!in_array($key, $this->getOverwriteableOptionKeys(), true)) {
            return false;
        }

        $options[$key] = $value;

        if (!isset($options['version'])) {
            $options['version'] = $this->getConfigVersion();
        }

        return $this->saveOptions($options);
    }
    
    /*
     * Einzelne Option löschen
     */
    public function deleteOption(string $key): bool {
        $options = $this->getRawOptions();

        if (array_key_exists($key, $options)) {
            unset($options[$key]);
            return $this->saveOptions($options);
        }

        return true;
    }
    
    /*
     * Versionsprüfung und Cleanup bei neuen Config Versionen
     */
    public function maybeMigrateStoredOptions(): bool {
        $raw = $this->getRawOptions();
        $currentVersion = $this->getConfigVersion();
        $storedVersion = isset($raw['version']) ? (int) $raw['version'] : 0;

        /*
         * Alte Keys umwandeln, falls diese vorher in Benutzung waren
         * (Kann ab V2.7 und später wieder raus, brauchen wir nur vom Schritt 2.5 auf 2.6)
         */
        if (isset($raw['default_output_fields']) && !isset($raw['show_output_fields_person_default'])) {
            $raw['show_output_fields_person_default'] = $raw['default_output_fields'];
        }

        if (isset($raw['default_org_output_fields']) && !isset($raw['show_output_fields_org_default'])) {
            $raw['show_output_fields_org_default'] = $raw['default_org_output_fields'];
        }

        if (isset($raw['output_fields_endpoint']) && !isset($raw['show_output_fields_person_page'])) {
            $raw['show_output_fields_person_page'] = $raw['output_fields_endpoint'];
        }  
        
        if (isset($raw['business_card_title']) && !isset($raw['button_link_title'])) {
            $raw['button_link_title'] = $raw['business_card_title'];
        }  
        
        if ($storedVersion === 0) {
            $cleaned = $this->filterAllowedOptions($raw);
            $cleaned['version'] = $currentVersion;
            return update_option($this->optionName, $cleaned);
        }

        if ($storedVersion >= $currentVersion) {
            return false;
        }

        $cleaned = $this->filterAllowedOptions($raw);
        $cleaned['version'] = $currentVersion;

        return update_option($this->optionName, $cleaned);
    }
}

