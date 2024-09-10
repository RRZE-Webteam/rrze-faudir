<?php
// Include this file in your plugin's main file
// require_once plugin_dir_path(__FILE__) . 'api-functions.php';
// Fetch data from the FAU persons API

function fetch_fau_persons($limit = 60, $offset = 0) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'persons?limit=' . $limit . '&offset=' . $offset;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'X-API-KEY: ' . $api_key,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        return 'Error retrieving data.';
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }

    return $data ?? [];
}


// Fetch data from the FAU organizations API
function fetch_fau_organizations($limit = 100, $offset = 1) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() .'organizations?limit=' . $limit . '&offset=' . $offset;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'X-API-KEY: ' . $api_key,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        return 'Error retrieving data.';
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }

    return $data;
}

//search person by id
function fetch_fau_person_by_id($personId) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . "persons/{$personId}";

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'X-API-KEY: ' . $api_key,
        ),
    ));

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false || $http_code !== 200) {
        return 'Error retrieving data or person not found.';
    }

    curl_close($curl);

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }

    return $data ?? [];
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
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'X-API-KEY: ' . $api_key,
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    if ($response === false) {
        return 'Error retrieving data.';
    }
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }
    return $data ?? [];
}
// Fetch data from the FAU contacts API
function fetch_fau_contacts($limit = 20, $offset = 0) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'contacts?limit=' . $limit . '&offset=' . $offset;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'X-API-KEY: ' . $api_key,
        ),
    ));
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($response === false || $http_code !== 200) {
        curl_close($curl);
        return 'Error retrieving data or contacts not found.';
    }
    curl_close($curl);
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Error decoding JSON data.';
    }
    return $data ?? [];
}
