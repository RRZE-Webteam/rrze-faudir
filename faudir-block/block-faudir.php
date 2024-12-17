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

	register_block_type(__DIR__ . '/build', [
		'render_callback' => function($attributes) {
			try {
				error_log('FAUDIR Block render started with attributes: ' . print_r($attributes, true));

				// Check if shortcode exists
				if (!shortcode_exists('faudir')) {
					throw new Exception('FAUDIR shortcode is not registered');
				}

				// Get identifier from attributes
				$identifier = isset($attributes['selectedPersonIds']) ? 
					(is_array($attributes['selectedPersonIds']) ? implode(',', $attributes['selectedPersonIds']) : $attributes['selectedPersonIds']) : '';

				if (empty($identifier)) {
					throw new Exception('No person IDs provided');
				}

				// Build shortcode attributes
				$shortcode_atts = [
					'identifier' => $identifier,
					'format' => $attributes['selectedFormat'] ?? 'kompakt',
					'show' => isset($attributes['selectedFields']) ? implode(',', $attributes['selectedFields']) : '',
				];

				// Add optional attributes
				$optional_pairs = [
					'category' => 'selectedCategory',
					'groupid' => 'groupId',
					'function' => 'functionField',
					'orgnr' => 'organizationNr',
					'url' => 'url'
				];

				foreach ($optional_pairs as $shortcode_key => $attr_key) {
					if (!empty($attributes[$attr_key])) {
						$shortcode_atts[$shortcode_key] = $attributes[$attr_key];
					}
				}

				// Build shortcode string
				$shortcode = '[faudir';
				foreach ($shortcode_atts as $key => $value) {
					if (!empty($value)) {
						$shortcode .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
					}
				}
				$shortcode .= ']';

				error_log('Generated shortcode: ' . $shortcode);

				// Execute shortcode
				$output = do_shortcode($shortcode);

				if (empty(trim($output))) {
					throw new Exception('Shortcode returned empty content');
				}

				return sprintf('<div class="wp-block-rrze-faudir-block">%s</div>', $output);

			} catch (Exception $e) {
				error_log('FAUDIR Block Error: ' . $e->getMessage());
				return sprintf(
					'<div class="faudir-error" style="padding: 20px; border: 1px solid #dc3545; background-color: #f8d7da; color: #721c24;">
						<p><strong>Error:</strong> %s</p>
						<details>
							<summary>Debug Information</summary>
							<pre>%s</pre>
						</details>
					</div>',
					esc_html($e->getMessage()),
					esc_html(print_r($attributes, true))
				);
			}
		}
	]);
}

add_action('init', 'rrze_faudir_block_init');

// Add admin notice if parent plugin is missing
add_action('admin_init', function() {
	if (!shortcode_exists('faudir')) {
		add_action('admin_notices', function() {
			echo '<div class="error"><p>The FAUDIR Block requires the RRZE-FAUDIR plugin to be installed and activated.</p></div>';
		});
	}
});

// Ensure styles are loaded in the editor
add_action('enqueue_block_editor_assets', function() {
	wp_enqueue_style('faudir-editor-styles');
});
