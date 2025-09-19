<?php


namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Config {
    private array $config = [
        'version'                           => 7,  // please count this up any time we change the config array
        'api_key'                           => '',
        'api-baseurl'                       => 'https://api.fau.de/pub/v1/opendir/',
        'faudir-url'                        => 'https://faudir.fau.de/',
        'no_cache_logged_in'                => true,
        'cache_timeout'                     => 120, // Minimum 15 minutes
        'transient_time_for_org_id'         => 1, // Minimum 1 day
        'show_error_message'                => false,
        'business_card_title'               => '',
        'fallback_link_faudir'              => true,
        'default_normalize_honorificPrefix' => false,
        'default_redirect_to_canonicals'    => false,
        'default_visible_copyrightmeta'     => false,
        'default_visible_bildunterschrift'  => false,
        'default_display_order'     => [
            'table' => ['image', 'displayname', 'familyName', 'givenName', 'jobTitle', 'organization', 'phone', 'email', 'url', 'socialmedia','address', 'room', 'floor', 'faumap', 'teasertext', 'link'],
            'list'  => ['displayname', 'familyName', 'givenName', 'jobTitle', 'url', 'email', 'socialmedia', 'room', 'floor', 'address','faumap', 'link']
        ],
        'avaible_fields_byformat'   => [
            'table'         => ['image', 'displayname','honorificPrefix','honorificSuffix', 'givenName',  'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link', 'format_displayname'],
            'list'          => ['displayname', 'honorificPrefix','honorificSuffix', 'givenName', 'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax',  'url', 'email', 'socialmedia', 'organization','address', 'room', 'floor', 'city', 'faumap', 'link', 'format_displayname'],
            'compact'       => ['image', 'displayname', 'honorificPrefix','honorificSuffix', 'givenName', 'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link', 'format_displayname'],
            'page'          => ['image', 'displayname', 'jobTitle', 'phone', 'fax', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'content', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'format_displayname'],
            'card'          => ['image', 'displayname','honorificPrefix','honorificSuffix', 'givenName',  'titleOfNobility', 'familyName', 'jobTitle', 'phone', 'fax', 'organization', 'url', 'email', 'socialmedia', 'link', 'format_displayname'],
            'org-compact'   => [ 'name', 'alternateName', 'phone', 'fax', 'email', 'url', 'socialmedia','address', 'postalAddress', 'faumap', 'officehours', 'consultationhours', 'text'],
        ],
        'default_format'    => 'compact',
        'default_display'   => 'person',
        'avaible_formats_by_display'   => [
            'person'    => ['compact', 'table', 'list',  'page', 'card'],               
            'org'       => ['compact'],
             // in all cases: first entry is default for the given display
        ],
        
        'default_output_fields'     => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'], // Default fields      
        'default_org_output_fields' => ['name', 'phone', 'fax', 'email', 'url', 'socialmedia', 'address', 'faumap', 'officehours', 'consultationhours', 'longDescription'],
        'default_output_fields_endpoint' => [
            'image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization', 'address', 'room', 'floor',  
            'teasertext', 'content', 'officehours', 'consultationhours'
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
            'show_error_message',
            'cache_timeout',
            'no_cache_logged_in',
            'transient_time_for_org_id',
            'fallback_link_faudir',
            'jobtitle_format',
            'default_normalize_honorificPrefix'
        ]

    ];
    
  
    public function __construct() {
        $this->config['business_card_title'] = __('To the profile', 'rrze-faudir');         
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
        return $this->config[$key] ?? null;
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
     * Get Array of those options with config content, that are overwiteable
     * We use these values as default values for the first init
     */
    public function getOverwiteableOptions(): array {
        $overoptions = $this->config['overwriteable_as_option'];
        $res = [];
        foreach ($overoptions as $key) {
            $res[$key] = $this->config[$key];
        }
        return $res;
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
    
    
    public function getAvaibleFieldlistByFormat(string $format = '', ?string $display = ''): array {       
        if (empty($format)) {
            $format = $this->config['default_format'];
        }
        if ((!empty($display)) && ($display === 'org')) {
            $format = 'org-'.$format;
        }
        
        return $this->config['avaible_fields_byformat'][$format];
  
    }
    
    
    /*
     * Get Site Option and merge with CONFIG if needed
     */
    public function getOptions(): array {          
        $default_settings = $this->getAll();
        $options = get_option('rrze_faudir_options', []);
        $settings = wp_parse_args($options, $default_settings);
        
        return $settings;
    }
    
 
    public function insertOptions(): bool {
        $options = get_option('rrze_faudir_options', []);
        $found = false; 
         foreach ($options as $key => $value) {
             if (isset($this->config[$key]) && ($this->config[$key] !== $value)) {
                 $this->config[$key] = $value;
                 $found = true;
             }
         }
         return $found;
    }
    
}

