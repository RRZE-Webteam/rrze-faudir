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
	wp_register_script(
        'rrze-faudir-block-script',
        plugins_url('faudir-block/build/index.js', __FILE__),
        ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'],
        filemtime(plugin_dir_path(__FILE__) . 'faudir-block/build/index.js'),
        true
    );

    wp_register_style(
        'rrze-faudir-block-style',
        plugins_url('faudir-block/build/style.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'faudir-block/build/style.css')
    );
    wp_set_script_translations('rrze-faudir-block-script', 'rrze-faudir', plugin_dir_path(__FILE__) . '../languages');
    wp_enqueue_script('rrze-faudir-block-script');
	wp_enqueue_style('faudir-editor-styles');

});
add_filter( 'block_categories_all' , function( $categories ) {

    // Adding a new category.
	$categories[] = array(
		'slug'  => 'custom-fau-category',
		'title' => 'Fau'
	);

	return $categories;
} );
