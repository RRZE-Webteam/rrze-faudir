<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class API {
    private string $baseUrl = 'https://api.fau.de/pub/v1/opendir/';
    private string $api_key;
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        if (!empty($this->config->get('api-baseurl'))) {
            $this->baseUrl = $this->config->get('api-baseurl');
        }
        $this->api_key = $this->getKey();
    }
    
   
    private static function isUsingNetworkKey(): bool  {
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
            throw new \Exception("FAUdir\API (getPerson): API Key is required.");
        }
        if (empty($personId)) {
            throw new \InvalidArgumentException('FAUdir\API (getPerson): Required field personid missing.');
        }
        $url = "{$this->baseUrl}/persons/{$personId}";
        
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            error_log("FAUdir\API (getPerson): No response from server on {$url}.");
            return null;
        }

        // Wandelt das Array in ein Profil-Objekt um
        return $response;
    }
    
    
    /*
     * Get persons
     * @param int $limit - Limit the number of contacts to fetch
     * @param int $offset - Offset the number of contacts to fetch
     * @param array $params - Additional query parameters
     * @return array - Array of persons 
     */
     public function getPersons($limit = 60, $offset = 0, $params = []): ?array {    
        if (!$this->api_key) {
            throw new \Exception("FAUdir\API (getPersons): API Key is required.");
        }
        if (empty($params)) {
            throw new \InvalidArgumentException('FAUdir\API (getPersons): Required params missing.');
        }
        $url = "{$this->baseUrl}/persons";
    
        $url .= '?limit=' . $limit . '&offset=' . $offset;
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

        $logUrl = '';

        // Loop through the parameters and append them to the URL if they exist in $params
        foreach ($query_params as $param) {
            if (!empty($params[$param])) {
                $url .= '&' . $param . '=' . urlencode($params[$param]);
            }
        }

        // Handle givenName and familyName as special cases to be combined into the 'q' parameter
        if (!empty($params['givenName'])) {
            $url .= '&q=' . urlencode('^' . $params['givenName']);
        }
        if (!empty($params['familyName'])) {
            $url .= '&q=' . urlencode('^' . $params['familyName']);
        }
        if (!empty($params['identifier'])) {
            $url .= '&q=' . urlencode('^' . $params['identifier']);
        }
        if (!empty($params['email'])) {
            $url .= '&q=' . urlencode('^' . $params['email']);
        }

        
        $response = $this->makeRequest($url, "REST");

        if (!$response) {
            error_log("FAUdir\API (getPersons): No response from server on {$url}.");
            return null;
        }

        return $response;
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
            throw new \Exception("FAUdir\API (getContacts): API Key is required.");
        }
        if (empty($params)) {
            throw new \InvalidArgumentException('FAUdir\API (getContacts): Required params missing.');
        }
        $url = "{$this->baseUrl}/contacts";
    
        $url .= '?limit=' . $limit . '&offset=' . $offset;

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

        // Loop through the parameters and append them to the URL if they exist in $params
        foreach ($query_params as $param) {
            if (!empty($params[$param])) {
                $url .= '&' . $param . '=' . urlencode($params[$param]);
            }
        }

        // Handle givenName and familyName as special cases to be combined into the 'q' parameter
        if (!empty($params['givenName'])) {
            $url .= '&q=' . urlencode('^' . $params['givenName']);
        }
        if (!empty($params['familyName'])) {
            $url .= '&q=' . urlencode('^' . $params['familyName']);
        }
        if (!empty($params['identifier'])) {
            $url .= '&q=' . urlencode('^' . $params['identifier']);
        }
        if (!empty($params['email'])) {
            $url .= '&q=' . urlencode('^' . $params['email']);
        }
        
        $response = $this->makeRequest($url, "REST");

        if (!$response) {
            error_log("FAUdir\API (getContacts): No response from server on {$url}.");
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
    function getOrgList($limit = 100, $offset = 1, $params = []) {
        if (!$this->api_key) {
            throw new \Exception("FAUdir\API (getOrgList): API Key is required.");
        }   
        if (empty($params)) {
            throw new \InvalidArgumentException('FAUdir\API (getContacts): Required params missing.');
        }
        $url = "{$this->baseUrl}/organizations";
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
                $url .= '&' . $param . '=' . urlencode($params[$param]);
            }
        }
        // Handle givenName and familyName as special cases to be combined into the 'q' parameter
        if (!empty($params['orgnr'])) {
            $url .= '&q=' . urlencode('^' . $params['orgnr']);
        }
        $response = $this->makeRequest($url, "REST");

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
            throw new \Exception("FAUdir\API (getOrgById): API Key is required.");
        }
        if (empty($orgid)) {
            throw new \InvalidArgumentException('FAUdir\API (getOrgById): Required field orgid missing.');
        }
        $url = "{$this->baseUrl}/organizations/{$orgid}";
        
        $response = $this->makeRequest($url, "GET");

        if (!$response) {
            error_log("FAUdir\API (getOrgById): No response from server on {$url}.");
            return null;
        }

        // Wandelt das Array in ein Profil-Objekt um
        return $response;
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
            throw new \Exception("FAUdir\API (makeRequest): API Key is required.");
        }
        if ($method === "GET" && $data) {
            // Daten als URL-Parameter kodieren und an die URL anhängen
            $queryString = http_build_query($data);
            $url .= '?' . $queryString;
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
            return $body;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('error' => true, 'message' => 'Error decoding JSON data');
        }

        return $data;
        
        
    }
}
