<?php


/**
 * Fetch persons from the FAU persons API
 * @param int $limit - Limit the number of persons to fetch
 * @param int $offset - Offset the number of persons to fetch
 * @param array $params - Additional query parameters
 * @return array - Array of persons
 */

use RRZE\FAUdir\EnqueueScripts;
use RRZE\FAUdir\FaudirShortcode;
use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Template;
use RRZE\FAUdir\Person;
use RRZE\FAUdir\Debug;

function fetch_fau_persons($limit = 60, $offset = 0, $params = []) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'persons?limit=' . $limit . '&offset=' . $offset;

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
            $logUrl .= '&' . $param . '=' . $params[$param];
            $url .= '&' . $param . '=' . urlencode($params[$param]);
        }
    }

    error_log('$logUrl: ' . $logUrl);

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

    // error_log('Fetching persons with URL: ' . $url);
    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return __('Error retrieving data: ', 'rrze-faudir') . $response->get_error_message();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return __('Error decoding JSON data.', 'rrze-faudir');
    }
    return $data ?? [];
}

// Fetch person by ID
function fetch_fau_person_by_id($personId) {
    // Log the function call
    // error_log("fetch_fau_person_by_id called with personId: {$personId}");

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

/**
 * Fetch contacts from the FAU contacts API
 * @param int $limit - Limit the number of contacts to fetch
 * @param int $offset - Offset the number of contacts to fetch
 * @param array $params - Additional query parameters
 * @return array - Array of contacts
 */
function fetch_fau_contacts($limit = 20, $offset = 0, $params = []) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'contacts?limit=' . $limit . '&offset=' . $offset;

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

    // error_log('Fetching contacts with URL: ' . $url);
    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return __('Error retrieving data: ', 'rrze-faudir') . $response->get_error_message();
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        return __('Error retrieving data or contacts not found.', 'rrze-faudir');
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return __('Error decoding JSON data.', 'rrze-faudir');
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

/**
 * Fetch organizations from the FAU organizations API
 * @param int $limit - Limit the number of organizations to fetch
 * @param int $offset - Offset the number of organizations to fetch
 * @param array $params - Additional query parameters
 * @return array - Array of organizations
 */
function fetch_fau_organizations($limit = 100, $offset = 1, $params = []) {
    $api_key = FaudirUtils::getKey();
    $url = FaudirUtils::getApiBaseUrl() . 'organizations?limit=' . $limit . '&offset=' . $offset;

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
    $response = wp_remote_get($url, array(
        'headers' => array(
            'accept' => 'application/json',
            'X-API-KEY' => $api_key,
        ),
    ));

    if (is_wp_error($response)) {
        return __('Error retrieving data: ', 'rrze-faudir') . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return __('Error decoding JSON data.', 'rrze-faudir');
    }

    return $data;
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
    // error_log('Fetching workplaces for contact identifier: ' . $contactIdentifier);

    // Fetch contact data
    $contactData = fetch_fau_contacts(1, 0, ['identifier' => $contactIdentifier]);
    // error_log('Contact data response: ' . print_r($contactData, true));

    if (empty($contactData['data'])) {
        // error_log('No contact data found for identifier: ' . $contactIdentifier);
        return __('No workplaces available', 'rrze-faudir');
    }

    $detailedContact = $contactData['data'][0];
    $workplaces = $detailedContact['workplaces'] ?? [];

    if (empty($workplaces)) {
        // error_log('No workplaces found in contact data');
        return __('No workplaces available', 'rrze-faudir');
    }

    // Format workplaces into a string
    $formattedWorkplaces = [];
    foreach ($workplaces as $workplace) {
        $workplaceDetails = [];

        if (!empty($workplace['room'])) {
            $workplaceDetails[] = __('Room', 'rrze-faudir').': ' . $workplace['room'];
        }
        if (!empty($workplace['floor'])) {
            $workplaceDetails[] = __('Floor', 'rrze-faudir').': ' . $workplace['floor'];
        }
        if (!empty($workplace['street'])) {
            $workplaceDetails[] = __('Street', 'rrze-faudir').': ' . $workplace['street'];
        }
        if (!empty($workplace['zip'])) {
            $workplaceDetails[] = __('ZIP Code', 'rrze-faudir').': ' . $workplace['zip'];
        }
        if (!empty($workplace['city'])) {
            $workplaceDetails[] = __('City', 'rrze-faudir').': ' . $workplace['city'];
        }
        if (!empty($workplace['faumap'])) {
            $workplaceDetails[] = __('FAU Map', 'rrze-faudir').': ' . $workplace['faumap'];
        }
        if (!empty($workplace['phones'])) {
            $workplaceDetails[] = __('Phones', 'rrze-faudir').': ' . implode(', ', $workplace['phones']);
        }
        if (!empty($workplace['fax'])) {
            $workplaceDetails[] = __('Fax', 'rrze-faudir').': ' . $workplace['fax'];
        }
        if (!empty($workplace['url'])) {
            $workplaceDetails[] = __('URL', 'rrze-faudir').': ' . $workplace['url'];
        }
        if (!empty($workplace['mails'])) {
            $workplaceDetails[] = __('Emails', 'rrze-faudir').': ' . implode(', ', $workplace['mails']);
        }
        if (!empty($workplace['officeHours'])) {
            $officeHours = array_map(function ($hours) {
                return __('Weekday ', 'rrze-faudir') . $hours['weekday'] . ': ' . $hours['from'] . ' - ' . $hours['to'];
            }, $workplace['officeHours']);
            $workplaceDetails[] = __('Office Hours', 'rrze-faudir') . implode('; ', $officeHours);
        }
        if (!empty($workplace['consultationHours'])) {
            $consultationHours = array_map(function ($hours) {
                return __('Weekday ', 'rrze-faudir') . $hours['weekday'] . ': ' . $hours['from'] . ' - ' . $hours['to'] . ' (' . $hours['comment'] . ') ' . $hours['url'];
            }, $workplace['consultationHours']);
            $workplaceDetails[] = __('Consultation Hours', 'rrze-faudir') . implode('; ', $consultationHours);
        }

        $formattedWorkplaces[] = implode("\n", $workplaceDetails);
    }

    return implode("\n\n", $formattedWorkplaces);
}

function fetch_and_format_address($contactIdentifier)
{
    // error_log('Fetching address for contact identifier: ' . $contactIdentifier);

    // Fetch contact data
    $contactData = fetch_fau_organizations(1, 0, ['identifier' => $contactIdentifier]);
    // error_log('Contact data response: ' . print_r($contactData, true));

    if (empty($contactData['data'])) {
        // error_log('No contact data found for identifier: ' . $contactIdentifier);
        return __('No address available', 'rrze-faudir');
    }

    $detailedContact = $contactData['data'][0];
    $address = $detailedContact['address'] ?? [];

    if (empty($address)) {
        // error_log('No address found in contact data');
        return __('No address available', 'rrze-faudir');
    }

    // Format address into a string
    $addressDetails = [];

    if (!empty($address['phone'])) {
        $addressDetails[] = __('Phone', 'rrze-faudir').': ' . $address['phone'];
    }
    if (!empty($address['mail'])) {
        $addressDetails[] = __('Email', 'rrze-faudir').': ' . $address['mail'];
    }
    if (!empty($address['url'])) {
        $addressDetails[] = __('URL', 'rrze-faudir').': ' . $address['url'];
    }
    if (!empty($address['street'])) {
        $addressDetails[] = __('Street', 'rrze-faudir').': ' . $address['street'];
    }
    if (!empty($address['zip'])) {
        $addressDetails[] = __('ZIP Code', 'rrze-faudir').': ' . $address['zip'];
    }
    if (!empty($address['city'])) {
        $addressDetails[] = __('City', 'rrze-faudir').': ' . $address['city'];
    }
    if (!empty($address['faumap'])) {
        $addressDetails[] = __('FAU Map', 'rrze-faudir').': ' . $address['faumap'];
    }

    return implode("\n", $addressDetails);
}

function fetch_and_format_socials($contactIdentifier) {
    //error_log('Fetching social media for contact identifier: ' . $contactIdentifier);

    // Fetch contact data
    $contactData = fetch_fau_contacts(1, 0, ['identifier' => $contactIdentifier]);
    // error_log('Contact data response: ' . print_r($contactData, true));

    if (empty($contactData['data'])) {
        // error_log('No contact data found for identifier: ' . $contactIdentifier);
        return __('No social media available', 'rrze-faudir');
    }

    $detailedContact = $contactData['data'][0];
    $socials = $detailedContact['socials'] ?? [];

    if (empty($socials)) {
        //error_log('No social media found in contact data');
        return __('No social media available', 'rrze-faudir');
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

/**
 * AJAX handler for organization search
 */
function rrze_faudir_search_org_callback() {
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

    if (empty($search_term)) {
        wp_send_json_error(__('Please enter a search term', 'rrze-faudir'));
        return;
    }

    $params = [];

    // Check if the search term looks like an organization number
    if (preg_match('/^\d+$/', $search_term)) {
        $params['orgnr'] = $search_term;
    } else {
        // Otherwise search by name
        $params['q'] = $search_term;
    }

    $response = fetch_fau_organizations(20, 0, $params);

    if (is_string($response)) {
        wp_send_json_error(sprintf(__('Error: %s', 'rrze-faudir'), $response));
    } else {
        $organizations = $response['data'] ?? [];
        if (!empty($organizations)) {
            $output = '<div class="organizations-wrapper">';
            foreach ($organizations as $org) {
                $name = esc_html($org['name']);
                $identifier = esc_html($org['identifier']);
                $disambiguatingDescription = esc_html($org['disambiguatingDescription']);

                $subOrganizations = $org['subOrganization'] ?? [];
                // extract the identifier from the subOrganizations
                $identifiers = array_map(function ($subOrg) {
                    return $subOrg['identifier'];
                }, $subOrganizations);

                // add the identifier of the parent organization to the subOrganizationIdentifiers
                $identifiers[] = $org['identifier'];

                $output .= '<div class="organization-card">';
                $output .= "<h2 class='organization-name'>{$name}</h2>";
                $output .= "<div class='organization-details'>";
                $output .= "<p><strong>" . __('Organization ID', 'rrze-faudir') . ":</strong> {$identifier}</p>";
                $output .= "<p><strong>" . __('Organization Number', 'rrze-faudir') . ":</strong> {$disambiguatingDescription}</p>";

                // Add parent organization if available
                if (!empty($org['parentOrganization'])) {
                    $parent_name = esc_html($org['parentOrganization']['name']);
                    $output .= "<p><strong>" . __('Parent Organization', 'rrze-faudir') . ":</strong> {$parent_name}</p>";
                }

                // Add organization type if available
                if (!empty($org['type'])) {
                    $type = esc_html($org['type']);
                    $output .= "<p><strong>" . __('Type', 'rrze-faudir') . ":</strong> {$type}</p>";
                }

                // Add address if available
                if (!empty($org['address'])) {
                    $output .= "<div class='organization-address'>";
                    $output .= "<h3>" . __('Address', 'rrze-faudir') . "</h3>";

                    if (!empty($org['address']['street'])) {
                        $output .= "<p>" . esc_html($org['address']['street']) . "</p>";
                    }
                    if (!empty($org['address']['zip']) || !empty($org['address']['city'])) {
                        $output .= "<p>" . esc_html($org['address']['zip'] ?? '') . " " . esc_html($org['address']['city'] ?? '') . "</p>";
                    }
                    if (!empty($org['address']['phone'])) {
                        $output .= "<p><strong>" . __('Phone', 'rrze-faudir') . ":</strong> " . esc_html($org['address']['phone']) . "</p>";
                    }
                    if (!empty($org['address']['mail'])) {
                        $output .= "<p><strong>" . __('Email', 'rrze-faudir') . ":</strong> " . esc_html($org['address']['mail']) . "</p>";
                    }
                    if (!empty($org['address']['url'])) {
                        $output .= "<p><strong>" . __('Website', 'rrze-faudir') . ":</strong> <a href='" . esc_url($org['address']['url']) . "' target='_blank'>" . esc_html($org['address']['url']) . "</a></p>";
                    }
                    $output .= "</div>";
                }

                $output .= "</div>"; // Close organization-details
                $output .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display: inline;">';
                $output .= wp_nonce_field('save_default_organization', '_wpnonce', true, false);
                $output .= '<input type="hidden" name="action" value="save_default_organization">';
                $output .= '<input type="hidden" name="org_ids" value="' . esc_attr(json_encode($identifiers)) . '">';
                $output .= '<input type="hidden" name="org_name" value="' . esc_attr($name) . '">';
                $output .= '<input type="hidden" name="org_nr" value="' . esc_attr($disambiguatingDescription) . '">';
                $output .= '<button type="submit" class="button button-primary">' .
                    esc_html__('Save as Default Organization', 'rrze-faudir') .
                    '</button>';
                $output .= '</form>';
                $output .= '</div>'; // Close organization-card
            }
            $output .= '</div>';
            wp_send_json_success($output);
        } else {
            wp_send_json_error(__('No organizations found. Please try a different search term.', 'rrze-faudir'));
        }
    }
}
add_action('wp_ajax_rrze_faudir_search_org', 'rrze_faudir_search_org_callback');

/**
 * Handle saving the default organization
 */
function rrze_faudir_save_default_organization() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'rrze-faudir'));
    }

    check_admin_referer('save_default_organization');

    // Debug the raw POST data
    // error_log('Raw POST org_ids: ' . print_r($_POST['org_ids'], true));

    $org_ids = [];
    if (isset($_POST['org_ids'])) {
        $decoded = json_decode(stripslashes($_POST['org_ids']), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $org_ids = $decoded;
        } else {
            // error_log('JSON decode error: ' . json_last_error_msg());
        }
    }

    $org_name = isset($_POST['org_name']) ? sanitize_text_field($_POST['org_name']) : '';
    $org_nr = isset($_POST['org_nr']) ? sanitize_text_field($_POST['org_nr']) : '';

    // error_log('Processed Org IDs: ' . print_r($org_ids, true));
    // error_log('Org Name: ' . $org_name);
    // error_log('Org NR: ' . $org_nr);

    if (!empty($org_ids) && !empty($org_name)) {
        $options = get_option('rrze_faudir_options', array());
        $options['default_organization'] = array(
            'ids' => $org_ids,
            'name' => $org_name,
            'orgnr' => $org_nr
        );
        // error_log('Saving Default Organization: ' . print_r($options['default_organization'], true));
        update_option('rrze_faudir_options', $options);

        add_settings_error(
            'rrze_faudir_messages',
            'default_org_saved',
            __('Default organization has been saved.', 'rrze-faudir'),
            'updated'
        );
    } else {
        // error_log('Missing required data - org_ids or org_name is empty');
    }

    // Redirect back to the settings page
    wp_redirect(add_query_arg(
        array('page' => 'rrze-faudir', 'settings-updated' => 'true'),
        admin_url('options-general.php')
    ));
    exit;
}
add_action('admin_post_save_default_organization', 'rrze_faudir_save_default_organization');
