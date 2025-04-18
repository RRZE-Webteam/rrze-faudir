<?php


namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Config {
    private array $config = [
        'api_key'                   => '',
        'api-baseurl'               => 'https://api.fau.de/pub/v1/opendir/',
        'faudir-url'                => 'https://faudir.fau.de/',
        'no_cache_logged_in'        => false,
        'cache_timeout'             => 120, // Minimum 15 minutes
        'transient_time_for_org_id' => 1, // Minimum 1 day
        'show_error_message'        => false,
        'business_card_title'       => '',
        'hard_sanitize'             => false,
        'fallback_link_faudir'      => true,
        'default_display_order'     => [
            'table' => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'link'],
            'list'  => ['displayname', 'jobTitle', 'url', 'email', 'socialmedia', 'roompos', 'room', 'floor', 'address','faumap', 'link']
        ],
        'avaible_fields_byformat'   => [
            'table'         => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link', 'format_displayname'],
            'list'          => ['displayname', 'jobTitle', 'url', 'email', 'socialmedia', 'roompos', 'room', 'floor', 'zip', 'street', 'city', 'faumap', 'link', 'format_displayname'],
            'compact'       => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link', 'format_displayname'],
            'page'          => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'content', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'format_displayname'],
            'card'          => ['image', 'displayname', 'jobTitle', 'organization', 'url', 'email', 'socialmedia', 'link', 'format_displayname'],
            'org-compact'   => ['phone', 'email', 'url', 'socialmedia', 'organization','address', 'faumap', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'content'],
        ],
        'default_display'   => 'person',
        'avaible_formats_by_display'   => [
            'person'    => ['compact', 'table', 'list',  'page', 'card'],               
            'org'       => ['compact'],
             // in all cases: first entry is default for the given display
        ],
        
        'default_output_fields'     => ['image', 'displayname', 'jobTitle', 'email', 'phone', 'socialmedia'], // Default fields      
        'default_output_fields_endpoint' => [
            'image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor',  
            'teasertext', 'content', 'zip', 'street', 'city', 'officehours', 'consultationhours'
        ],        
        'args_person_to_faudir' => [
            "titel"         => 'honorificPrefix',
            "name"          => 'displayname',
            "suffix"        => 'honorificSuffix',
            "workLocation"  => 'city',
            "organisation"  => 'organization',
            "worksFor"      => 'organization',
            "abteilung"      => 'organization',
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
        
        'person_taxonomy'   => 'custom_taxonomy',
            // TODO: CHange to a non generic name!
        'person_post_type'  => 'custom_person'
            // TODO: CHange to a non generic name!
    ];
    

    public function __construct() {
        $this->config['business_card_title'] = __('Call up business card', 'rrze-faudir');         
        $this->config['avaible_fields'] = [
            'image'             => __('Image', 'rrze-faudir'),
            'displayname'       => __('Display Name', 'rrze-faudir'),
                // Einzelne Namensbestandteile werden später im Shortcode
                // durch Änderung der Zusammensetzung von Displayname generiert
            'email'             => __('Email', 'rrze-faudir'),
            'phone'             => __('Phone', 'rrze-faudir'),
            'organization'      => __('Organization', 'rrze-faudir'),
            'jobTitle'          => __('Job Title', 'rrze-faudir'),
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
            'link'              => __('Link to Profil', 'rrze-faudir')
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
            
        ];

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
     * Get allowed fields for format
     */
    public function getFieldsByFormat(string $format = ''): array { 
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

