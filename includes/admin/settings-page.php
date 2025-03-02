<?php


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Config;

// Add admin menu
function rrze_faudir_add_admin_menu() {
    add_options_page(
        __('FAUdir Settings', 'rrze-faudir'),
        __('FAUdir', 'rrze-faudir'),
        'manage_options',
        'rrze-faudir',
        'rrze_faudir_settings_page'
    );
}
add_action('admin_menu', 'rrze_faudir_add_admin_menu');


function rrze_faudir_settings_init() {
    // Load the default settings
    $config = new Config;
    $default_settings = $config->getAll();
    
    $options = get_option('rrze_faudir_options', []);

    // Merge the default settings with the saved options
    $settings = wp_parse_args($options, $default_settings);
    update_option('rrze_faudir_options', $settings);

    register_setting(
        'rrze_faudir_settings', 
        'rrze_faudir_options',
        array(
            'sanitize_callback' => 'rrze_faudir_sanitize_options'
        )
    );

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
    add_settings_field(
        'rrze_faudir_person_slug',
        __('Person Slug', 'rrze-faudir'),
        'rrze_faudir_person_slug_field',
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
        'rrze_faudir_transient_time_for_org_id',
        __('Transient Time for Organization ID (in days)', 'rrze-faudir'),
        'rrze_faudir_transient_time_for_org_id_render',
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
        __('Kompakt Card Button Title', 'rrze-faudir'),
        'rrze_faudir_business_card_title_render',
        'rrze_faudir_settings_business_card',
        'rrze_faudir_business_card_section'
    );
    add_settings_field(
        'rrze_faudir_hard_sanitize',
        __('Hard Sanitize', 'rrze-faudir'),
        'rrze_faudir_hard_sanitize_render',
        'rrze_faudir_settings_business_card',
        'rrze_faudir_business_card_section'
    );

    // Contacts Search Section
    add_settings_section(
        'rrze_faudir_contacts_search_section',
        __('Search Contacts', 'rrze-faudir'),
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
    add_settings_field(
        'rrze_faudir_default_output_fields',
        __('Default Output Fields', 'rrze-faudir'),
        'rrze_faudir_default_output_fields_render',
        'rrze_faudir_settings_shortcode',
        'rrze_faudir_shortcode_section'
    );

    // Add Organization Search Section
    add_settings_section(
        'rrze_faudir_org_search_section',
        __('Search Organizations', 'rrze-faudir'),
        'rrze_faudir_org_search_section_callback',
        'rrze_faudir_settings_org_search'
    );

    // Note: The search form will be handled in the settings page itself, no need for a settings field here.


}
add_action('admin_init', 'rrze_faudir_settings_init');



// Callback functions
function rrze_faudir_api_section_callback() {
    echo '<p>' . esc_html__('Configure the API settings for accessing the FAU person and institution directory.', 'rrze-faudir') . '</p>';
}


function rrze_faudir_cache_section_callback() {
    echo '<p>' . esc_html__('Configure caching settings for the plugin.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_error_section_callback() {
    echo '<p>' . esc_html__('Handle error messages for invalid contact entries.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_business_card_section_callback() {
    echo '<p>' . esc_html__('Configure the business card link settings.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_shortcode_section_callback() {
    echo '<p>' . esc_html__('Configure the shortcode settings.', 'rrze-faudir') . '</p>';
}

// Render functions
function rrze_faudir_api_key_render() {
    if (FaudirUtils::isUsingNetworkKey()) {
        echo '<p>' . esc_html__('The API key is being used from the network installation.', 'rrze-faudir') . '</p>';
    } else {
        $options = get_option('rrze_faudir_options');
        $apiKey = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo '<input type="text" name="rrze_faudir_options[api_key]" value="' .  esc_attr($apiKey) . '" size="50">';
        echo '<p class="description">' . esc_html__('Enter your API key here.', 'rrze-faudir') . '</p>';
    }
}

function rrze_faudir_no_cache_logged_in_render() {
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['no_cache_logged_in']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[no_cache_logged_in]" value="1" ' .  esc_attr($checked) . '>';
    echo '<p class="description">' . esc_html__('Disable caching for logged-in editors.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_timeout_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['cache_timeout']) ? max(intval($options['cache_timeout']), 15) : 15; // Ensure minimum value is 15
    echo '<input type="number" name="rrze_faudir_options[cache_timeout]" value="' . esc_attr($value) . '" min="15">';
    echo '<p class="description">' . esc_html__('Set the cache timeout in minutes (minimum 15 minutes).', 'rrze-faudir') . '</p>';
}

function rrze_faudir_transient_time_for_org_id_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['transient_time_for_org_id']) ? max(intval($options['transient_time_for_org_id']), 1) : 1; // Ensure minimum value is 1
    echo '<input type="number" name="rrze_faudir_options[transient_time_for_org_id]" value="' . esc_attr($value) . '" min="1">';
    echo '<p class="description">' . esc_html__('Set the transient time in days for intermediate stored organization identifiers (minimum 1 day).', 'rrze-faudir') . '</p>';
}

function rrze_faudir_cache_org_timeout_render() {
    $options = get_option('rrze_faudir_options');
    $value = isset($options['cache_org_timeout']) ? intval($options['cache_org_timeout']) : 1;
    echo '<input type="number" name="rrze_faudir_options[cache_org_timeout]" value="' . esc_attr($value) . '" min="1">';
    echo '<p class="description">' . esc_html__('Set the cache timeout in days for organization identifiers.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_clear_cache_render() {
    echo '<button type="button" class="button button-secondary" id="clear-cache-button">' . esc_html__('Clear Cache Now', 'rrze-faudir') . '</button>';
    echo '<p class="description">' . esc_html__('Click the button to clear all cached data.', 'rrze-faudir') . '</p>';
}


function rrze_faudir_error_message_render() {
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['show_error_message']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[show_error_message]" value="1" ' . esc_attr($checked) . '>';
    echo '<p class="description">' . esc_html__('Show error messages for incorrect contact entries.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_business_card_title_render() {
    $options = get_option('rrze_faudir_options');
    $default_title = esc_html__('More', 'rrze-faudir');
    $value = isset($options['business_card_title']) && !empty($options['business_card_title'])
        ? sanitize_text_field($options['business_card_title'])
        : $default_title;

    // Save the default value if it's not set
    if (!isset($options['business_card_title']) || empty($options['business_card_title'])) {
        $options['business_card_title'] = $default_title;
        update_option('rrze_faudir_options', $options);
    }

    echo '<input type="text" name="rrze_faudir_options[business_card_title]" value="' . esc_attr($value) . '" size="50">';
    echo '<p class="description">' . esc_html__('Enter the title for the kompakt card read more button.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_hard_sanitize_render() {
    $options = get_option('rrze_faudir_options');
    $checked = isset($options['hard_sanitize']) ? 'checked' : '';
    echo '<input type="checkbox" name="rrze_faudir_options[hard_sanitize]" value="1" ' . esc_attr($checked) . '>';
    echo '<p class="description">' . esc_html__('Hard Sanitize abbreviations.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_person_slug_field() {
    $options = get_option('rrze_faudir_options'); // Get all plugin options
    $default_slug = 'person'; // Default slug value

    // Retrieve the saved slug or use the default if not set
    $slug = isset($options['person_slug']) && !empty($options['person_slug'])
        ? sanitize_text_field($options['person_slug'])
        : $default_slug;

    // Save the default slug if not already set
    if (!isset($options['person_slug']) || empty($options['person_slug'])) {
        $options['person_slug'] = $default_slug;
        update_option('rrze_faudir_options', $options);
    }

    echo '<input type="text" id="rrze_faudir_person_slug" name="rrze_faudir_options[person_slug]" value="' . esc_attr($slug) . '" size="50">';
    echo '<p class="description">' . esc_html__('Enter the slug for the person post type.', 'rrze-faudir') . '</p>';
}

function rrze_faudir_default_output_fields_render() {
    $options = get_option('rrze_faudir_options');
    $default_fields = isset($options['default_output_fields']) ? $options['default_output_fields'] : array();

        $config = new Config;
        $available_fields = $config->get('avaible_fields');
        $fieldlist = $config->getAvaibleFieldlist();

    // Set default state: all checkboxes checked if no selection exists
    if (empty($default_fields)) {
        $default_fields = array_keys($available_fields); // Use all available fields by default
    }

    echo '<fieldset>';
    foreach ($available_fields as $field => $label) {
        $checked = in_array($field, $default_fields); // Check if the field is in the default fields array
        echo "<label for='" . esc_attr('rrze_faudir_default_output_fields_' . $field) . "'>";
        echo "<input type='checkbox' id='" . esc_attr('rrze_faudir_default_output_fields_' . $field) . "' name='rrze_faudir_options[default_output_fields][]' value='" . esc_attr($field) . "' " . checked($checked, true, false) . ">";
        echo esc_html($label) . "</label>";
        $missing = '';
        foreach ($fieldlist as $fl => $entries) {
            if (isset($fieldlist[$fl]) && in_array($field, $fieldlist[$fl], true)) {
                // can use
            } else {
                if (!empty($missing)) {
                    $missing .= ', ';
                }
                $missing .= $fl;
            }
        }
        if (!empty($missing)) {
            echo ' <em>('.__('Not avaible for the following formats', 'rrze_faudir').': '.$missing.')</em>';
        }
        echo "<br>";
        
    }
    echo '</fieldset>';
    echo '<p class="description">' . esc_html__('Select the fields to display by default in shortcodes and blocks.', 'rrze-faudir') . '</p>';
}

// Add the callback function
function rrze_faudir_org_search_section_callback() {
    echo '<p>' . esc_html__('Search for FAU organizations by name or identifier.', 'rrze-faudir') . '</p>';
}

// Settings page display
function rrze_faudir_settings_page() {
?>
    <div class="wrap faudir-settings">
        <h1>
            <?php echo esc_html(__('FAUdir Settings', 'rrze-faudir')); ?>
        </h1>

        <!-- Tabs Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="#tab-1" class="nav-tab nav-tab-active">
                <?php echo esc_html__('API Settings', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-2" class="nav-tab">
                <?php echo esc_html__('Cache Settings', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-3" class="nav-tab">
                <?php echo esc_html__('Error Handling', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-4" class="nav-tab">
                <?php echo esc_html__('Kompakt Card Button', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-5" class="nav-tab">
                <?php echo esc_html__('Search Contacts', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-8" class="nav-tab">
                <?php echo esc_html__('Search Organizations', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-6" class="nav-tab">
                <?php echo esc_html__('Shortcode Settings', 'rrze-faudir'); ?>
            </a>
            <a href="#tab-7" class="nav-tab">
                <?php echo esc_html__('Reset Settings', 'rrze-faudir'); ?>
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

            <!-- Shortcode Settings Tab -->
            <div id="tab-6" class="tab-content" style="display:none;">
                <?php do_settings_sections('rrze_faudir_settings_shortcode'); ?>
                <?php submit_button(); ?>
            </div>

            <!-- Reset Settings Tab -->
            <div id="tab-7" class="tab-content" style="display:none;">
                <h3><?php echo esc_html__('Reset to Default Settings', 'rrze-faudir'); ?></h3>
                <p><?php echo esc_html__('Click the button below to reset all settings to their default values.', 'rrze-faudir'); ?></p>
                <button type="button" class="button button-secondary" id="reset-to-defaults-button">
                    <?php echo esc_html__('Reset to Default Values', 'rrze-faudir'); ?>
                </button>
            </div>
        </form>

        <!-- Contacts Search Tab -->
        <div id="tab-5" class="tab-content" style="display:none;">
            <h2>
                <?php echo esc_html__('Search Contacts', 'rrze-faudir'); ?>
            </h2>

            <form id="search-person-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="rrze_faudir_search_person">
                <?php wp_nonce_field('rrze_faudir_search_person', 'rrze_faudir_search_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <p class="description">
                                    <?php echo esc_html__('Please enter at least one search term. If more than one parameter is entered, the search results must contain all values (AND search).', 'rrze-faudir'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Search Options', 'rrze-faudir'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="include-default-org">
                                        <input type="checkbox" id="include-default-org" name="include-default-org" value="1" checked>
                                        <span><?php echo esc_html__('Filter by default organization', 'rrze-faudir'); ?></span>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="person-id"><?php echo esc_html__('Search Terms', 'rrze-faudir'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label for="person-id"><?php echo esc_html__('API-Person-Identifier', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="person-id" name="person-id" class="regular-text" />
                                    </p>
                                    <br>
                                    <p>
                                        <label for="given-name"><?php echo esc_html__('Given Name', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="given-name" name="given-name" class="regular-text" />
                                    </p>
                                    <br>
                                    <p>
                                        <label for="family-name"><?php echo esc_html__('Family Name', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="family-name" name="family-name" class="regular-text" />
                                    </p>
                                    <br>
                                    <p>
                                        <label for="email"><?php echo esc_html__('Email', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="email" name="email" class="regular-text" />
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" disabled><?php echo esc_html__('Search', 'rrze-faudir') ?></button>
            </form>

            <div id="contacts-list">
                <?php
                if (isset($_GET['search_results'])) {
                    echo wp_kses_post(urldecode($_GET['search_results']));
                }
                ?>
            </div>
        </div>

        <!-- Organizations Search Tab -->
        <div id="tab-8" class="tab-content" style="display:none;">
            <!-- Default Organization -->
            <?php
            $default_org = get_option('rrze_faudir_options', array())['default_organization'] ?? null;
            if ($default_org) {
            ?>
                <div id="default-organization">
                    <h2><?php echo esc_html__('Current Default Organization', 'rrze-faudir'); ?></h2>
                    <p><?php echo esc_html__('This is the organization that will be used by default in shortcodes and blocks.', 'rrze-faudir'); ?></p>
                    <p><strong><?php echo esc_html(__('Name', 'rrze-faudir')); ?>:</strong> <?php echo esc_html($default_org['name']); ?></p>
                    <p><strong><?php echo esc_html(__('Organization Number', 'rrze-faudir')); ?>:</strong> <?php echo esc_html($default_org['orgnr']); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                        <?php wp_nonce_field('delete_default_organization'); ?>
                        <input type="hidden" name="action" value="delete_default_organization">
                        <button type="submit" class="button button-secondary" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete the default organization?', 'rrze-faudir')); ?>');">
                            <?php echo esc_html__('Delete Default Organization', 'rrze-faudir'); ?>
                        </button>
                    </form>
                </div>
            <?php
            }
            ?>
            <h2><?php echo esc_html__('Search Organizations', 'rrze-faudir'); ?></h2>

            <form id="search-org-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="rrze_faudir_search_org">
                <?php wp_nonce_field('rrze_faudir_search_org', 'rrze_faudir_search_org_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="org-search"><?php echo esc_html__('Search Term', 'rrze-faudir'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="org-search" name="org-search" class="regular-text" />
                            <p class="description"><?php echo esc_html__('Enter organization name or identifier', 'rrze-faudir'); ?></p>
                        </td>
                    </tr>
                </table>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Search', 'rrze-faudir') ?></button>
            </form>

            <div id="organizations-list">
                <?php
                if (isset($_GET['org_search_results'])) {
                    echo wp_kses_post(urldecode($_GET['org_search_results']));
                }
                ?>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show and hide tabs
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                $($(this).attr('href')).show();
            });

            // Handle reset button click
            $('#reset-to-defaults-button').on('click', function() {
                if (confirm('<?php echo esc_js(esc_html__('Are you sure you want to reset all settings to their default values?', 'rrze-faudir')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'rrze_faudir_reset_defaults',
                        security: '<?php echo esc_js(wp_create_nonce('rrze_faudir_reset_defaults_nonce')); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php echo esc_js(esc_html__('Settings have been reset to default values.', 'rrze-faudir')); ?>');
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(esc_html__('Failed to reset settings. Please try again.', 'rrze-faudir')); ?>');
                        }
                    });
                }
            });
        });
    </script>
<?php
}

function rrze_faudir_delete_default_organization() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'rrze-faudir'));
    }

    check_admin_referer('delete_default_organization');

    $options = get_option('rrze_faudir_options', array());
    if (isset($options['default_organization'])) {
        unset($options['default_organization']);
        // Explicitly set default_organization to null to signal intentional removal
        $options['default_organization'] = null;
        update_option('rrze_faudir_options', $options);
        add_settings_error(
            'rrze_faudir_messages',
            'default_org_deleted',
            __('Default organization has been deleted.', 'rrze-faudir'),
            'updated'
        );
    }

    wp_redirect(add_query_arg(
        array('page' => 'rrze-faudir', 'settings-updated' => 'true'),
        admin_url('options-general.php')
    ));
    exit;
}
add_action('admin_post_delete_default_organization', 'rrze_faudir_delete_default_organization');

// Function to reset settings to defaults
function rrze_faudir_reset_defaults() {
    check_ajax_referer('rrze_faudir_reset_defaults_nonce', 'security');

    $config = new Config;
    $default_settings = $config->getAll();
    
    // Update the plugin options with the default values
    update_option('rrze_faudir_options', $default_settings);

    wp_send_json_success(__('Settings have been reset to default values.', 'rrze-faudir'));
}
add_action('wp_ajax_rrze_faudir_reset_defaults', 'rrze_faudir_reset_defaults');


// Clear Cache Function
function rrze_faudir_clear_cache()
{
    global $wpdb;
    // Delete all transients related to the plugin's cache
    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%_transient_rrze_faudir%'");

    wp_send_json_success(__('All cache cleared successfully.', 'rrze-faudir'));
}
add_action('wp_ajax_rrze_faudir_clear_cache', 'rrze_faudir_clear_cache');

// Add this function at the end of the file
function rrze_faudir_search_person_ajax() {
    check_ajax_referer('rrze_faudir_api_nonce', 'security');

    $personId = isset($_POST['person_id']) ? sanitize_text_field($_POST['person_id']) : '';
    $givenName = isset($_POST['given_name']) ? sanitize_text_field($_POST['given_name']) : '';
    $familyName = isset($_POST['family_name']) ? sanitize_text_field($_POST['family_name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $includeDefaultOrg = isset($_POST['include_default_org']) && $_POST['include_default_org'] === '1';
    // error_log('$includeDefaultOrg: ' . $includeDefaultOrg);

    // get default organization
    $defaultOrg = get_option('rrze_faudir_options', array())['default_organization'] ?? null;
    // get identifier of default organization
    $defaultOrgIds = $defaultOrg ? $defaultOrg['ids'] : [];

    $queryParts = [];

    if (!empty($personId)) {
        $queryParts[] = 'identifier=' . $personId;
    }
    if (!empty($givenName)) {
        $queryParts[] = 'givenName[ireg]=' . $givenName;
    }
    if (!empty($familyName)) {
        $queryParts[] = 'familyName[ireg]=' . $familyName;
    }
    if (!empty($email)) {
        // search for contacts with the email
        $response = fetch_fau_contacts(1, 0, ['lq' => 'workplaces.mails[ireg]=' . $email]);
        // error_log('Response: ' . print_r($response, true));
        // if no contact is found, search the persons
        if (empty($response['data'])) {
            $queryParts[] = 'email[ireg]=' . $email;
        } else {
            // get the person id from the contact's person object
            $personId = $response['data'][0]['person']['identifier'];
            $queryParts[] = 'identifier=' . $personId;
        }
    }
    if ($includeDefaultOrg && !empty($defaultOrgIds)) {
        $queryParts[] = 'contacts.organization.identifier[reg]=^(' . implode('|', $defaultOrgIds) . ')$';
    }

    $params = [
        'lq' => implode('&', $queryParts)
    ];

    $response = fetch_fau_persons(60, 0, $params);

    foreach ($response['data'] as $key => $person) {
        $response['data'][$key]['contacts'] = FaudirUtils::filterContactsByCriteria(
            $person['contacts'],
            $includeDefaultOrg,
            $defaultOrgIds,
            $email
        );
    }

    if (is_string($response)) {
        wp_send_json_error(sprintf(__('Error: %s', 'rrze-faudir'), $response));
    } else {
        $contacts = $response['data'] ?? [];
        if (!empty($contacts)) {
            $output = '<div class="contacts-wrapper">';
            foreach ($contacts as $contact) {
                $personalTitle = isset($contact['personalTitle']) ? $contact['personalTitle'] . ' ' : '';
                $name = esc_html($personalTitle . $contact['givenName'] . ' ' . $contact['familyName']);
                $identifier = esc_html($contact['identifier']);
                $output .= '<div class="contact-card">';
                $output .= "<h2 class='contact-name'>{$name}</h2>";
                $output .= "<div class='contact-details'>";
                $output .= "<p><strong>API-Person-Identifier:</strong> {$identifier}</p>";
                if (isset($contact['email']) && !empty($contact['email'])) {
                    $output .= "<p><strong>Email:</strong> " . esc_html($contact['email']) . "</p>";
                }
                if (!empty($contact['contacts'])) {
                    foreach ($contact['contacts'] as $contactDetail) {
                        $orgName = esc_html($contactDetail['organization']['name']);
                        $functionLabel = esc_html($contactDetail['functionLabel']['en']);
                        $output .= "<p><strong>".__('Organization','rrze-faudir').":</strong> {$orgName} ({$functionLabel})</p>";
                    }
                }
                // Check if a post already exists with this identifier
                $existing_post = get_posts(array(
                    'post_type' => 'custom_person',
                    'meta_key' => 'person_id',
                    'meta_value' => $identifier,
                    'posts_per_page' => 1
                ));


                $output .= "</div>";
                if (!empty($existing_post)) {
                    // Post exists, show edit link
                    $edit_link = get_edit_post_link($existing_post[0]->ID);
                    $output .= "<a href='" . esc_url($edit_link) . "' class='edit-person button'><span class='dashicons dashicons-edit'></span> " . esc_html__('Edit', 'rrze-faudir') . "</a>";
                } else {
                    // Post doesn't exist, show add button
                    $output .= "<button class='add-person button' data-name='" . esc_attr($name) . "' data-id='" . esc_attr($identifier) . "' data-include-default-org='" . $includeDefaultOrg . "'><span class='dashicons dashicons-plus'></span> Add</button>";
                }
                $output .= '</div>';
            }
            $output .= '</div>';
            wp_send_json_success($output);
        } else {
            wp_send_json_error(__('No contacts found. Please verify the IdM-Kennung or names provided.', 'rrze-faudir'));
        }
    }
}
add_action('wp_ajax_rrze_faudir_search_person', 'rrze_faudir_search_person_ajax');

// Add this new function
function rrze_faudir_sanitize_options($new_options) {
    // Get existing options
    $existing_options = get_option('rrze_faudir_options', array());
    
    // Only preserve default_organization if it exists in current options
    // AND if it's not being intentionally removed (check if it exists in new_options)
    if (isset($existing_options['default_organization']) && !array_key_exists('default_organization', $new_options)) {
        $new_options['default_organization'] = $existing_options['default_organization'];
    }
    
    return $new_options;
}
