<?php
/**
 * Plugin Name: Block Faudir
 * Description: FAUDIR Block Integration
 * Version: 1.0.0
 * Author: RRZE
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: block-faudir
 */

if (!defined('ABSPATH')) {
	exit;
}

function rrze_faudir_block_init() {
	if (!function_exists('register_block_type')) {
		return;
	}

}

add_action('init', 'rrze_faudir_block_init');

// Ensure styles are loaded in the editor
add_action('enqueue_block_editor_assets', function() {
	wp_enqueue_style('faudir-editor-styles');
});
