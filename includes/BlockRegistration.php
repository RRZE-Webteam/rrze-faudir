<?php

namespace RRZE\FAUdir;
defined('ABSPATH') || exit;

use Exception;

/**
 * Handles Block Registration, Localization and Rendering of the FAUdir Block.
 */
class BlockRegistration
{
    public function __construct()
    {
        add_action('init', [$this, 'rrze_faudir_block_init']);
        add_filter('block_categories_all', [$this, 'register_rrze_block_category'], 10, 2);
    }

    /**
     * Register the FAUdir Block and initialize the l10n.
     */
    public function rrze_faudir_block_init(): void
    {
        $this->rrze_register_blocks();
        //$this->rrze_register_translations();
    }

    /**
     * Register the FAUdir block for the BlockEditor
     */
    public function rrze_register_blocks(): void
    {
        register_block_type(plugin_dir_path(__DIR__) . 'build/block', [
            'render_callback' => [$this, 'render_faudir_block'],
            'skip_inner_blocks' => true
        ]);
    }


    /**
     * Adds custom block category if not already present.
     *
     * @param array $categories Existing block categories.
     * @param WP_Post $post Current post object.
     * @return array Modified block categories.
     */
    public static function register_rrze_block_category($categories, $post)
    {
        // Check if there is already a RRZE category present
        foreach ($categories as $category) {
            if (isset($category['slug']) && $category['slug'] === 'rrze') {
                return $categories;
            }
        }

        $custom_category = [
            'slug' => 'fau',
            'title' => __('FAU', 'rrze-bluesky'),
        ];

        $categories[] = $custom_category;

        return $categories;
    }

    /**
     * Render and Process the dynamic FAUdir Block
     * @param $attributes
     * @return string The Shortcode Output | An error message if no shortcode is present.
     */
    public static function render_faudir_block($attributes): string
    {
        error_log('FAUDIR Block attributes: ' . print_r($attributes, true));


        try {
            error_log('FAUDIR Block render started with attributes: ' . print_r($attributes, true));

            if (!shortcode_exists('faudir')) {
                throw new Exception('FAUDIR shortcode is not registered');
            }

            // Get default organization from options with proper checks
            $options = get_option('rrze_faudir_options', []);
            $default_org = isset($options['default_organization']) && is_array($options['default_organization'])
                ? $options['default_organization']
                : [];
            $defaultOrgIdentifier = isset($default_org['id']) ? $default_org['id'] : '';

            // First check if we have function and orgnr
            if (!empty($attributes['role'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'role' => $attributes['role'],
                    'orgnr' => !empty($attributes['orgnr']) ? $attributes['orgnr'] : $defaultOrgIdentifier
                ];
            } // Then check for category
            else if (!empty($attributes['selectedCategory'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'category' => $attributes['selectedCategory']
                ];

                // Only add identifiers if they're specifically selected for this category
                if (!empty($attributes['selectedPersonIds'])) {
                    $shortcode_atts['identifier'] = implode(',', $attributes['selectedPersonIds']);
                }
            } // Finally check for selectedPersonIds without category
            else if (!empty($attributes['selectedPersonIds'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'identifier' => is_array($attributes['selectedPersonIds']) ?
                        implode(',', $attributes['selectedPersonIds']) :
                        $attributes['selectedPersonIds']
                ];
            } // Org without other parameters from above given
            else if (!empty($attributes['orgid'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'orgid' => $attributes['orgid']
                ];
            }
            else if (!empty($attributes['orgnr'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'orgnr' => $attributes['orgnr']
                ];
            } else {
                throw new Exception(__('Neither person IDs, function+orgnr, nor category were provided', 'rrze-faudir'));
            }

            // Add optional attributes
            if (!empty($attributes['selectedFields'])) {
                $shortcode_atts['show'] = implode(',', $attributes['selectedFields']);
            }

            if (!empty($attributes['hideFields'])) {
                $shortcode_atts['hide'] = implode(',', $attributes['hideFields']);
            }

            if (!empty($attributes['url'])) {
                $shortcode_atts['url'] = $attributes['url'];
            }
            if (!empty($attributes['format_displayname'])) {
                $shortcode_atts['format_displayname'] = $attributes['format_displayname'];
            }

            if (!empty($attributes['sort'])) {
                $shortcode_atts['sort'] = $attributes['sort'];
            }

            if (!empty($attributes['display'])) {
                $shortcode_atts['display'] = $attributes['display'];
            }

            // Build shortcode string
            $shortcode = '[faudir';
            foreach ($shortcode_atts as $key => $value) {
                if (!empty($value)) {
                    $shortcode .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
                }
            }
            $shortcode .= ']';

            // Execute shortcode
            $output = do_shortcode($shortcode);

            if (empty(trim($output))) {
                throw new Exception('Shortcode returned empty content');
            }

            return $output;

        } catch (Exception $e) {
            return sprintf(
                '<div class="faudir-error">%s</div>',
                esc_html($e->getMessage())
            );
        }
    }
}