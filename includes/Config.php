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
        'default_output_fields'     => ['academic_title', 'first_name', 'last_name', 'email'], // Default fields
    ];

    public function __construct() {
         $this->config['business_card_title'] = __('Call up business card', 'rrze-faudir');
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

