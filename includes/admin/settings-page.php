<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
function rrze_faudir_add_admin_menu() {
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
// Register settings
function rrze_faudir_settings_init() {
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
        'rrze_faudir_cache_org_timeout',
        __('Organization Cache Timeout (in days)', 'rrze-faudir'),
        'rrze_faudir_cache_org_timeout_render',
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

    // Note: The search form will be handled in the settings page itself, no need for a settings field here.
}
add_action('admin_init', 'rrze_faudir_settings_init');



// Callback functions
function rrze_faudir_api_section_callback() {
    echo '<p>' . __('Configure the API settings for accessing the FAU person and institution directory.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_section_callback() {
    echo '<p>' . __('Configure caching settings for the plugin.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_error_section_callback() {
    echo '<p>' . __('Handle error messages for invalid contact entries.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_business_card_section_callback() {
    echo '<p>' . __('Configure the business card link settings.', 'rrze-faudir') . '</p>';
}

// Render functions
function rrze_faudir_api_key_render() {
    if (FaudirUtils::isUsingNetworkKey()) {
        echo '<p>' . __('The API key is being used from the network installation.', 'rrze-faudir') . '</p>';
    } else {
        $options = get_option('rrze_faudir_options');
        $apiKey = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo '<input type="text" name="rrze_faudir_options[api_key]" value="' . $apiKey . '" size="50">';
        echo '<p class="description">' . __('Enter your API key here.', 'rrze-faudir') . '</p>';
    }
}

function rrze_faudir_no_cache_logged_in_render() {
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['no_cache_logged_in']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[no_cache_logged_in]" value="1" ' . $checked . '>';
    echo '<p class="description">' . __('Disable caching for logged-in editors.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_timeout_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['cache_timeout']) ? intval($options['cache_timeout']) : 15;
    echo '<input type="number" name="rrze_faudir_options[cache_timeout]" value="' . $value . '" min="15">';
    echo '<p class="description">' . __('Set the cache timeout in minutes (minimum 15 minutes).', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_org_timeout_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['cache_org_timeout']) ? intval($options['cache_org_timeout']) : 1;
    echo '<input type="number" name="rrze_faudir_options[cache_org_timeout]" value="' . $value . '" min="1">';
    echo '<p class="description">' . __('Set the cache timeout in days for organization identifiers.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_clear_cache_render() {
    echo '<button type="button" class="button button-secondary" id="clear-cache-button">' . __('Clear Cache Now', 'rrze-faudir') . '</button>';
    echo '<p class="description">' . __('Click the button to clear all cached data.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_error_message_render() {
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['show_error_message']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[show_error_message]" value="1" ' . $checked . '>';
    echo '<p class="description">' . __('Show error messages for incorrect contact entries.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_business_card_title_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['business_card_title']) ? sanitize_text_field($options['business_card_title']) : __('Call up business card', 'rrze-faudir');
    echo '<input type="text" name="rrze_faudir_options[business_card_title]" value="' . $value . '" size="50">';
    echo '<p class="description">' . __('Enter the title for the business card link.', 'rrze-faudir') . '</p>';
}

// Settings page display
    function rrze_faudir_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('FAU Directory Settings', 'rrze-faudir')); ?></h1>
            
            <!-- Tabs Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-1" class="nav-tab nav-tab-active"><?php echo __('API Settings', 'rrze-faudir'); ?></a>
                <a href="#tab-2" class="nav-tab"><?php echo __('Cache Settings', 'rrze-faudir'); ?></a>
                <a href="#tab-3" class="nav-tab"><?php echo __('Error Handling', 'rrze-faudir'); ?></a>
                <a href="#tab-4" class="nav-tab"><?php echo __('Business Card Link', 'rrze-faudir'); ?></a>
                <a href="#tab-5" class="nav-tab"><?php echo __('Search Contacts', 'rrze-faudir'); ?></a>
            </h2>
    
            <!-- Tabs Content -->
            <form action="options.php" method="post">
                <?php settings_fields('rrze_faudir_settings'); ?>
                
                <!-- API Settings Tab -->
                <div id="tab-1" class="tab-content">
                    <?php do_settings_sections('rrze_faudir_settings'); ?>
                </div>
                
                <!-- Cache Settings Tab -->
                <div id="tab-2" class="tab-content" style="display:none;">
                    <?php do_settings_sections('rrze_faudir_settings_cache'); ?>
                </div>
                
                <!-- Error Handling Tab -->
                <div id="tab-3" class="tab-content" style="display:none;">
                    <?php do_settings_sections('rrze_faudir_settings_error'); ?>
                </div>
                
                <!-- Business Card Link Tab -->
                <div id="tab-4" class="tab-content" style="display:none;">
                    <?php do_settings_sections('rrze_faudir_settings_business_card'); ?>
                </div>
    
                <!-- Contacts Search Tab -->
                <div id="tab-5" class="tab-content" style="display:none;">
                    <h2><?php echo __('Search Contacts by Identifier', 'rrze-faudir'); ?></h2>
                    <form id="contacts-search-form">
                        <label for="contact-id"><?php echo __('Enter Identifier:', 'rrze-faudir'); ?></label>
                        <input type="text" id="contact-id" name="contact-id" />
                        <button type="button" id="search-contacts" class="button button-primary"><?php echo __('Search', 'rrze-faudir'); ?></button>
                    </form>
                    <div id="contacts-list">
                        <?php echo rrze_faudir_display_all_contacts(); ?>
                    </div>
                </div>
    
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.nav-tab').click(function(e) {
                    e.preventDefault();
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
    
                    $('.tab-content').hide();
                    $($(this).attr('href')).show();
                });
    
                $('#search-contacts').click(function() {
                    var identifier = $('#contact-id').val();
                    if (identifier.trim() === '') {
                        return; // Prevent empty searches
                    }
    
                    $.ajax({
                        url: rrze_faudir_ajax.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'rrze_faudir_search_contacts',
                            security: rrze_faudir_ajax.api_nonce,
                            identifier: identifier
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#contacts-list').html(response.data);
                            } else {
                                $('#contacts-list').html('<p>' + response.data + '</p>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX request failed:', status, error); // Log errors
                            $('#contacts-list').html('<p>An error occurred during the request.</p>');
                        }
                    });
                });
            });
        </script>
        <?php
    }
    function rrze_faudir_display_all_contacts() {
        $contacts_data = fetch_fau_persons(); // Fetch data from the FAU API
    
        if (is_string($contacts_data)) {
            return '<p>' . esc_html($contacts_data) . '</p>'; // Handle error message
        }
    
        $contacts = $contacts_data['data'] ?? []; // Adjust this line according to your API response format
    
        if (!empty($contacts)) {
            $output = '<div class="contacts-wrapper">';
            foreach ($contacts as $contact) {
                $name = esc_html($contact['personalTitle'] . ' ' . $contact['givenName'] . ' ' . $contact['familyName']);
                $identifier = esc_html($contact['identifier']);
                $output .= '<div class="contact-card">';
                $output .= "<h2 class='contact-name'>{$name}</h2>";
                $output .= "<p><strong>IdM-Kennung:</strong> {$identifier}</p>";
                $output .= "<h3>Contacts:</h3>";
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
        } else {
            $output = '<p>No contacts found.</p>';
        }
    
        return $output;
    }
    
    
    
    // AJAX handler for filtering contacts
    function rrze_faudir_search_contacts_handler() {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');
        
        $identifier = sanitize_text_field($_POST['identifier']);
        $contacts_data = fetch_fau_persons(); // Fetch all contacts from the API
    
        if (is_string($contacts_data)) {
            wp_send_json_error($contacts_data);
        }
    
        $contacts = $contacts_data['data'] ?? []; // Adjust this line according to your API response format
    
        // Filter contacts based on the identifier or name
        $filtered_contacts = array_filter($contacts, function($contact) use ($identifier) {
            $full_name = strtolower($contact['givenName'] . ' ' . $contact['familyName']);
            return stripos($contact['identifier'], $identifier) !== false ||
                   stripos($full_name, $identifier) !== false;
        });
    
        if (!empty($filtered_contacts)) {
            $output = '<div class="contacts-wrapper">';
            foreach ($filtered_contacts as $contact) {
                $name = esc_html($contact['personalTitle'] . ' ' . $contact['givenName'] . ' ' . $contact['familyName']);
                $identifier = esc_html($contact['identifier']);
                $output .= '<div class="contact-card">';
                $output .= "<h2 class='contact-name'>{$name}</h2>";
                $output .= "<p><strong>IdM-Kennung:</strong> {$identifier}</p>";
                $output .= "<h3>Contacts:</h3>";
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
            wp_send_json_error('No contacts found with the provided identifier.');
        }
    }
    
    add_action('wp_ajax_rrze_faudir_search_contacts', 'rrze_faudir_search_contacts_handler');
    


    function rrze_faudir_refresh_contacts_handler() {
        $contacts_data = fetch_fau_persons(); // Fetch all contacts from the API
    
        if (is_string($contacts_data)) {
            wp_send_json_error($contacts_data);
        }
    
        $contacts = $contacts_data['data'] ?? []; // Adjust this line according to your API response format
    
        if (!empty($contacts)) {
            $output = '<div class="contacts-wrapper">';
            foreach ($contacts as $contact) {
                $name = esc_html($contact['personalTitle'] . ' ' . $contact['givenName'] . ' ' . $contact['familyName']);
                $identifier = esc_html($contact['identifier']);
                $output .= '<div class="contact-card">';
                $output .= "<h2 class='contact-name'>{$name}</h2>";
                $output .= "<p><strong>IdM-Kennung:</strong> {$identifier}</p>";
                $output .= "<h3>Contacts:</h3>";
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
            wp_send_json_error('No contacts found.');
        }
    }
    
    add_action('wp_ajax_rrze_faudir_refresh_contacts', 'rrze_faudir_refresh_contacts_handler');
    
// Clear Cache Function
function rrze_faudir_clear_cache() {
    global $wpdb;
    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%_transient_rrze_faudir%'");
    wp_send_json_success(__('All cache cleared successfully.', 'rrze-faudir'));
}
