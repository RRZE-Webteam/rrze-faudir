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
