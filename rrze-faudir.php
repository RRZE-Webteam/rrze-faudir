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
require_once plugin_dir_path(__FILE__) . 'src/includes/utils/api-functions.php';

// Include the new settings page file
require_once plugin_dir_path(__FILE__) . 'src/includes/admin/settings-page.php';

// Register and enqueue scripts
EnqueueScripts::register();
