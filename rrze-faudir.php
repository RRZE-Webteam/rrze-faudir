<?php
/*
Plugin Name: rrze-faudir
Description: Plugin for displaying the FAU person and institution directory on websites.
Version: 1.0.0
Author: Your Name
Text Domain: rrze-faudir
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin textdomain for translations
function rrze_faudir_load_textdomain()
{
    load_plugin_textdomain('rrze-faudir', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'rrze_faudir_load_textdomain');

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'src/includes/shortcodes/fau_dir_shortcode.php';
require_once plugin_dir_path(__FILE__) . 'src/includes/blocks/fau_dir_block.php';
require_once plugin_dir_path(__FILE__) . 'src/includes/utils/enqueue_scripts.php';
require_once plugin_dir_path(__FILE__) . 'src/includes/utils/faudir_utils.php';

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
        $options = get_option('rrze_faudir_options');
        $apiKey = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo '<input type="text" name="rrze_faudir_options[api_key]" value="' . $apiKey . '" size="50">';
        echo '<p class="description">' . __('Enter your API key here.', 'rrze-faudir') . '</p>';
    }
}

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
    </div>
<?php
}
