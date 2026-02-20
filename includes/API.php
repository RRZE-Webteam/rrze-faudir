<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class API {
    private string $baseUrl = Constants::API_BASE_URL;
    private string $transient_prefix = Constants::TRANSIENT_PREFIX_API;
    private int $transient_jitter_minutes = Constants::TRANSIENT_JITTER_MINUTES;
    private array $transient_times = Constants::TRANSIENT_TIMES;
    private int $default_limit = Constants::DEFAULT_LIMIT;
    private string $api_key;
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        if (!empty($this->config->get('api-baseurl'))) {
            $this->baseUrl = $this->config->get('api-baseurl');
        }
        $this->api_key = $this->getKey();
        
        if (empty($this->api_key)) {
            do_action( 'rrze.log.error',"FAUdir\API (__construct): API Key empty.");
        }
    }
    
   
    public static function isUsingNetworkKey(): bool {
        if (!is_multisite()) {
            return false;
        }

        $settingsOptions = get_site_option('rrze_settings');
        if (is_object($settingsOptions)) {
            return !empty($settingsOptions->plugins->faudir_public_apiKey);
        }
        if (is_array($settingsOptions)) {
            return !empty($settingsOptions['plugins']['faudir_public_apiKey']);
        }

        return false;
    }

    public static function getKey(): string {
        if (self::isUsingNetworkKey()) {
            $settingsOptions = get_site_option('rrze_settings');

            if (is_object($settingsOptions)) {
                return (string) ($settingsOptions->plugins->faudir_public_apiKey ?? '');
            }
            if (is_array($settingsOptions)) {
                return (string) ($settingsOptions['plugins']['faudir_public_apiKey'] ?? '');
            }

            return '';
        }
        $options = get_option('rrze_faudir_options');
        if (!is_array($options)) {
            return '';
        }

        $key = $options['api_key'] ?? '';
        return is_string($key) ? trim($key) : '';
    }

    public function getApiBaseUrl(): string {
        return $this->baseUrl;
    }
    
    
    /**
     * Get a person
     * @param personid
     * @return array|null on not found
     */
    public function getPerson(string $input): ?array {
        if ($this->api_key === '') {
            do_action( 'rrze.log.error', "FAUdir\API (getPerson): API Key missing.");
            return null;
        }
        $personId = FaudirUtils::sanitizePersonId($input);       
        if ($personId === null) {
            do_action('rrze.log.error', "FAUdir\\API (getPerson): Invalid personId {$input}.");
            return null;
        }
        $url = trailingslashit($this->baseUrl) . 'persons/' . $personId;      
        return $this->makeRequest($url, 'GET');
    }
    
    
     /**
     * Get a person
     * @param personid
     * @return array|null on not found
     */
    public function getContact(string $contactId): ?array {
        if ($this->api_key === '') {
            do_action( 'rrze.log.error', "FAUdir\API (getContact): API Key missing.");
            return null;
        }
        $contactId = FaudirUtils::sanitizePersonId($contactId);
        if ($contactId === null) {
            do_action('rrze.log.error', 'FAUdir\API (getContact): Invalid contactId.');
            return null;
        }

        $url = trailingslashit($this->baseUrl) . 'contacts/' . $contactId;
        return $this->makeRequest($url, 'GET');
    }
    
   
  
    
    
    /*
     * Get persons
     * @param int $limit - Limit the number of contacts to fetch
     * @param int $offset - Offset the number of contacts to fetch
     * @param array $params - Additional query parameters
     * @return array - Array of persons 
     */
     public function getPersons($limit = 100, $offset = 0, $params = [], bool $retry = true): ?array {
        if (!$this->api_key) {
            do_action( 'rrze.log.error', "FAUdir\API (getPersons): API Key missing.");           
            return null;
        }
        if (empty($params)) {
            do_action( 'rrze.log.error', "FAUdir\API (getPersons): Required params missing.");         
            return null;
        }
        $url = "{$this->baseUrl}persons";
        if ($limit==0) {
            $limit = $this->default_limit;
        }
        $url .= '?limit=' . $limit . '&offset=' . $offset;
        $param_uri = '';
        
         // Define allowed query parameters and map them to their corresponding keys
        $query_params = ['q','sort', 'attrs', 'lq', 'rq', 'view','lf'];

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
            
        //    do_action( 'rrze.log.info',"FAUdir\API (getPersons): URL= ".$url);
            
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
                do_action( 'rrze.log.error',"FAUdir\API (getPersons): No response from server on {$url}.");
                return null;
            }
            return $response;
        }
        do_action( 'rrze.log.error',"FAUdir\API (getPersons): No params to query for.");
        return [];

    }   
   
   
    
    /**
    * Get contacts  
    * @param int $limit - Limit the number of contacts to fetch
    * @param int $offset - Offset the number of contacts to fetch
    * @param array $params - Additional query parameters
    * @return array - Array of contacts
    */
    public function getContacts($limit = 20, $offset = 0, $params = []): ?array {
        if (!$this->api_key) {
            do_action('rrze.log.error', "FAUdir\API (getContacts): API Key missing.");
            return null;
        }
        if (empty($params)) {
            do_action('rrze.log.error', "FAUdir\API (getContacts): Required params missing.");
            return null;
        }

        $url = "{$this->baseUrl}contacts";
        if ($limit == 0) {
            $limit = $this->default_limit;
        }
        $url .= '?limit=' . $limit . '&offset=' . $offset;
        $param_uri = '';

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
                $params['q'] = '^' . trim(($params['givenName'] ?? '') . ' ' . ($params['familyName'] ?? ''));
            }
        }

        foreach ($query_params as $param) {
            if (!empty($params[$param])) {
                $param_uri .= '&' . $param . '=' . $this->encodeParam($param, (string) $params[$param]);
            }
        }

        if ($param_uri === '') {
            do_action('rrze.log.error', "FAUdir\API (getContacts): No params to query for.");
            return [];
        }

        $url .= $param_uri;

        $response = $this->makeRequest($url, "GET");
        if (!$response) {
            do_action('rrze.log.error', "FAUdir\API (getContacts): No response from server on {$url}.");
            return null;
        }

        return $response;
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
            do_action( 'rrze.log.error', "FAUdir\API (getOrgList): API Key missing.");
            return null;
        }   
        if (empty($params)) {
            do_action( 'rrze.log.error', "FAUdir\API (getOrgList): Required params missing.");
            return null;
        }
        $url = "{$this->baseUrl}organizations";
        if ($limit==0) {
            $limit = $this->default_limit;
        }
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
            $orgnr = FaudirUtils::sanitizeOrgnr((string) $params['orgnr']);
            if ($orgnr !== null) {
                $url .= '&q=' . urlencode('^' . $orgnr);
            }
        }
     //   do_action( 'rrze.log.info', "FAUdir\API (getOrgList): Requesting {$url}.");
        
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            do_action( 'rrze.log.error', "FAUdir\API (getOrgList): No valid response from server on {$url}.");
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
        if ($this->api_key === '') {
            do_action( 'rrze.log.error', "FAUdir\API (getOrgById): API Key missing.");
            return null;
        }
        
        $orgid = FaudirUtils::sanitizeOrganizationId($orgid);
        if ($orgid === null) {
            do_action('rrze.log.error', "FAUdir\\API (getOrgById): Invalid organization id.");
            return null;
        }
        
        $url = trailingslashit($this->baseUrl) . 'organizations/' . $orgid;
        return $this->makeRequest($url, 'GET');
    }

    /**
     * Encodes a query parameter 
     */
    private function encodeParam(string $key, string $value): string {
        return rawurlencode($value);
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
        if ($this->api_key === '') {
            do_action( 'rrze.log.error', "FAUdir\API (makeRequest): API Key missing.");
            return null;
        }

        $method = strtoupper(trim($method));
        if ($method !== 'GET') {
            do_action('rrze.log.error', "FAUdir\API (makeRequest): Unsupported method {$method} for {$url}.");
            return null;
        }

        if (!empty($data)) {
            $url = add_query_arg($data, $url);
        }
        
        $url = $this->normalizeUrlForCache($url);
        // Prüfe, ob gecachte Daten existieren
        $cached = $this->get_cache_data($url);
        if ($cached !== null) {
            return $cached;
        }
        
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'accept' => 'application/json',
                'X-API-KEY' => $this->api_key
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            do_action('rrze.log.error', "FAUdir\API (makeRequest): WP_Error on {$url}: " . $response->get_error_message());
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            do_action('rrze.log.error', "FAUdir\API (makeRequest): HTTP {$code} on {$url}.");
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            do_action('rrze.log.error', "FAUdir\\API (makeRequest): Empty body on {$url}.");
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            do_action('rrze.log.error', "FAUdir\API (makeRequest): JSON decode error on {$url}: " . json_last_error_msg());
            return null;
        }

        $this->set_cache_data($url, $decoded);

        return $decoded;
        
        
    }
 
    /*
     * Get Cache from transient data
     */
    private function get_cache_data(string $url): ?array {
        $parsed = wp_parse_url($url);
        $path = (string) ($parsed['path'] ?? '');

        $basePath = (string) wp_parse_url($this->baseUrl, PHP_URL_PATH);
        $relative = $basePath !== '' ? str_replace($basePath, '', $path) : $path;

        $parts = explode('/', trim($relative, '/'));
        $endpoint = $parts[0] !== '' ? $parts[0] : 'default';

        $transient_key = $this->transient_prefix . $endpoint . '_' . md5($url);

        $cached = get_transient($transient_key);
        if ($cached !== false && is_array($cached)) {
            return $cached;
        }

        return null; 
    }
    
    // Save data as transient
    private function set_cache_data(string $url, array $data): void {
        $parsed = wp_parse_url($url);
        $path = (string) ($parsed['path'] ?? '');

        $basePath = (string) wp_parse_url($this->baseUrl, PHP_URL_PATH);
        $relative = $basePath !== '' ? str_replace($basePath, '', $path) : $path;

        $parts = explode('/', trim($relative, '/'));
        $endpoint = $parts[0] !== '' ? $parts[0] : 'default';

        $base_minutes = (int) ($this->transient_times[$endpoint] ?? $this->transient_times['default']);
        $random_offset = wp_rand(0, $this->transient_jitter_minutes) * 60;
        $lifetime = ($base_minutes * 60) + $random_offset;

        $transient_key = $this->transient_prefix . $endpoint . '_' . md5($url);
        set_transient($transient_key, $data, $lifetime);
        do_action( 'rrze.log.info', "FAUdir\API (set_cache_data): Set Transient key {$transient_key}.");
    }

    
    /*
     * Reihenfolge der Request Query-Bestandteile normalisieren
     */
    private function normalizeUrlForCache(string $url): string {
        $parsed = wp_parse_url($url);
        if (!is_array($parsed)) {
            return $url;
        }

        $scheme = (string) ($parsed['scheme'] ?? '');
        $host = (string) ($parsed['host'] ?? '');
        $path = (string) ($parsed['path'] ?? '');
        $query = (string) ($parsed['query'] ?? '');

        if ($scheme === '' || $host === '' || $query === '') {
            return $url;
        }

        $params = [];
        parse_str($query, $params);

        if (!is_array($params) || empty($params)) {
            return $url;
        }

        ksort($params);

        $rebuilt = $scheme . '://' . $host . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        if (!empty($parsed['fragment'])) {
            $rebuilt .= '#' . $parsed['fragment'];
        }

        return $rebuilt;
    }


}
