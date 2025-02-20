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
        
    ];
    
    

    
    

    public function __construct() {
        $this->config['business_card_title'] = __('Call up business card', 'rrze-faudir');
             
        $this->config['avaible_fields'] = [
            'image'             => __('Image', 'rrze-faudir'),
            'displayname'      => __('Display Name', 'rrze-faudir'),
            'honorificPrefix'    => __('Academic Title', 'rrze-faudir'),
            'givenName'        => __('First Name', 'rrze-faudir'),
            'nobility_title'    => __('Nobility Title', 'rrze-faudir'),
            'familyName'         => __('Last Name', 'rrze-faudir'),
            'honorificSuffix'   => __('Academic Suffix', 'rrze-faudir'),
            'email'             => __('Email', 'rrze-faudir'),
            'phone'             => __('Phone', 'rrze-faudir'),
            'organization'      => __('Organization', 'rrze-faudir'),
            'jobTitle'          => __('Job Title', 'rrze-faudir'),
            'url'               => __('URL', 'rrze-faudir'),
            'content'           => __('Content', 'rrze-faudir'),
            'teasertext'        => __('Teasertext', 'rrze-faudir'),
            'socialmedia'       => __('Social Media and Websites', 'rrze-faudir'),
            'workplaces'        => __('Workplaces', 'rrze-faudir'),
            'room'              => __('Room', 'rrze-faudir'),
            'floor'             => __('Floor', 'rrze-faudir'),
            'address'           => __('Address', 'rrze-faudir'),
            'street'            => __('Street', 'rrze-faudir'),
            'zip'               => __('ZIP Code', 'rrze-faudir'),
            'city'              => __('City', 'rrze-faudir'),
            'faumap'            => __('FAU Map', 'rrze-faudir'),
            'officehours'       => __('Office Hours', 'rrze-faudir'),
            'consultationhours' => __('Consultation Hours', 'rrze-faudir'),
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
}

