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

				// Get all available fields based on the selected format
				$format = $attributes['selectedFormat'] ?? 'kompakt';
				$all_format_fields = [
					'card' => [
						'display_name', 'academic_title', 'first_name', 'last_name', 
						'academic_suffix', 'email', 'phone', 'function', 'socialmedia'
					],
					'table' => [
						'display_name', 'academic_title', 'first_name', 'last_name', 
						'academic_suffix', 'email', 'phone', 'url', 'socialmedia'
					],
					'list' => [
						'display_name', 'academic_title', 'first_name', 'last_name', 
						'academic_suffix', 'email', 'phone', 'url', 'teasertext'
					],
					'kompakt' => [
						'display_name', 'academic_title', 'first_name', 'last_name', 
						'academic_suffix', 'email', 'phone', 'organization', 'function', 
						'url', 'kompaktButton', 'content', 'teasertext', 'socialmedia', 
						'workplaces', 'room', 'floor', 'street', 'zip', 'city', 
						'faumap', 'officehours', 'consultationhours'
					],
					'page' => [
						'display_name', 'academic_title', 'first_name', 'last_name', 
						'academic_suffix', 'email', 'phone', 'organization', 'function', 
						'url', 'kompaktButton', 'content', 'teasertext', 'socialmedia', 
						'workplaces', 'room', 'floor', 'street', 'zip', 'city', 
						'faumap', 'officehours', 'consultationhours'
					]
				];

				// Get all available fields for the selected format
				$available_fields = $all_format_fields[$format] ?? $all_format_fields['kompakt'];

				// Get selected fields
				$selected_fields = $attributes['selectedFields'] ?? [];

				// Calculate fields to hide (available fields that aren't selected)
				$hide_fields = array_values(array_diff($available_fields, $selected_fields));

				// Build shortcode attributes
				$shortcode_atts = [
					'identifier' => $identifier,
					'format' => $format
				];

				// Add show fields if any are selected
				if (!empty($selected_fields)) {
					$shortcode_atts['show'] = implode(',', $selected_fields);
				}

				// Add hide fields if any are unselected
				if (!empty($hide_fields)) {
					$shortcode_atts['hide'] = implode(',', $hide_fields);
				}

				// Add optional attributes
				if (!empty($attributes['buttonText'])) {
					$shortcode_atts['button-text'] = $attributes['buttonText'];
				}
				if (!empty($attributes['selectedCategory'])) {
					$shortcode_atts['category'] = $attributes['selectedCategory'];
				}
				if (!empty($attributes['groupId'])) {
					$shortcode_atts['groupid'] = $attributes['groupId'];
				}
				if (!empty($attributes['functionField'])) {
					$shortcode_atts['function'] = $attributes['functionField'];
				}
				if (!empty($attributes['organizationNr'])) {
					$shortcode_atts['orgnr'] = $attributes['organizationNr'];
				}
				if (!empty($attributes['url'])) {
					$shortcode_atts['url'] = $attributes['url'];
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

				return sprintf(' ', $output);

			} catch (Exception $e) {
				error_log('FAUDIR Block Error: ' . $e->getMessage());
				return sprintf(
					'<div class="faudir-error" style="padding: 20px; border: 1px solid #dc3545; background-color: #f8d7da; color: #721c24;">
						<p><strong>Error:</strong> %s</p>
						<details>
							<summary>Debug Information</summary>
							<pre>Shortcode: %s</pre>
							<pre>Attributes: %s</pre>
						</details>
					</div>',
					esc_html($e->getMessage()),
					esc_html($shortcode ?? 'Not generated'),
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
