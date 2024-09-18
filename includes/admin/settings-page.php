<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
function rrze_faudir_add_admin_menu()
{
    add_options_page(
        __('RRZE-FAUdir Settings', 'rrze-faudir'),
        __('RRZE-FAUdir', 'rrze-faudir'),
        'manage_options',
        'rrze-faudir',
        'rrze_faudir_settings_page'
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

    // Cache Settings Section
    add_settings_section(
        'rrze_faudir_cache_section',
        __('Cache Settings', 'rrze-faudir'),
        'rrze_faudir_cache_section_callback',
        'rrze_faudir_settings_cache'
    );

    add_settings_field(
        'rrze_faudir_no_cache_logged_in',
        __('No Caching for Logged-in Editors', 'rrze-faudir'),
        'rrze_faudir_no_cache_logged_in_render',
        'rrze_faudir_settings_cache',
        'rrze_faudir_cache_section'
    );





    add_settings_field(
        'rrze_faudir_cache_timeout',
        __('Cache Timeout (in minutes)', 'rrze-faudir'),
        'rrze_faudir_cache_timeout_render',
        'rrze_faudir_settings_cache',
        'rrze_faudir_cache_section'
    );
    add_settings_field(
        'rrze_faudir_clear_cache',
        __('Clear All Cache', 'rrze-faudir'),
        'rrze_faudir_clear_cache_render',
        'rrze_faudir_settings_cache',
        'rrze_faudir_cache_section'
    );
    

    // Error Handling Section
    add_settings_section(
        'rrze_faudir_error_section',
        __('Error Handling', 'rrze-faudir'),
        'rrze_faudir_error_section_callback',
        'rrze_faudir_settings_error'
    );

    add_settings_field(
        'rrze_faudir_error_message',
        __('Show Error Message for Invalid Contacts', 'rrze-faudir'),
        'rrze_faudir_error_message_render',
        'rrze_faudir_settings_error',
        'rrze_faudir_error_section'
    );

    // Business Card Link Section
    add_settings_section(
        'rrze_faudir_business_card_section',
        __('Business Card Link', 'rrze-faudir'),
        'rrze_faudir_business_card_section_callback',
        'rrze_faudir_settings_business_card'
    );

    add_settings_field(
        'rrze_faudir_business_card_title',
        __('Business Card Link Title', 'rrze-faudir'),
        'rrze_faudir_business_card_title_render',
        'rrze_faudir_settings_business_card',
        'rrze_faudir_business_card_section'
    );

    // Contacts Search Section
    add_settings_section(
        'rrze_faudir_contacts_search_section',
        __('Search Contacts by Identifier', 'rrze-faudir'),
        'rrze_faudir_contacts_search_section_callback',
        'rrze_faudir_settings_contacts_search'
    );

    // Shortcode Settings Section
    add_settings_section(
        'rrze_faudir_shortcode_section',
        __('Shortcode Settings', 'rrze-faudir'),
        'rrze_faudir_shortcode_section_callback',
        'rrze_faudir_settings_shortcode'
    );

    // Note: The search form will be handled in the settings page itself, no need for a settings field here.

    
}
add_action('admin_init', 'rrze_faudir_settings_init');



// Callback functions
function rrze_faudir_api_section_callback()
{
    echo '<p>' . __('Configure the API settings for accessing the FAU person and institution directory.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_section_callback()
{
    echo '<p>' . __('Configure caching settings for the plugin.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_error_section_callback()
{
    echo '<p>' . __('Handle error messages for invalid contact entries.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_business_card_section_callback()
{
    echo '<p>' . __('Configure the business card link settings.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_shortcode_section_callback()
{
    echo '<p>' . __('Configure the shortcode settings.', 'rrze-faudir') . '</p>';
}

// Render functions
function rrze_faudir_api_key_render()
{
    if (FaudirUtils::isUsingNetworkKey()) {
        echo '<p>' . __('The API key is being used from the network installation.', 'rrze-faudir') . '</p>';
    } else {
        $options = get_option('rrze_faudir_options');
        $apiKey = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo '<input type="text" name="rrze_faudir_options[api_key]" value="' . $apiKey . '" size="50">';
        echo '<p class="description">' . __('Enter your API key here.', 'rrze-faudir') . '</p>';
    }
}

function rrze_faudir_no_cache_logged_in_render()
{
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['no_cache_logged_in']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[no_cache_logged_in]" value="1" ' . $checked . '>';
    echo '<p class="description">' . __('Disable caching for logged-in editors.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_timeout_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['cache_timeout']) ? intval($options['cache_timeout']) : 15; // Default to 15 minutes
    echo '<input type="number" name="rrze_faudir_options[cache_timeout]" value="' . $value . '" min="1">';
    echo '<p class="description">' . __('Set the cache timeout in minutes (minimum 1 minute).', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_org_timeout_render()
{
    $options = get_option('rrze_faudir_options');
    $value = isset($options['cache_org_timeout']) ? intval($options['cache_org_timeout']) : 1;
    echo '<input type="number" name="rrze_faudir_options[cache_org_timeout]" value="' . $value . '" min="1">';
    echo '<p class="description">' . __('Set the cache timeout in days for organization identifiers.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_clear_cache_render() {
    echo '<button type="button" class="button button-secondary" id="clear-cache-button">' . __('Clear Cache Now', 'rrze-faudir') . '</button>';
    echo '<p class="description">' . __('Click the button to clear all cached data.', 'rrze-faudir') . '</p>';
}


function rrze_faudir_error_message_render()
{
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['show_error_message']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[show_error_message]" value="1" ' . $checked . '>';
    echo '<p class="description">' . __('Show error messages for incorrect contact entries.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_business_card_title_render()
{
    $options = get_option('rrze_faudir_options');
    $value = isset($options['business_card_title']) ? sanitize_text_field($options['business_card_title']) : __('Call up business card', 'rrze-faudir');
    echo '<input type="text" name="rrze_faudir_options[business_card_title]" value="' . $value . '" size="50">';
    echo '<p class="description">' . __('Enter the title for the business card link.', 'rrze-faudir') . '</p>';
}

// Settings page display
function rrze_faudir_settings_page()
{
    ?>
    <div class="wrap">
        <h1>
            <?php echo esc_html(__('FAU Directory Settings', 'rrze-faudir')); ?>
        </h1>

        <!-- Tabs Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="#tab-1" class="nav-tab nav-tab-active">
                <?php echo __('API Settings', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-2" class="nav-tab">
                <?php echo __('Cache Settings', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-3" class="nav-tab">
                <?php echo __('Error Handling', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-4" class="nav-tab">
                <?php echo __('Business Card Link', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-5" class="nav-tab">
                <?php echo __('Search Contacts', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-6" class="nav-tab">
                <?php echo __('Shortcode Settings', 'rrze-faudir'); ?>
            </a>
        </h2>

        <!-- Tabs Content -->
        <form action="options.php" method="post">
            <?php settings_fields('rrze_faudir_settings'); ?>


            <!-- API Settings Tab -->
            <div id="tab-1" class="tab-content">
                <?php do_settings_sections('rrze_faudir_settings'); ?>
                <?php submit_button(); ?>
            </div>

            <!-- Cache Settings Tab -->
            <div id="tab-2" class="tab-content" style="display:none;">
                <?php do_settings_sections('rrze_faudir_settings_cache'); ?>
                <?php submit_button(); ?>
            </div>

            <!-- Error Handling Tab -->
            <div id="tab-3" class="tab-content" style="display:none;">
                <?php do_settings_sections('rrze_faudir_settings_error'); ?>
                <?php submit_button(); ?>
            </div>

            <!-- Business Card Link Tab -->
            <div id="tab-4" class="tab-content" style="display:none;">
                <?php do_settings_sections('rrze_faudir_settings_business_card'); ?>
                <?php submit_button(); ?>
            </div>

            <!-- Contacts Search Tab -->
            <div id="tab-5" class="tab-content" style="display:none;">
                <h2>
                    <?php echo __('Search Contacts by Identifier', 'rrze-faudir'); ?>
                </h2>

                <form id="search-person-form">
                    <label for="person-id">Search by Name, Surbame, Email or ID</label>
                    <input type="text" id="person-id" name="person-id" />

                    <label for="given-name">Given Name:</label>
                    <input type="text" id="given-name" name="given-name" />

                    <label for="family-name">Family Name:</label>
                    <input type="text" id="family-name" name="family-name" />
                    <label for="email">Email:</label>
                    <input type="text" id="email" name="email" />

                    <button type="button" id="search-person-by-id" class="button button-primary">Search</button>
                </form>

                <div id="contacts-list">
                   
                </div>
            </div>

            <!-- Shortcode Settings Tab -->
            <div id="tab-6" class="tab-content" style="display:none;">
                <?php do_settings_sections('rrze_faudir_settings_shortcode'); ?>
                <?php submit_button(); ?>
            </div>
        </form>
    </div>

    <script type="text/javascript">
      
    </script>
    <?php
}




function rrze_faudir_fetch_contacts_handler()
{
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $page = intval($_POST['page']);
    $output = rrze_faudir_display_all_contacts($page);

    wp_send_json_success($output);
}

add_action('wp_ajax_rrze_faudir_fetch_contacts', 'rrze_faudir_fetch_contacts_handler');

// AJAX handler for filtering contacts



// Clear Cache Function
function rrze_faudir_clear_cache() {
    global $wpdb;
    // Delete all transients related to the plugin's cache
    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%_transient_rrze_faudir%'");

    wp_send_json_success(__('All cache cleared successfully.', 'rrze-faudir'));
}
add_action('wp_ajax_rrze_faudir_clear_cache', 'rrze_faudir_clear_cache');



function rrze_faudir_search_person_by_id_handler()
{
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $personId = sanitize_text_field($_POST['personId']);
    $givenName = sanitize_text_field($_POST['givenName']);
    $familyName = sanitize_text_field($_POST['familyName']);
    $email = sanitize_text_field($_POST['email']);

    // Initialize the response
    $response = '';

    // Check if searching by person ID
    
   if (!empty($givenName) || !empty($familyName) || !empty($personId)|| !empty($email)) {
        // If searching by name, pass the givenName and familyName as parameters
        $params = [
            'givenName' => $givenName,
            'familyName' => $familyName,
            'identifier'=> $personId,
            'email'=> $email,
        ];
        $response = fetch_fau_persons_atributes(60, 0, $params);
    }

    // Check if the response is a valid array (success), otherwise return an error
    if (is_string($response)) {
        wp_send_json_error(__('Error: ' . $response, 'rrze-faudir'));
    }

    $contacts = $response['data'] ?? [];

    if (!empty($contacts)) {
        $output = '<div class="contacts-wrapper">';
        foreach ($contacts as $contact) {
            $name = esc_html($contact['personalTitle'] . ' ' . $contact['givenName'] . ' ' . $contact['familyName']);
            $identifier = esc_html($contact['identifier']);
            $output .= '<div class="contact-card">';
            $output .= "<h2 class='contact-name'>{$name}</h2>";
            $output .= "<p><strong>IdM-Kennung:</strong> {$identifier}</p>";
            $output .= "<p><strong>Email:</strong> " . esc_html($contact['email']) . "</p>";
            if (!empty($contact['contacts'])) {
                foreach ($contact['contacts'] as $contactDetail) {
                    $orgName = esc_html($contactDetail['organization']['name']);
                    $functionLabel = esc_html($contactDetail['functionLabel']['en']);
                    $output .= "<p><strong>Organization:</strong> {$orgName} ({$functionLabel})</p>";
                }
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_send_json_success($output);
    } else {
        wp_send_json_error(__('No contacts found. Please verify the IdM-Kennung or names provided.', 'rrze-faudir'));
    }
}

add_action('wp_ajax_search_person_by_id', 'rrze_faudir_search_person_by_id_handler');

