<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
function rrze_faudir_add_admin_menu()
{
    add_menu_page(
        __('FAU Directory Settings', 'rrze-faudir'),  // Page title
        __('FAU Directory', 'rrze-faudir'),           // Menu title
        'manage_options',                             // Capability
        'rrze-faudir',                                // Menu slug
        'rrze_faudir_settings_page',                  // Callback function
        'dashicons-admin-generic',                    // Icon
        81                                            // Position
    );
}
add_action('admin_menu', 'rrze_faudir_add_admin_menu');

// Register settings
function rrze_faudir_settings_init()
{
    register_setting('rrze_faudir_settings', 'rrze_faudir_options');

    // API Settings Section
    add_settings_section(
        'rrze_faudir_api_section',
        __('API Settings', 'rrze-faudir'),
        'rrze_faudir_api_section_callback',
        'rrze_faudir_settings'
    );

    add_settings_field(
        'rrze_faudir_api_key',
        __('API Key', 'rrze-faudir'),
        'rrze_faudir_api_key_render',
        'rrze_faudir_settings',
        'rrze_faudir_api_section'
    );
}
add_action('admin_init', 'rrze_faudir_settings_init');

// Section callback
function rrze_faudir_api_section_callback()
{
    echo '<p>' . __('Configure the API settings for accessing the FAU person and institution directory.', 'rrze-faudir') . '</p>';
}

// Field render
function rrze_faudir_api_key_render()
{
    if (FaudirUtils::isUsingNetworkKey()) {
        echo '<p>' . __('The API key is being used from the network installation.', 'rrze-faudir') . '</p>';
    } else {
        $apiKey = FaudirUtils::getKey();
        echo '<input type="text" name="rrze_faudir_options[api_key]" value="' . $apiKey . '" size="50">';
        echo '<p class="description">' . __('Enter your API key here.', 'rrze-faudir') . '</p>';
    }
}

// AJAX API search call
function rrze_faudir_search_person() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'rrze_faudir_api_nonce')) {
        wp_send_json_error('Invalid security token.');
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
        return;
    }

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $idm_kennung = isset($_POST['idm_kennung']) ? sanitize_text_field($_POST['idm_kennung']) : '';
    $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';

    $first_name = '';
    $last_name = '';
    if (!empty($full_name)) {
        $names = explode(' ', $full_name, 2);
        if (count($names) == 2) {
            $first_name = $names[0];
            $last_name = $names[1];
        }
    }

    // Fetch data from the API
    $results = fetch_fau_persons();

    if (is_string($results)) {
        wp_send_json_error($results);
        return;
    }

    $persons = is_array($results) ? $results : []; // Ensure $persons is always an array

    $filtered_results = array_filter($persons, function($person) use ($email, $idm_kennung, $first_name, $last_name) {
        $matches_email = empty($email); 
        $matches_idm_kennung = empty($idm_kennung) || (isset($person['identifier']) && stripos($person['identifier'], $idm_kennung) !== false);
        $matches_first_name = empty($first_name) || (isset($person['givenName']) && stripos($person['givenName'], $first_name) !== false);
        $matches_last_name = empty($last_name) || (isset($person['familyName']) && stripos($person['familyName'], $last_name) !== false);

        if (!empty($email) && isset($person['contacts'])) {
            foreach ($person['contacts'] as $contact) {
                if (isset($contact['email']) && stripos($contact['email'], $email) !== false) {
                    $matches_email = true;
                    break;
                }
            }
        }

        return $matches_email && $matches_idm_kennung && $matches_first_name && $matches_last_name;
    });

    wp_send_json_success(array_values($filtered_results));
}

add_action('wp_ajax_rrze_faudir_search_person', 'rrze_faudir_search_person');





// Settings page display
function rrze_faudir_settings_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html(__('FAU Directory Settings', 'rrze-faudir')); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('rrze_faudir_settings');
            do_settings_sections('rrze_faudir_settings');
            submit_button();
            ?>
        </form>

        <h2><?php echo __('Search FAU Person', 'rrze-faudir'); ?></h2>
        <form id="fau-person-search-form">
            <label for="email"><?php echo __('E-Mail Address', 'rrze-faudir'); ?>:</label>
            <input type="email" id="email" name="email" />

            <label for="idm_kennung"><?php echo __('IdM-Kennung', 'rrze-faudir'); ?>:</label>
            <input type="text" id="idm_kennung" name="idm_kennung" />

            <label for="full_name"><?php echo __('First Name PLUS Last Name', 'rrze-faudir'); ?>:</label>
            <input type="text" id="full_name" name="full_name" />

            <button type="button" id="search-person" class="button button-primary"><?php echo __('Search', 'rrze-faudir'); ?></button>
        </form>

        <div id="search-results"></div>
    </div>
<?php
}
