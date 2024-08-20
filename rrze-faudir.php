<?php
/*
Plugin Name: rrze-faudir
Description: Plugin zur Darstellung des Personen- und Einrichtungsverzeichnis der FAU in Websites

Version: 1.0.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'src/includes/shortcodes/fau_dir_shortcode.php';
require_once plugin_dir_path(__FILE__) . 'src/includes/blocks/fau_dir_block.php';
require_once plugin_dir_path(__FILE__) . 'src/includes/utils/enqueue_scripts.php';

// Register components
FaudirShortcode::register();
FaudirBlock::register();
EnqueueScripts::register();
