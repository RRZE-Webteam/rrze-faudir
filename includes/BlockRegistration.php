<?php

namespace RRZE\FAUdir;
defined('ABSPATH') || exit;

use Exception;

/**
 * Handles Block Registration, Localization and Rendering of the FAUdir Block.
 */
class BlockRegistration {
    public function __construct() {
        add_action('init', [$this, 'rrze_faudir_block_init'], 15);
        add_filter('block_categories_all', [$this, 'register_rrze_block_category'], 10, 2);
    }

    /**
     * Register the FAUdir Block and initialize the l10n.
     */
    public function rrze_faudir_block_init(): void {
        $this->rrze_register_blocks();
        //$this->rrze_register_translations();
    }

    /**
     * Register the FAUdir block for the BlockEditor
     */
    public function rrze_register_blocks(): void {
        register_block_type(plugin_dir_path(__DIR__) . 'build/block', [
            'render_callback' => [$this, 'render_faudir_block'],
            'skip_inner_blocks' => true
        ]);
        $scriptHandle = generate_block_asset_handle('rrze-faudir/block', 'editorScript');
        wp_set_script_translations(
            $scriptHandle,
            'rrze-faudir',
            plugin_dir_path(__DIR__) . 'languages'
        );
        load_plugin_textdomain(
            'rrze-faudir',
            false,
            dirname(plugin_basename(__DIR__)) . '/languages'
        );
    }


    /**
     * Adds custom block category if not already present.
     *
     * @param array $categories Existing block categories.
     * @param WP_Post $post Current post object.
     * @return array Modified block categories.
     */
    public static function register_rrze_block_category($categories, $post) {
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
    public static function render_faudir_block($attributes): string {
        try {
            if (!shortcode_exists('faudir')) {
                throw new Exception('FAUdir shortcode is not registered');
            }
            
            // Get default organization from options with proper checks
            $options = get_option('rrze_faudir_options', []);
            $default_org = isset($options['default_organization']) && is_array($options['default_organization'])
                ? $options['default_organization']
                : [];
            $defaultOrgIdentifier = isset($default_org['id']) ? $default_org['id'] : '';

            if (isset($attributes['display']) && 'org' === $attributes['display'] &&
                empty($attributes['orgid']) && empty($attributes['orgnr'])) {
                throw new \Exception(
                    __('You selected display="org", but neither a FAUorganization ID (orgid) nor a FAU Organization Number (orgnr) was provided.', 'rrze-faudir')
                );
            }

            // First check if we have function and orgnr
            if (!empty($attributes['role'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'role' => $attributes['role'],
                    'orgnr' => !empty($attributes['orgnr']) ? $attributes['orgnr'] : '',
                    'orgid' => !empty($attributes['orgid']) ? $attributes['orgid'] : '',
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
             } // Ccheck for selectedPosts (CPT Ids der lokalen Personen) without category
            else if (!empty($attributes['selectedPosts'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'id' => is_array($attributes['selectedPosts']) ?
                        implode(',', $attributes['selectedPosts']) :
                        $attributes['selectedPosts']
                ];
                
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
            } else if (!empty($attributes['orgnr'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'orgnr' => $attributes['orgnr']
                ];
            } else if (!empty($attributes['identifier'])) {
                $shortcode_atts = [
                    'format' => $attributes['selectedFormat'] ?? 'compact',
                    'identifier' => $attributes['identifier']
                ];
            } else {
                throw new Exception(__('Neither person IDs, function+orgnr, nor category were provided', 'rrze-faudir'));
            }

            // Add optional attributes
            if (!empty($attributes['selectedFields'])) {
                $shortcode_atts['show'] = implode(',', $attributes['selectedFields']);
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
             if (!empty($attributes['order'])) {
                $shortcode_atts['order'] = $attributes['order'];
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
            $shortcode .= ' blockeditor="true"';
            $shortcode .= ']';
            do_action( 'rrze.log.notice', "FAUdir\BlockRegistration (render_faudir_block): Creating Shortcode: ".$shortcode, $attributes);   

            
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