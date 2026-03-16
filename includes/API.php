<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class API {
    private string $baseUrl = Constants::API_BASE_URL;
    private int $default_limit = Constants::DEFAULT_LIMIT;
    private string $api_key;
    private Config $config;
    private Cache $cache;

    public function __construct(Config $config) {
        $this->config = $config;
        if (!empty($this->config->get('api-baseurl'))) {
            $this->baseUrl = $this->config->get('api-baseurl');
        }
        $this->cache = new Cache($this->baseUrl);
        $this->api_key = FaudirUtils::getKey();
        
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
     * @param $input is a FAUdir Identifier
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
        $cacheKeyBasis = Constants::TRANSIENT_KEY_PERSON_PREFIX . $personId;
        return $this->makeRequest($url, 'GET', null, $cacheKeyBasis);
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
        $contactId = FaudirUtils::sanitizeContactId($contactId);
        if ($contactId === null) {
            do_action('rrze.log.error', 'FAUdir\API (getContact): Invalid contactId.');
            return null;
        }

        $url = trailingslashit($this->baseUrl) . 'contacts/' . $contactId;
        $cacheKeyBasis = Constants::TRANSIENT_KEY_CONTACT_PREFIX . $contactId;
        return $this->makeRequest($url, 'GET', null, $cacheKeyBasis);
    }
    
   
  
    
    
    /*
     * Get persons
     * @param int $limit - Limit the number of contacts to fetch
     * @param int $offset - Offset the number of contacts to fetch
     * @param array $params - Additional query parameters
     * @return array - Array of persons 
     */
     public function getPersons(int $limit = 100, int $offset = 0, array $params = [], bool $retry = true): ?array {
        if (!$this->api_key) {
            do_action( 'rrze.log.error', "FAUdir\API (getPersons): API Key missing.");           
            return null;
        }
        if (empty($params)) {
            do_action( 'rrze.log.error', "FAUdir\API (getPersons): Required params missing.");         
            return null;
        }
        $url = trailingslashit($this->baseUrl) . 'persons';
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
    public function getContacts(int $limit = 20, int $offset = 0, array $params = []): ?array {
        if (!$this->api_key) {
            do_action('rrze.log.error', "FAUdir\API (getContacts): API Key missing.");
            return null;
        }
        if (empty($params)) {
            do_action('rrze.log.error', "FAUdir\API (getContacts): Required params missing.");
            return null;
        }
        $url = trailingslashit($this->baseUrl) . 'contacts';
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
    public function getOrgList(int $limit = 100, int $offset = 0, array $params = []): ?array {
        if (!$this->api_key) {
            do_action( 'rrze.log.error', "FAUdir\API (getOrgList): API Key missing.");
            return null;
        }   
        if (empty($params)) {
            do_action( 'rrze.log.error', "FAUdir\API (getOrgList): Required params missing.");
            return null;
        }
        $url = trailingslashit($this->baseUrl) . 'organizations';
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
        $cacheKeyBasis = Constants::TRANSIENT_KEY_ORG_PREFIX . $orgid;
        return $this->makeRequest($url, 'GET', null, $cacheKeyBasis);
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
    private function makeRequest(string $url, string $method, ?array $data = null, ?string $cache_key_basis = null): ?array {
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
        
        $url = $this->cache->normalizeUrlForCache($url);

        $cached = $this->cache->get($url, $cache_key_basis);
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

        $this->cache->set($url, $decoded, $cache_key_basis);

        return $decoded;
        
        
    }
 

}
