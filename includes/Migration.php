<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\API;

class Migration {
    private Config $config;

    public function __construct(Config $config) {
        $config->insertOptions();
        $this->config = $config;
    }
    
    public function register_hooks(): void {
        if (!$this->isFauPersonActive()) {
            return;
        }

        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'migrate_person_data_on_activation']);
        if ($this->isFauPersonActive()) {
            add_action('admin_notices', [$this, 'rrze_faudir_display_import_notice'], 15);
        }
    }

    public function isFauPersonActive(): bool {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (is_multisite() && function_exists('is_plugin_active_for_network')) {
            if (is_plugin_active_for_network('fau-person/fau-person.php')) {
                return true;
            }
        }

        return is_plugin_active('fau-person/fau-person.php');
    }

    /**
     * Migration beim Aktivieren:
     * - importiert Posts aus altem CPT 'person'
     * - legt neuen CPT-Post an
     * - speichert als Meta NUR: person_id (+ interne Helfer-Metas)
     * - setzt post_excerpt direkt aus alter Kurzbeschreibung
     * - KEINE Speicherung von alten person_* Feldern / Kontaktdaten
     */
    public function migrate_person_data_on_activation(): void {
        if (!$this->isFauPersonActive()) {
            return;
        }
        
        $post_type = (string) $this->config->get('person_post_type');
        $taxonomy  = (string) $this->config->get('person_taxonomy');

        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy(
                $taxonomy,
                $post_type,
                [
                    'hierarchical'          => true,
                    'public'                => true,
                    'show_ui'               => true,
                    'show_in_menu'          => true,
                    'show_in_nav_menus'     => true,
                    'show_tagcloud'         => true,
                    'show_in_quick_edit'    => true,
                    'meta_box_cb'           => null,
                    'show_admin_column'     => true,
                    'query_var'             => true,
                    'rewrite'               => ['slug' => $taxonomy],
                    'show_in_rest'          => true,
                    'rest_base'             => $taxonomy,
                    'rest_controller_class' => 'WP_REST_Terms_Controller',
                ]
            );
        }

        $contact_posts = get_posts([
            'post_type'      => 'person',
            'posts_per_page' => -1,
        ]);

        $imported_count = 0;
        $imported_list = [];
        $not_imported_count = 0;
        $not_imported_reasons = [];

        if (empty($this->config->get('api_key')) && !API::isUsingNetworkKey()) {
            $not_imported_count = is_array($contact_posts) ? count($contact_posts) : 0;
            if ($not_imported_count > 0) {
                $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir') . ' ' . __('After this you can restart importing old contact entries from there.', 'rrze-faudir');
            } else {
                $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir');
            }
        } else {
            if (!empty($contact_posts)) {
                foreach ($contact_posts as $post) {
                    $univisid = get_post_meta($post->ID, 'fau_person_univis_id', true);

                    $existing_person = get_posts([
                        'post_type'      => $post_type,
                        'meta_query'     => [
                            [
                                'key'     => 'fau_person_faudir_synced',
                                'value'   => $univisid,
                                'compare' => '=',
                            ],
                        ],
                        'posts_per_page' => 1,
                    ]);

                    if ($univisid && !$existing_person) {
                        $url = 'http://univis.uni-erlangen.de/prg?search=persons&id=' . $univisid . '&show=json';
                        $response = wp_remote_get($url);
                        $body = wp_remote_retrieve_body($response);
                        $data = json_decode($body, true);

                        if ($data === null) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir') . '.';
                            continue;
                        }

                        $person = $data['Person'][0] ?? null;
                        if (!$person) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir') . '.';
                            continue;
                        }

                        $uid = $person['idm_id'] ?? null;
                        $email = $person['location'][0]['email'] ?? null;
                        $given = $person['firstname'] ?? null;
                        $family = $person['lastname'] ?? null;

                        $api = new API($this->config);
                        $params = [
                            'identifier' => $uid,
                            'email'      => $email,
                            'givenName'  => $given,
                            'familyName' => $family,
                        ];
                        $resp = $api->getPersons(1, 0, $params);

                        if (!is_array($resp) || !isset($resp['data'])) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ':  <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('already exists or cannot be found on FAUdir', 'rrze-faudir') . '.';
                            continue;
                        }

                        $p = $resp['data'][0] ?? null;
                        if (!$p) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ':  <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('could not be importet. Possible Reasons: Either E-Mail-Adress from UnivIS wasnt found in public FAUdir entry or person name is not unique in FAUdir', 'rrze-faudir') . '.';
                            continue;
                        }

                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                        $short_desc = get_post_meta($post->ID, 'fau_person_description', true);
                        $description = !empty($short_desc) ? $short_desc : get_post_meta($post->ID, 'fau_person_small_description', true);

                        $excerpt = '';
                        if (!empty($description)) {
                            $excerpt = sanitize_text_field($description);
                        }

                        $new_post_id = wp_insert_post([
                            'post_type'    => $post_type,
                            'post_title'   => $post->post_title,
                            'post_content' => $post->post_content,
                            'post_excerpt' => $excerpt,
                            'post_status'  => 'publish',
                            'meta_input'   => [
                                '_thumbnail_id'            => $thumbnail_id ?: '',
                                'person_id'                => sanitize_text_field($p['identifier']),
                                'fau_person_faudir_synced' => $univisid,
                                'old_person_post_id'       => $post->ID,
                            ],
                        ]);

                        $this->migrate_categories((int) $post->ID, (int) $new_post_id, $taxonomy);

                        if ($new_post_id && !is_wp_error($new_post_id)) {
                            $imported_count++;
                            $imported_list[] = __('Person successfully importet', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>';
                        }
                    } else {
                        $not_imported_count++;
                        if (empty($univisid)) {
                            $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>';
                        } else {
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('already exists', 'rrze-faudir') . '.';
                        }
                    }
                }
            }
        }

        set_transient('rrze_faudir_imported_count', $imported_count, 60);
        set_transient('rrze_faudir_imported_list', $imported_list, 60);
        set_transient('rrze_faudir_not_imported_count', $not_imported_count, 60);
        set_transient('rrze_faudir_not_imported_reasons', $not_imported_reasons, 60);
    }

    private function migrate_categories(int $old_post_id, int $new_post_id, string $taxonomy): void {
        if ($new_post_id <= 0) {
            return;
        }

        $old_categories = wp_get_post_terms($old_post_id, 'persons_category', ['fields' => 'all']);
        if (empty($old_categories) || is_wp_error($old_categories)) {
            return;
        }

        foreach ($old_categories as $old_category) {
            $existing_term = term_exists($old_category->name, $taxonomy);
            if (!$existing_term) {
                $new_term = wp_insert_term(
                    $old_category->name,
                    $taxonomy,
                    [
                        'description' => $old_category->description,
                        'slug'        => $old_category->slug,
                    ]
                );
                if (!is_wp_error($new_term)) {
                    $term = get_term($new_term['term_id'], $taxonomy);
                } else {
                    $term = null;
                }
            } else {
                $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
                $term = get_term($term_id, $taxonomy);
            }

            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($new_post_id, $term->name, $taxonomy, true);
            }
        }
    }

    public function rrze_faudir_display_import_notice($echothis = true, $onpluginpage = true) {
        if (!isset($onpluginpage) || $onpluginpage) {
            $screen = get_current_screen();
            if ($screen && $screen->id !== 'plugins') {
                return;
            }
        }

        $res_escaped = '';

        $imported_count = get_transient('rrze_faudir_imported_count');
        $imported_list = get_transient('rrze_faudir_imported_list');
        $not_imported_count = get_transient('rrze_faudir_not_imported_count');
        $not_imported_reasons = get_transient('rrze_faudir_not_imported_reasons');

        if ($imported_count !== false || $not_imported_count !== false) {
            $import_message = sprintf(
                _n('%d person was successfully imported from the old plugin.', '%d persons were successfully imported from the old plugin.', (int) $imported_count, 'rrze-faudir'),
                (int) $imported_count
            );

            $not_imported_message = sprintf(
                _n('%d person was not able to be imported from the old plugin.', '%d persons were not able to be imported from the old plugin.', (int) $not_imported_count, 'rrze-faudir'),
                (int) $not_imported_count
            );

            if ((int) $imported_count > 0) {
                $success  = '<div class="notice notice-success is-dismissible">';
                $success .= '<p>' . esc_html($import_message) . '</p>';
                if (!empty($imported_list)) {
                    $success .= '<ul>';
                    foreach ((array) $imported_list as $reason) {
                        $success .= '<li>' . $reason . '</li>';
                    }
                    $success .= '</ul>';
                }
                $success .= '</div>';
                $res_escaped .= $success;
            }

            if ((int) $not_imported_count > 0) {
                $errorout  = '<div class="notice notice-error is-dismissible">';
                $errorout .= '<p>' . esc_html($not_imported_message) . '</p>';
                if (!empty($not_imported_reasons)) {
                    $errorout .= '<ul>';
                    foreach ((array) $not_imported_reasons as $reason) {
                        $errorout .= '<li>' . $reason . '</li>';
                    }
                    $errorout .= '</ul>';
                }
                $errorout .= '</div>';
                $res_escaped .= $errorout;
            }

            delete_transient('rrze_faudir_imported_count');
            delete_transient('rrze_faudir_imported_list');
            delete_transient('rrze_faudir_not_imported_count');
            delete_transient('rrze_faudir_not_imported_reasons');
        }

        if (!isset($onpluginpage) || $onpluginpage) {
            echo $res_escaped;
        }

        return $res_escaped;
    }
}