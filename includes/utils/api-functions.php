<?php
// Include this file in your plugin's main file
// require_once plugin_dir_path(__FILE__) . 'api-functions.php';
// Fetch data from the FAU persons API

function fetch_fau_persons($limit = 60, $offset = 0) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'persons?limit=' . $limit . '&offset=' . $offset;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return 'Error retrieving data: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }

    return $data ?? [];
}


// Fetch data from the FAU organizations API
function fetch_fau_organizations($limit = 100, $offset = 1, $params=[]) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() .'organizations?limit=' . $limit . '&offset=' . $offset;

    $query_params = [
        'q', 'sort', 'attrs', 'lq', 'rq', 'view', 'lf'
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
    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return 'Error retrieving data: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }

    return $data;
}


//search person by id
function fetch_fau_person_by_id($personId) {
    // Log the function call
    error_log("fetch_fau_person_by_id called with personId: {$personId}");

    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . "persons/{$personId}";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
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

function fetch_fau_persons_atributes($limit = 60, $offset = 0, $params = []) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'persons?limit=' . $limit . '&offset=' . $offset;
    // Define allowed query parameters and map them to their corresponding keys
    $query_params = [
        'q', 'sort', 'attrs', 'lq', 'rq', 'view', 'lf'
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
    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));
    if (is_wp_error($response)) {
        return 'Error retrieving data: ' . $response->get_error_message();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }
    return $data ?? [];
}
// Fetch data from the FAU contacts API
function fetch_fau_contacts($limit = 20, $offset = 0) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'contacts?limit=' . $limit . '&offset=' . $offset;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return 'Error retrieving data: ' . $response->get_error_message();
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        return 'Error retrieving data or contacts not found.';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }

    return $data ?? [];
}
