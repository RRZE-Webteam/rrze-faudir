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
