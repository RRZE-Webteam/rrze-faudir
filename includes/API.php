<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class API {
    private string $baseUrl = 'https://api.fau.de/pub/v1/opendir/';
    private string $api_key;
    private string $transient_prefix = 'faudir_api_';
    private int $transient_jitter_minutes = 5;
    private array $transient_times = [
        "persons"       => 120,
        "contacts"      => 120,
        "organizations" => 240,
        "default"       => 150,
    ];
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        if (!empty($this->config->get('api-baseurl'))) {
            $this->baseUrl = $this->config->get('api-baseurl');
        }
        $this->api_key = $this->getKey();
    }
    
   
    public static function isUsingNetworkKey(): bool  {
        if (is_multisite()) {
            $settingsOptions = get_site_option('rrze_settings');
            if (!empty($settingsOptions->plugins->faudir_public_apiKey)) {
                return true;
            }
        }
        return false;
    }

    public static function getKey()  {
        if (self::isUsingNetworkKey()) {
            $settingsOptions = get_site_option('rrze_settings');
            return $settingsOptions->plugins->faudir_public_apiKey;
        } else {
            $options = get_option('rrze_faudir_options');
            return isset($options['api_key']) ? $options['api_key'] : '';
        }
    }

    public function getApiBaseUrl() {
        return $this->baseUrl;
    }
    
    
    /**
     * Get a person
     * @param personid
     * @return array|null on not found
     */
    public function getPerson(string $personId): ?array {
        if (!$this->api_key) {
            error_log('RRZE FAUdir\API::getPerson: API Key missing.');
            return null;
        }
        if (empty($personId)) {
            error_log('RRZE FAUdir\API::getPerson: Required field personid missing.');
            return null;
        }
        $url = "{$this->baseUrl}persons/{$personId}";
        
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            error_log("FAUdir\API (getPerson): No response from server on {$url}.");
            return null;
        }
        return $response;
    }
    
    
     /**
     * Get a person
     * @param personid
     * @return array|null on not found
     */
    public function getContact(string $contactId): ?array {
        if (!$this->api_key) {       
            error_log('RRZE FAUdir\API::getContact: API Key missing.');
            return null;
        }
        if (empty($contactId)) {
            error_log('RRZE FAUdir\API::getContact: Required field contactId missing.');
            return null;
        }
        $url = "{$this->baseUrl}contacts/{$contactId}";
        
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            error_log("FAUdir\API (getContact): No response from server on {$url}.");
            return null;
        }

        return $response;
    }
    
   
  
    
    
    /*
     * Get persons
     * @param int $limit - Limit the number of contacts to fetch
     * @param int $offset - Offset the number of contacts to fetch
     * @param array $params - Additional query parameters
     * @return array - Array of persons 
     */
     public function getPersons($limit = 60, $offset = 0, $params = [], bool $retry = true): ?array {
        if (!$this->api_key) {
             error_log('RRZE FAUdir\API::getPersons: API Key missing.');
             return null;
        }
        if (empty($params)) {
            error_log("FAUdir\API (getPersons): Required params missing.");
            return null;
        }
        $url = "{$this->baseUrl}persons";
    
        $url .= '?limit=' . $limit . '&offset=' . $offset;
        $param_uri = '';
        
         // Define allowed query parameters and map them to their corresponding keys
        $query_params = [
            'q',
            'sort',
            'attrs',
            'lq',
            'rq',
            'view',
            'lf'
        ];

         if (!isset($params['q'])) {
             if (!empty($params['email'])) {
                 $params['q'] = '^' . $params['email'];
             } elseif (!empty($params['identifier'])) {
                 $params['q'] = '^' . $params['identifier'];
             } elseif (!empty($params['givenName']) || !empty($params['familyName'])) {
                 $params['q'] = '^' . trim(($params['givenName'] ?? '')
                         . ' ' . ($params['familyName'] ?? ''));
             }
         }

         // Loop through the parameters and append them to the URL if they exist in $params
        foreach ($query_params as $param) {
            if (!empty($params[$param])) {
                $param_uri .= '&' . $param . '=' . $this->encodeParam($param, $params[$param]);
            }
        }

        if (!empty($param_uri)) {
            $url .= $param_uri;
            $response = $this->makeRequest($url, "GET");

            if ($retry && (empty($response) || empty($response['data']))) {

                // Mail & ID empty, retry with Name and First Name
                if (!empty($params['givenName']) || !empty($params['familyName'])) {
                    unset($params['q']);
                    $params['q'] = '^' . trim(($params['givenName'] ?? '')
                            . ' ' . ($params['familyName'] ?? ''));
                    return $this->getPersons($limit, $offset, $params, false);
                }

                // Still no match, retry with workplace mail
                if (!empty($params['email'])) {
                    unset($params['q']);
                    $params['q'] = $params['email'];
                    return $this->getPersons($limit, $offset, $params, false);
                }

                // No match found
                return $response;
            }

            if (!$response) {
                error_log("FAUdir\API (getPersons): No response from server on {$url}.");
                return null;
            }
            return $response;
        }
        error_log("FAUdir\API (getPersons): No params to query for.");
        return [];

    }   
   
   
    
    /**
    * Get contacts  
    * @param int $limit - Limit the number of contacts to fetch
    * @param int $offset - Offset the number of contacts to fetch
    * @param array $params - Additional query parameters
    * @return array - Array of contacts
    */
    function getContacts($limit = 20, $offset = 0, $params = []) {
        if (!$this->api_key) {
            error_log('RRZE FAUdir\API::getContacts: API Key missing.');
            return null;
        }
        if (empty($params)) {
            error_log("FAUdir\API (getContacts): Required params missing.");
            return null;
        }
        $url = "{$this->baseUrl}contacts";
        $url .= '?limit=' . $limit . '&offset=' . $offset;
        $param_uri = '';
        
        // Define allowed query parameters and map them to their corresponding keys
        $query_params = [
            'q',
            'sort',
            'attrs',
            'lq',
            'rq',
            'view',
            'lf'
        ];

        if (!isset($params['q'])) {
            if (!empty($params['email'])) {
                $params['q'] = '^' . $params['email'];
            } elseif (!empty($params['identifier'])) {
                $params['q'] = '^' . $params['identifier'];
            } elseif (!empty($params['givenName']) || !empty($params['familyName'])) {
                $params['q'] = '^' . trim(($params['givenName'] ?? '')
                        . ' ' . ($params['familyName'] ?? ''));
            }
        }

        // Loop through the parameters and append them to the URL if they exist in $params
        foreach ($query_params as $param) {
            if (!empty($params[$param])) {
                $param_uri .= '&' . $param . '=' . $this->encodeParam($param, $params[$param]);
            }
        }

        if (!empty($param_uri)) {
            $url .= $param_uri;
            $response = $this->makeRequest($url, "GET");

            if (!$response) {
                error_log("FAUdir\API (getContacts): No response from server on {$url}.");
                return null;
            }

            return $response;
        }
        error_log("FAUdir\API (getContacts): No params to query for.");
        return [];
    }
    
    /**
    * Fetch organizations from the FAU organizations API
    * @param int $limit - Limit the number of organizations to fetch
    * @param int $offset - Offset the number of organizations to fetch
    * @param array $params - Additional query parameters
    * @return array - Array of organizations
    */
    public function getOrgList($limit = 100, $offset = 0, $params = []): ?array {
        if (!$this->api_key) {
            error_log('RRZE FAUdir\API::getOrgList: API Key missing.');
            return null;
        }   
        if (empty($params)) {
            error_log('RRZE FAUdir\API::getOrgList: Required params missing.');
            return null;
        }
        $url = "{$this->baseUrl}organizations";
        $url .= '?limit=' . $limit . '&offset=' . $offset;
        

        $query_params = [
            'q',
            'sort',
            'attrs',
            'lq',
            'rq',
            'view',
            'lf'
        ];
        // Loop through the parameters and append them to the URL if they exist in $params
        foreach ($query_params as $param) {
            if (!empty($params[$param])) {
                $url .= '&' . $param . '=' . $this->encodeParam($param, $params[$param]);
            }
        }
        // Handle orgnr as special cases to be combined into the 'q' parameter
        if (!empty($params['orgnr'])) {
            $url .= '&q=' . urlencode('^' . $params['orgnr']);
        }
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            error_log("FAUdir\API (getOrgList): No valid response from server on {$url}.");
            return null;
        }
        return $response;
  
    }
    
    
     /**
     * Get a Organisation by id
     * @param orgid
     * @return array|null on not found
     */
    public function getOrgById(string $orgid): ?array {
        if (!$this->api_key) {
            error_log('RRZE FAUdir\API::getOrgById: API Key missing.');
            return null;
        }
        if (empty($orgid)) {
            error_log('RRZE FAUdir\API::getOrgById: Required field orgid missing.');
            return null;
        }
        $url = "{$this->baseUrl}organizations/{$orgid}";
        
        
        // TODO: Add cache here, cause this requests will be repeated often 
        // on list of people from the same department
        
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            error_log("FAUdir\API (getOrgById): No response from server on {$url}.");
            return null;
        }

        // Wandelt das Array in ein Profil-Objekt um
        return $response;
    }

    /**
     * Encodes a query parameter without affecting lq's = and & symbols
     */
    private function encodeParam(string $key, string $value): string
    {
        return urlencode($value);
    }
   
    /**
     * Führt eine HTTP-Anfrage aus.
     *
     * @param string $url Die URL für die Anfrage.
     * @param string $method Die HTTP-Methode ("GET" oder "POST").
     * @param array|null $data Optional: Daten für POST-Anfragen.
     * @return array|null Die JSON-Antwort als Array oder null bei Fehlern.
     */
    private function makeRequest(string $url, string $method, ?array $data = null): ?array {
        if (!$this->api_key) {
            error_log('RRZE FAUdir\API::makeRequest: API Key missing.');
            return null;
        }

        if ($method === "GET" && $data) {
            // Daten als URL-Parameter kodieren und an die URL anhängen
            $queryString = http_build_query($data);
            $url .= '?' . $queryString;
        }
               
        // Prüfe, ob gecachte Daten existieren
        $cached = $this->get_cache_data($url);
        if (!is_null($cached)) {
            return $cached;
        }
        
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'accept' => 'application/json',
                'X-API-KEY' => $this->api_key
            ),
        ));

        if (is_wp_error($response)) {
            return array('error' => true, 'message' => 'Error retrieving data: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array('error' => true, 'message' => 'Empty content');
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('error' => true, 'message' => 'Error decoding JSON data');
        }
        
        // Speichere im Cache
        $this->set_cache_data($url, $data);

        return $data;
        
        
    }
 
    // Take a look for avaible transient data
    private function get_cache_data(string $url): ?array {
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'] ?? '';

        // Entferne die baseURL aus dem Pfad
        $relative_path = str_replace(parse_url($this->baseUrl, PHP_URL_PATH), '', $path);
        $parts = explode('/', trim($relative_path, '/'));

        $endpoint = $parts[0] ?? 'default';

        // 2. Transient-Lifetime in Sekunden (Fallback: default)
        $minutes = $this->transient_times[$endpoint] ?? $this->transient_times['default'];
        $lifetime = $minutes * 60;

        $transient_key = $this->transient_prefix . $endpoint . '_' . md5($url);

        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached;
        }

        return null;    
    }
    
    // Save data as transient
    private function set_cache_data(string $url, array $data): void {
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'] ?? '';

        // Entferne die baseURL aus dem Pfad
        $relative_path = str_replace(parse_url($this->baseUrl, PHP_URL_PATH), '', $path);
        $parts = explode('/', trim($relative_path, '/'));
        
        // Der Endpoint ist das erste Element nach der Base-URL
        $endpoint = $parts[0] ?? 'default';

        // Bestimme Cache-Dauer mit optionalem Zufalls-Offset
        $base_lifetime = $this->transient_times[$endpoint] ?? $this->transient_times['default'];
        $random_offset = rand(0, $this->transient_jitter_minutes) * 60;
        $lifetime = ($base_lifetime * 60) + $random_offset;

        // Erzeuge Transient-Key und speichere die Daten
        $transient_key = $this->transient_prefix . $endpoint . '_' . md5($url);
        set_transient($transient_key, $data, $lifetime);
    }



}
