<?php


namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Config {
    private array $config = [
        'api_key'                   => '',
        'api-baseurl'               => 'https://api.fau.de/pub/v1/opendir/',
        'no_cache_logged_in'        => false,
        'cache_timeout'             => 15, // Minimum 15 minutes
        'transient_time_for_org_id' => 1, // Minimum 1 day
        'show_error_message'        => false,
        'business_card_title'       => '',
        'hard_sanitize'             => false,
        'default_output_fields'     => ['displayname', 'jobTitle', 'email', 'phone', 'url'], // Default fields      
        'default_display_order'     => [
            'table' => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'link'],
            'list'  => ['displayname', 'jobTitle', 'url', 'email', 'socialmedia', 'roompos', 'room', 'floor', 'address','faumap', 'link']
        ],
        'avaible_fields_byformat'   => [
            'table' => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link'],
            'list'  => ['displayname', 'jobTitle', 'url', 'email', 'socialmedia', 'roompos', 'room', 'floor', 'zip', 'street', 'city', 'faumap', 'link'],
            'compact' => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'zip', 'street', 'city', 'officehours', 'consultationhours', 'link'],
            'page' => ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext', 'content', 'zip', 'street', 'city', 'officehours', 'consultationhours'],
            'card'  => ['image', 'displayname', 'jobTitle', 'organization', 'url', 'email', 'socialmedia'],

        ]
        
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
    
    
    
}

