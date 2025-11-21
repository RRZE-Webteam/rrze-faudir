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
        register_block_type(plugin_dir_path(__DIR__) . 'build/blocks/faudir', [
            'render_callback' => [$this, 'render_faudir_block'],
            'skip_inner_blocks' => true
        ]);
        $scriptHandle = generate_block_asset_handle('rrze-faudir/blocks/faudir', 'editorScript');
        wp_set_script_translations(
            $scriptHandle,
            'rrze-faudir',
            plugin_dir_path(__DIR__) . 'languages'
        );
        register_block_type(plugin_dir_path(__DIR__) . 'build/blocks/service', [
            'render_callback' => [$this, 'render_service_block'],
            'skip_inner_blocks' => true
        ]);
        $scriptHandle = generate_block_asset_handle('rrze-faudir/blocks/service', 'editorScript');
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

        $orgData   = $organization->toArray();
        $address   = isset($orgData['address']) && is_array($orgData['address']) ? $orgData['address'] : [];
        $name      = $orgData['name'] ?? '';
        $officeRaw = $orgData['officeHours'] ?? ($organization->openingHours?->officeHours ?? []);

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

        $formattedOfficeHours = $isVisible('officeHours')
            ? self::format_service_hours($officeRaw)
            : [];

        $hasAddress = (
            ($isVisible('street') && $street) ||
            (($isVisible('zip') && $zip) || ($isVisible('city') && $city))
        );
        $hasContact = (
            ($isVisible('phone') && $phone) ||
            ($isVisible('mail') && $mail) ||
            ($isVisible('url') && $url)
        );
        $hasOfficeHours = !empty($formattedOfficeHours);
        $hasImage = !empty($imageHtml);
        $hasDescription = !empty($displayText);

        $wrapperClass = '';
        if ($hasAddress) {
            $wrapperClass = ' has_address';
        }
        if ($hasContact) {
            $wrapperClass = $wrapperClass . ' has_contact';
        }
        if ($hasDescription) {
            $wrapperClass = $wrapperClass . ' has_desc';
        }
        if ($hasImage) {
            $wrapperClass = $wrapperClass . ' has_image';
        }

        $title_id = 'service-title-' . wp_unique_id();

        ob_start();
        ?>
        <article class="rrze-elements-blocks_service_card<?php echo($wrapperClass); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
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
                <section class="rrze-elements-blocks_service__information" aria-labelledby="addr-h">
                    <h3 class="addr-h"><?php esc_html_e('Adresse', 'rrze-faudir'); ?></h3>
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
                <section aria-labelledby="hours-h">
                    <h3 class="hours-h"><?php esc_html_e('Office hours', 'rrze-faudir'); ?></h3>
                    <ul class="list-icons">
                        <?php foreach ($formattedOfficeHours as $index => $entry): ?>
                            <li><?php echo esc_html($entry); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($hasContact): ?>
                <section class="contact-section" aria-labelledby="contact-h">
                    <h3 class="contact-h"><?php esc_html_e('Contact', 'rrze-faudir'); ?></h3>
                    <address class="contact-address">
                        <?php if ($isVisible('phone') && $phone): ?>
                            <p>
                                <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                                    <?php echo esc_html($phone); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($isVisible('mail') && $mail): ?>
                            <p>
                                <a href="mailto:<?php echo esc_attr($mail); ?>">
                                    <?php echo esc_html($mail); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($isVisible('url') && $url): ?>
                            <p>
                                <a href="<?php echo esc_url($url); ?>">
                                    <?php echo esc_html($url); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </address>
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

    private static function format_service_hours($hours): array {
        if (!is_array($hours) || empty($hours)) {
            return [];
        }

        $formatted = [];
        foreach ($hours as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $weekdayRaw = $entry['weekday'] ?? null;
            $weekday = is_numeric($weekdayRaw) ? (int) $weekdayRaw : null;
            if ($weekday !== null) {
                if ($weekday > 6) {
                    $weekday = $weekday % 7;
                }
                if ($weekday < 0) {
                    $weekday = null;
                }
            }
            $weekdayLabel = self::get_weekday_label($weekday);
            $from = isset($entry['from']) ? (string) $entry['from'] : '';
            $to   = isset($entry['to']) ? (string) $entry['to'] : '';

            $timeLabel = '';
            if ($from && $to) {
                $timeLabel = sprintf('%s – %s', $from, $to);
            } elseif ($from || $to) {
                $timeLabel = $from ?: $to;
            }

            $parts = array_filter([$weekdayLabel, $timeLabel]);
            if (!empty($parts)) {
                $formatted[] = implode(': ', $parts);
            }
        }

        return $formatted;
    }

    private static function get_weekday_label(?int $weekday): string {
        $map = [
            0 => __('Sunday', 'rrze-faudir'),
            1 => __('Monday', 'rrze-faudir'),
            2 => __('Tuesday', 'rrze-faudir'),
            3 => __('Wednesday', 'rrze-faudir'),
            4 => __('Thursday', 'rrze-faudir'),
            5 => __('Friday', 'rrze-faudir'),
            6 => __('Saturday', 'rrze-faudir'),
        ];
        return $map[$weekday ?? -1] ?? '';
    }
}
