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
        $this->rrze_faudir_register_dynamic_blocks();
    }

    /**
     * Registriert die dynamischen FAUdir-Blöcke und deren Render-Funktionen.
     *
     * @return void
     */
    function rrze_faudir_register_dynamic_blocks(): void
    {
        $blocks = [
            [
                'build_folder'     => 'faudir',
                'block_name'       => 'rrze-faudir/block',   // aus block.json -> "name"
                'render_callback'  => [$this, 'render_faudir_block'],
            ],
            [
                'build_folder'     => 'service',
                'block_name'       => 'rrze-faudir/service', // aus block.json -> "name"
                'render_callback'  => [$this, 'render_service_block'],
            ],
        ];

        $plugin_dir = plugin_dir_path(__DIR__);
        $textdomain = 'rrze-faudir';
        $languages_dir = $plugin_dir . 'languages';

        foreach ($blocks as $block_def) {
            register_block_type(
                $plugin_dir . 'build/blocks/' . $block_def['build_folder'],
                [
                    'render_callback'   => $block_def['render_callback'],
                    'skip_inner_blocks' => true,
                ]
            );

            load_plugin_textdomain('rrze-faudir', false, dirname(plugin_basename(__DIR__)) . 'languages');

            $script_handle = generate_block_asset_handle( $block_def['block_name'], 'editorScript');
            wp_set_script_translations($script_handle, 'rrze-faudir', plugin_dir_path(__DIR__) . 'languages');
        }
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
            'title' => __('FAU', 'rrze-faudir'),
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
            
            $shortcode_atts = [];
            
            if ($attributes['selectedFormat']) {
                $shortcode_atts['format'] =  $attributes['selectedFormat'];
            } else {
                $shortcode_atts['format'] =  'compact';
            }
            // First check if we have function and orgnr
            if (!empty($attributes['role'])) {
                $shortcode_atts['role'] =  $attributes['role'];
            }    
            if (!empty($attributes['orgid'])) {
               $shortcode_atts['orgid'] =  $attributes['orgid'];
            }
            if (!empty($attributes['orgnr'])) {
               $shortcode_atts['orgnr'] =  $attributes['orgnr'];
            }
            


            if (!empty($attributes['selectedPosts'])) {
                if (is_array($attributes['selectedPosts'])) {
                    $shortcode_atts['id'] = implode(',', $attributes['selectedPosts']);
                } else {
                    $shortcode_atts['id'] = $attributes['selectedPosts'];
                }
            }  


            
            if (!empty($attributes['identifier'])) {
                $shortcode_atts['identifier'] = $attributes['identifier'];  
            } elseif (!empty($attributes['selectedPersonIds'])) {
                if (is_array($attributes['selectedPersonIds'])) {
                    $shortcode_atts['identifier'] = implode(',', $attributes['selectedPersonIds']);
                } else {
                    $shortcode_atts['identifier'] = $attributes['selectedPersonIds'];
                }
            }
            
            if (!empty($attributes['selectedCategory'])) {
                $shortcode_atts['category'] =   $attributes['selectedCategory'];
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
            // do_action( 'rrze.log.notice', "FAUdir\BlockRegistration (render_faudir_block): Creating Shortcode: ".$shortcode, $attributes);   

            
            // Execute shortcode
            $output = do_shortcode($shortcode);

            if (empty(trim($output))) {
                throw new Exception(esc_html__('No output avaible', 'rrze-faudir'));
            }

            return $output;

        } catch (Exception $e) {
            return sprintf(
                '<div class="faudir-error">%s</div>',
                esc_html($e->getMessage())
            );
        }
    }

    /**
     * Render the Service block with live FAUdir data.
     */
    public static function render_service_block($attributes): string {
        $orgid = $attributes['orgid'] ?? '';
        $orgid = Organization::sanitizeOrgIdentifier((string) $orgid);

        if (empty($orgid)) {
            return '';
        }

        $organization = new Organization();
        if (!$organization->getOrgbyAPI($orgid)) {
            return '';
        }

        $orgData = $organization->toArray();
        $address = isset($orgData['address']) && is_array($orgData['address']) ? $orgData['address'] : [];
        $name    = $orgData['name'] ?? '';

        $openingHours = $organization->openingHours ?? new OpeningHours($orgData);

        $visibleFields = self::get_service_visible_fields($attributes['visibleFields'] ?? null);
        $isVisible = fn(string $field): bool => in_array($field, $visibleFields, true);

        $street = (string) ($address['street'] ?? '');
        $zip    = (string) ($address['zip'] ?? '');
        $city   = (string) ($address['city'] ?? '');
        $phone  = (string) ($address['phone'] ?? '');
        $mail   = (string) ($address['mail'] ?? '');
        $url    = (string) ($address['url'] ?? '');

        $imageId     = isset($attributes['imageId']) ? (int) $attributes['imageId'] : 0;
        $imageUrl    = !empty($attributes['imageURL']) ? esc_url($attributes['imageURL']) : '';
        $imageWidth  = isset($attributes['imageWidth']) ? (int) $attributes['imageWidth'] : 0;
        $imageHeight = isset($attributes['imageHeight']) ? (int) $attributes['imageHeight'] : 0;

        $imageHtml = '';
        if ($imageId > 0) {
            $imageHtml = wp_get_attachment_image(
                $imageId,
                'full',
                false,
                [
                    'class'   => 'rrze-elements-blocks_service__image',
                    'loading' => 'lazy',
                ]
            );
        } elseif ($imageUrl) {
            $attributesAttr = '';
            if ($imageWidth > 0) {
                $attributesAttr .= ' width="' . esc_attr((string) $imageWidth) . '"';
            }
            if ($imageHeight > 0) {
                $attributesAttr .= ' height="' . esc_attr((string) $imageHeight) . '"';
            }
            $imageHtml = sprintf(
                '<img class="rrze-elements-blocks_service__image" src="%s" alt=""%s />',
                $imageUrl,
                $attributesAttr
            );
        }

        $displayText = !empty($attributes['displayText']) ? wp_kses_post($attributes['displayText']) : '';

        $officeHoursLabel = __('Office hours', 'rrze-faudir');
        $officeHoursHtml = '';
        if ($isVisible('officeHours') && $openingHours instanceof OpeningHours) {
            $officeHoursHtml = $openingHours->getConsultationsHours(
                'officeHours',
                null,
                self::get_opening_hours_lang(),
                $officeHoursLabel
            );
        }

        $hasAddress = (
            ($isVisible('street') && $street) ||
            (($isVisible('zip') && $zip) || ($isVisible('city') && $city))
        );
        $hasContact = (
            ($isVisible('phone') && $phone) ||
            ($isVisible('mail') && $mail) ||
            ($isVisible('url') && $url)
        );
        $hasOfficeHours = !empty(trim($officeHoursHtml));
        $hasImage = !empty($imageHtml);
        $hasDescription = !empty($displayText);

        $wrapperClass = '';
        if (!$hasImage) {
            $wrapperClass = $wrapperClass . ' no_image';
        }

        $block_unique_id   = wp_unique_id('service-');
        $title_id          = $block_unique_id . '-title';
        $address_heading_id = $block_unique_id . '-address';
        $hours_heading_id   = $block_unique_id . '-hours';
        $contact_heading_id = $block_unique_id . '-contact';

        ob_start();
        ?>
        <article class="faudir__service rrze-elements-blocks_service_card<?php echo($wrapperClass); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
            <?php if ($imageHtml): ?>
                <figure class="rrze-elements-blocks_service__figure">
                    <?php echo $imageHtml; ?>
                </figure>
            <?php endif; ?>

            <div class="rrze-elements-blocks_service__information_card">
            <?php if ($isVisible('name') && $name): ?>
                <header class="rrze-elements-blocks_service__meta_headline">
                    <h2 id="<?php echo esc_attr($title_id); ?>" class="meta-headline"><?php echo esc_html($name); ?></h2>
                    <?php if (!empty($displayText)): ?>
                        <?php echo $displayText; ?>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <?php if ($hasAddress): ?>
                <section class="rrze-elements-blocks_service__information" aria-labelledby="<?php echo esc_attr($address_heading_id); ?>">
                    <h3 id="<?php echo esc_attr($address_heading_id); ?>" class="addr-h"><?php esc_html_e('Address', 'rrze-faudir'); ?></h3>
                    <address>
                        <?php if ($isVisible('street') && $street): ?>
                            <span><?php echo esc_html($street); ?><br/></span>
                        <?php endif; ?>
                        <?php if (($isVisible('zip') && $zip) || ($isVisible('city') && $city)): ?>
                            <span>
                                <?php
                                $zipCity = [];
                                if ($isVisible('zip') && $zip) {
                                    $zipCity[] = esc_html($zip);
                                }
                                if ($isVisible('city') && $city) {
                                    $zipCity[] = esc_html($city);
                                }
                                echo implode(' ', $zipCity);
                                ?>
                            </span>
                        <?php endif; ?>
                    </address>
                </section>
            <?php endif; ?>

            <?php if ($hasOfficeHours): ?>
                <section aria-labelledby="<?php echo esc_attr($hours_heading_id); ?>">
                    <h3 id="<?php echo esc_attr($hours_heading_id); ?>" class="hours-h"><?php echo esc_html($officeHoursLabel); ?></h3>
                    <?php echo $officeHoursHtml; ?>
                </section>
            <?php endif; ?>

            <?php if ($hasContact): ?>
                <section class="contact-section" aria-labelledby="<?php echo esc_attr($contact_heading_id); ?>">
                    <h3 id="<?php echo esc_attr($contact_heading_id); ?>" class="contact-h"><?php esc_html_e('Contact', 'rrze-faudir'); ?></h3>
                    <ul class="contact-address icon icon-list">
                        <?php if ($isVisible('phone') && $phone): ?>
                            <li>
                                <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                                    <?php echo esc_html($phone); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($isVisible('mail') && $mail): ?>
                            <li>
                                <a href="mailto:<?php echo esc_attr($mail); ?>">
                                    <?php echo esc_html($mail); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($isVisible('url') && $url): ?>
                            <li>
                                <a href="<?php echo esc_url($url); ?>">
                                    <?php echo esc_html($url); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>
            <?php endif; ?>
            </div>
        </article>
        <?php
        return trim(ob_get_clean()) ?: '';
    }

    private const SERVICE_DEFAULT_VISIBLE_FIELDS = [
        'name',
        'street',
        'zip',
        'city',
        'phone',
        'mail',
        'url',
        'officeHours',
    ];

    private static function get_service_visible_fields($fields): array {
        if (is_array($fields) && !empty($fields)) {
            $sanitized = array_values(array_filter(array_map('strval', $fields)));
            if (!empty($sanitized)) {
                return $sanitized;
            }
        }
        return self::SERVICE_DEFAULT_VISIBLE_FIELDS;
    }

    /**
     * Keeps OpeningHours output in sync with the current site language.
     */
    private static function get_opening_hours_lang(): string {
        $locale = '';
        if (function_exists('determine_locale')) {
            $locale = (string) determine_locale();
        } elseif (function_exists('get_locale')) {
            $locale = (string) get_locale();
        }

        $prefix = strtolower(substr($locale, 0, 2));
        return ($prefix === 'en') ? 'en' : 'de';
    }
}
