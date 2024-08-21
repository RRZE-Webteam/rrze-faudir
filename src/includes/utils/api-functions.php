<?php
// Include this file in your plugin's main file
// require_once plugin_dir_path(__FILE__) . 'api-functions.php';
// Fetch data from the FAU persons API


function fetch_fau_persons($limit = 10, $offset = 1) {
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

    return $data;
}

// Fetch data from the FAU organizations API
function fetch_fau_organizations($limit = 10, $offset = 1) {
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
