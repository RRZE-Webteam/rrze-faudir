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
    //error_log("fetch_fau_person_by_id called with personId: {$personId}");

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
function fetch_fau_contacts($limit = 20, $offset = 0, $params = []) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'contacts?limit=' . $limit . '&offset=' . $offset;
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

// Fetch contact by ID
function fetch_fau_contact_by_id($contactId) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . "contacts/{$contactId}";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true) ?? [];
}

// Fetch organization by ID
function fetch_fau_organization_by_id($organizationId) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . "organizations/{$organizationId}";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true) ?? [];
}

function fetch_and_format_workplaces($contactIdentifier) {
    error_log('Fetching workplaces for contact identifier: ' . $contactIdentifier);

    // Fetch contact data
    $contactData = fetch_fau_contacts(1, 0, ['identifier' => $contactIdentifier]);
    error_log('Contact data response: ' . print_r($contactData, true));

    if (empty($contactData['data'])) {
        error_log('No contact data found for identifier: ' . $contactIdentifier);
        return 'No workplaces available';
    }

    $detailedContact = $contactData['data'][0];
    $workplaces = $detailedContact['workplaces'] ?? [];

    if (empty($workplaces)) {
        error_log('No workplaces found in contact data');
        return 'No workplaces available';
    }

    // Format workplaces into a string
    $formattedWorkplaces = [];
    foreach ($workplaces as $workplace) {
        $workplaceDetails = [];

        if (!empty($workplace['room'])) {
            $workplaceDetails[] = 'Room: ' . $workplace['room'];
        }
        if (!empty($workplace['floor'])) {
            $workplaceDetails[] = 'Floor: ' . $workplace['floor'];
        }
        if (!empty($workplace['street'])) {
            $workplaceDetails[] = 'Street: ' . $workplace['street'];
        }
        if (!empty($workplace['zip'])) {
            $workplaceDetails[] = 'ZIP: ' . $workplace['zip'];
        }
        if (!empty($workplace['city'])) {
            $workplaceDetails[] = 'City: ' . $workplace['city'];
        }
        if (!empty($workplace['faumap'])) {
            $workplaceDetails[] = 'FAU Map: ' . $workplace['faumap'];
        }
        if (!empty($workplace['phones'])) {
            $workplaceDetails[] = 'Phones: ' . implode(', ', $workplace['phones']);
        }
        if (!empty($workplace['fax'])) {
            $workplaceDetails[] = 'Fax: ' . $workplace['fax'];
        }
        if (!empty($workplace['url'])) {
            $workplaceDetails[] = 'URL: ' . $workplace['url'];
        }
        if (!empty($workplace['mails'])) {
            $workplaceDetails[] = 'Emails: ' . implode(', ', $workplace['mails']);
        }
        if (!empty($workplace['officeHours'])) {
            $officeHours = array_map(function($hours) {
                return 'Weekday ' . $hours['weekday'] . ': ' . $hours['from'] . ' - ' . $hours['to'];
            }, $workplace['officeHours']);
            $workplaceDetails[] = 'Office Hours: ' . implode('; ', $officeHours);
        }
        if (!empty($workplace['consultationHours'])) {
            $consultationHours = array_map(function($hours) {
                return 'Weekday ' . $hours['weekday'] . ': ' . $hours['from'] . ' - ' . $hours['to'] . ' (' . $hours['comment'] . ')';
            }, $workplace['consultationHours']);
            $workplaceDetails[] = 'Consultation Hours: ' . implode('; ', $consultationHours);
        }

        $formattedWorkplaces[] = implode("\n", $workplaceDetails);
    }

    return implode("\n\n", $formattedWorkplaces);
}

function fetch_and_format_address($contactIdentifier) {
    error_log('Fetching address for contact identifier: ' . $contactIdentifier);

    // Fetch contact data
    $contactData = fetch_fau_organizations(1, 0, ['identifier' => $contactIdentifier]);
    error_log('Contact data response: ' . print_r($contactData, true));

    if (empty($contactData['data'])) {
        error_log('No contact data found for identifier: ' . $contactIdentifier);
        return 'No address available';
    }

    $detailedContact = $contactData['data'][0];
    $address = $detailedContact['address'] ?? [];

    if (empty($address)) {
        error_log('No address found in contact data');
        return 'No address available';
    }

    // Format address into a string
    $addressDetails = [];

    if (!empty($address['phone'])) {
        $addressDetails[] = 'Phone: ' . $address['phone'];
    }
    if (!empty($address['mail'])) {
        $addressDetails[] = 'Email: ' . $address['mail'];
    }
    if (!empty($address['url'])) {
        $addressDetails[] = 'URL: ' . $address['url'];
    }
    if (!empty($address['street'])) {
        $addressDetails[] = 'Street: ' . $address['street'];
    }
    if (!empty($address['zip'])) {
        $addressDetails[] = 'ZIP: ' . $address['zip'];
    }
    if (!empty($address['city'])) {
        $addressDetails[] = 'City: ' . $address['city'];
    }
    if (!empty($address['faumap'])) {
        $addressDetails[] = 'FAU Map: ' . $address['faumap'];
    }

    return implode("\n", $addressDetails);
}

function fetch_and_format_socials($contactIdentifier) {
    error_log('Fetching social media for contact identifier: ' . $contactIdentifier);

    // Fetch contact data
    $contactData = fetch_fau_contacts(1, 0, ['identifier' => $contactIdentifier]);
    error_log('Contact data response: ' . print_r($contactData, true));

    if (empty($contactData['data'])) {
        error_log('No contact data found for identifier: ' . $contactIdentifier);
        return 'No social media available';
    }

    $detailedContact = $contactData['data'][0];
    $socials = $detailedContact['socials'] ?? [];

    if (empty($socials)) {
        error_log('No social media found in contact data');
        return 'No social media available';
    }

    // Format social media into a string
    $formattedSocials = [];
    foreach ($socials as $social) {
        if (!empty($social['platform']) && !empty($social['url'])) {
            $formattedSocials[] = ucfirst($social['platform']) . ': ' . $social['url'];
        }
    }

    return implode("\n", $formattedSocials);
}