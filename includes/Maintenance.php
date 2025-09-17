<?php
/**
 * Maintenance
 */
namespace RRZE\FAUdir;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use RRZE\FAUdir\API;

class Maintenance {
    protected ?Config $config = null;

    public function __construct(Config $configdata) {
        $configdata->insertOptions();
        $this->config = $configdata;
    }

    public function register_hooks() {
        // Aktivierungshooks
        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'migrate_person_data_on_activation']);
        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'on_plugin_activation']);
        register_deactivation_hook(RRZE_PLUGIN_FILE, [$this, 'on_plugin_deactivation']);

        // Admin Notices
        add_action('admin_notices', [$this, 'rrze_faudir_display_import_notice'], 15);

        // Slug-Änderung überwachen
        add_action('update_option_rrze_faudir_options',  [$this, 'rrze_faudir_flush_rewrite_on_slug_change'], 10, 3);

        // Rewrite-Regeln speichern
        add_action('admin_init', [$this, 'rrze_faudir_save_permalink_settings']);

        // Scheduler
        add_action('rrze-faudir_check_person_availability', [$this, 'check_api_person_availability']);
        $this->migrate_scheduler_hook();

        // Templates / Redirects
        add_action('template_redirect', [$this, 'maybe_disable_canonical_redirect'], 1);
        add_filter('template_include', [$this, 'load_custom_person_template'], 99);
        add_action('template_redirect', [$this, 'custom_cpt_404_message']);
    }

    /**
     * Migration beim Aktivieren:
     * - importiert Posts aus altem CPT 'person'
     * - legt neuen CPT-Post an
     * - speichert als Meta NUR: person_id (+ interne Helfer-Metas)
     * - setzt post_excerpt direkt aus alter Kurzbeschreibung
     * - KEINE Speicherung von alten person_* Feldern / Kontaktdaten
     */
    public function migrate_person_data_on_activation() {
        // Taxonomy/Rewrite bereitstellen (ohne alte prozedurale Funktionen)
        $config   = new Config();
        $post_type = $config->get('person_post_type');
        $taxonomy  = $config->get('person_taxonomy');

        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy($taxonomy, $post_type, [
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => ['slug' => 'person-category'],
                'show_in_rest'      => true,
                'rest_base'         => $taxonomy,
            ]);
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
            $not_imported_count = count($contact_posts);
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
                        // UnivIS abrufen
                        $url = 'http://univis.uni-erlangen.de/prg?search=persons&id=' . $univisid . '&show=json';
                        $response = wp_remote_get($url);
                        $body = wp_remote_retrieve_body($response);
                        $data = json_decode($body, true);

                        if ($data === null) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir') . '.';
                        } else {
                            // Basisdaten aus UnivIS + FAUdir-API ermitteln
                            $person  = $data['Person'][0];
                            $uid     = $person['idm_id']     ?? null;
                            $email   = $person['location'][0]['email'] ?? null;
                            $given   = $person['firstname']   ?? null;
                            $family  = $person['lastname']    ?? null;

                            $api   = new API($this->config);
                            $params = [
                                'identifier' => $uid,
                                'email'      => $email,
                                'givenName'  => $given,
                                'familyName' => $family,
                            ];
                            $resp = $api->getPersons(1, 0, $params);

                            if (is_array($resp) && isset($resp['data'])) {
                                $p = $resp['data'][0] ?? null;

                                if ($p) {
                                    // Altes Bild / Kurzbeschreibung holen
                                    $thumbnail_id = get_post_thumbnail_id($post->ID);
                                    $short_desc   = get_post_meta($post->ID, 'fau_person_description', true);
                                    $description  = !empty($short_desc) ? $short_desc : get_post_meta($post->ID, 'fau_person_small_description', true);

                                    // Excerpt direkt setzen (statt _teasertext_* zu speichern)
                                    $excerpt = '';
                                    if (!empty($description)) {
                                        $excerpt = sanitize_text_field($description);
                                    }

                                    // Neuen Beitrag anlegen – nur nötigste Metas:
                                    // - _thumbnail_id (Core-Featured-Image)
                                    // - person_id (einzige persistente API-bezogene Meta laut neuem Konzept)
                                    // - fau_person_faudir_synced (interne Import-Markierung)
                                    // - old_person_post_id (interne Referenz)
                                    $new_post_id = wp_insert_post([
                                        'post_type'    => $post_type,
                                        'post_title'   => $post->post_title,
                                        'post_content' => $post->post_content,
                                        'post_excerpt' => $excerpt,
                                        'post_status'  => 'publish',
                                        'meta_input'   => [
                                            '_thumbnail_id'             => $thumbnail_id ?: '',
                                            'person_id'                 => sanitize_text_field($p['identifier']),
                                            'fau_person_faudir_synced'  => $univisid,
                                            'old_person_post_id'        => $post->ID,
                                        ],
                                    ]);

                                    // Alte Kategorien -> neue Taxonomy
                                    $old_categories = wp_get_post_terms($post->ID, 'persons_category', ["fields" => "all"]);
                                    if (!empty($old_categories) && !is_wp_error($old_categories)) {
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
                                                wp_set_object_terms(
                                                    $new_post_id,
                                                    $term->name,
                                                    $taxonomy,
                                                    true
                                                );
                                            }
                                        }
                                    }

                                    if ($new_post_id && !is_wp_error($new_post_id)) {
                                        $imported_count++;
                                        $imported_list[] = __('Person successfully importet', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>';
                                    }
                                } else {
                                    if (empty($univisid)) {
                                        $not_imported_count++;
                                        $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>';
                                    } else {
                                        $not_imported_count++;
                                        $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ':  <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('could not be importet. Possible Reasons: Either E-Mail-Adress from UnivIS wasnt found in public FAUdir entry or person name is not unique in FAUdir', 'rrze-faudir') . '.';
                                    }
                                }
                            } else {
                                if (empty($univisid)) {
                                    $not_imported_count++;
                                    $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>';
                                } else {
                                    $not_imported_count++;
                                    $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ':  <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('already exists or cannot be found on FAUdir', 'rrze-faudir') . '.';
                                }
                            }
                        }
                    } else {
                        if (empty($univisid)) {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>';
                        } else {
                            $not_imported_count++;
                            $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir') . ': <em>' . $post->post_title . '</em>, <code>' . $univisid . '</code> ' . __('already exists', 'rrze-faudir') . '.';
                        }
                    }
                }
            }
        }

        // Ergebnisse zwischenspeichern (für Admin Notice)
        set_transient('rrze_faudir_imported_count', $imported_count, 60);
        set_transient('rrze_faudir_imported_list', $imported_list, 60);
        set_transient('rrze_faudir_not_imported_count', $not_imported_count, 60);
        set_transient('rrze_faudir_not_imported_reasons', $not_imported_reasons, 60);
    }

    // Admin-Notice nach Migration
    public function rrze_faudir_display_import_notice($echothis = true, $onpluginpage = true) {
        if (!isset($onpluginpage) || ($onpluginpage)) {
            $screen = get_current_screen();
            if ($screen->id !== 'plugins') {
                return;
            }
        }
        $res_escaped = '';

        $imported_count      = get_transient('rrze_faudir_imported_count');
        $imported_list       = get_transient('rrze_faudir_imported_list');
        $not_imported_count  = get_transient('rrze_faudir_not_imported_count');
        $not_imported_reasons= get_transient('rrze_faudir_not_imported_reasons');

        if ($imported_count !== false || $not_imported_count !== false) {
            $import_message = sprintf(
                  /* translators: 1: Number of person, 2: Number of persons. */
                _n('%d person was successfully imported from the old plugin.', '%d persons were successfully imported from the old plugin.', $imported_count, 'rrze-faudir'),
                $imported_count
            );

            $not_imported_message = sprintf(
                /* translators: 1: Number of person, 2: Number of persons. */     
                _n('%d person was not able to be imported from the old plugin.', '%d persons were not able to be imported from the old plugin.', $not_imported_count, 'rrze-faudir'),
                $not_imported_count
            );

            if ($imported_count > 0) {
                $success  = '<div class="notice notice-success is-dismissible">';
                $success .= '<p>' . esc_html($import_message) . '</p>';
                if (!empty($imported_list)) {
                    $success .= '<ul>';
                    foreach ($imported_list as $reason) {
                        $success .= '<li>' . $reason . '</li>';
                    }
                    $success .= '</ul>';
                }
                $success .= '</div>';
                $res_escaped .= $success;
            }

            if ($not_imported_count > 0) {
                $errorout  = '<div class="notice notice-error is-dismissible">';
                $errorout .= '<p>' . esc_html($not_imported_message) . '</p>';
                if (!empty($not_imported_reasons)) {
                    $errorout .= '<ul>';
                    foreach ($not_imported_reasons as $reason) {
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

        if (!isset($onpluginpage) || ($onpluginpage)) {
            echo $res_escaped;
        }
        return $res_escaped;
    }

    public function rrze_faudir_flush_rewrite_on_slug_change($old_value, $value, $option) {
        if (
            ($option === 'rrze_faudir_options')
            && isset($old_value['person_slug'], $value['person_slug'])
            && ($old_value['person_slug'] !== $value['person_slug'])
        ) {
            flush_rewrite_rules();
        }
    }

    public function rrze_faudir_save_permalink_settings(): void {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    // CRON Scheduler
    public function on_plugin_activation(): void {
        if (!wp_next_scheduled('rrze-faudir_check_person_availability')) {
            wp_schedule_event(time(), 'hourly', 'rrze-faudir_check_person_availability');
        }
    }

    public function on_plugin_deactivation(): void {
        $timestamp = wp_next_scheduled('rrze-faudir_check_person_availability');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rrze-faudir_check_person_availability');
        }
    }

    public function migrate_scheduler_hook(): void {
        $old_hook = 'check_person_availability';
        $new_hook = 'rrze-faudir_check_person_availability';

        $timestamp = wp_next_scheduled($old_hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $old_hook);
        }

        if (!wp_next_scheduled($new_hook)) {
            wp_schedule_event(time(), 'hourly', $new_hook);
        }
    }

    // CRON Task: Beiträge ohne gültige person_id auf 'draft' setzen
    public function check_api_person_availability(): void {
        if (get_transient('check_person_availability_running')) {
            return;
        }
        $config    = new Config();
        $post_type = $config->get('person_post_type');

        set_transient('check_person_availability_running', true, 60);

        $api = new API($this->config);
        $posts = get_posts([
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
        ]);

        foreach ($posts as $post) {
            $person_id = get_post_meta($post->ID, 'person_id', true);

            if (empty($person_id)) {
                wp_update_post(['ID' => $post->ID, 'post_status' => 'draft']);
                continue;
            }

            $person_data = $api->getPerson($person_id);
            if ($person_data === false || empty($person_data)) {
                wp_update_post(['ID' => $post->ID, 'post_status' => 'draft']);
            }
        }

        delete_transient('check_person_availability_running');
    }

    // Templates / Routing
    public static function load_custom_person_template($template) {
        $config = new Config();
        $post_type = $config->get('person_post_type');
        if (get_query_var($post_type) || is_singular($post_type)) {
            $plugin_template = plugin_dir_path(__DIR__) . '/templates/single-custom_person.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public static function custom_cpt_404_message() {
        global $wp_query;
        $config = new Config();
        $post_type = $config->get('person_post_type');

        if (isset($wp_query->query_vars['post_type'])
            && $wp_query->query_vars['post_type'] === $post_type
            && empty($wp_query->post)) {

            self::render_custom_404();
            return;
        }

        $options = get_option('rrze_faudir_options');
        $slug = !empty($options['person_slug']) ? sanitize_title($options['person_slug']) : 'faudir';
        if (self::is_slug_request($slug)) {
            $redirect = trim($options['redirect_archivpage_uri'] ?? '');
            if (!empty($redirect)) {
                if (str_starts_with($redirect, '/')) {
                    $redirect = home_url($redirect);
                }
                if (filter_var($redirect, FILTER_VALIDATE_URL)) {
                    wp_redirect(esc_url_raw($redirect), 301);
                    exit;
                }
            }
            self::render_custom_404();
        }
    }

    public static function maybe_disable_canonical_redirect(): void {
        $options  = get_option('rrze_faudir_options');
        $redirect = trim($options['redirect_archivpage_uri'] ?? '');
        if (empty($redirect)) return;

        $slug = !empty($options['person_slug']) ? sanitize_title($options['person_slug']) : 'faudir';
        if (self::is_slug_request($slug)) {
            remove_filter('template_redirect', 'redirect_canonical');
        }
    }

    private static function render_custom_404(): void {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);

        ob_start();
        add_action('shutdown', function () {
            $content = ob_get_clean();
            $new_hero_content = '<div class="hero-container hero-content">'
                . '<p class="presentationtitle">' . __('No contact entry could be found.', 'rrze-faudir') . '</p>'
                . '</div>';
            $updated_content_escaped = preg_replace(
                '/<p class="presentationtitle">.*?<\/p>/s',
                $new_hero_content,
                $content
            );
            echo $updated_content_escaped;
        }, 0);

        include get_404_template();
        exit;
    }

    private static function is_slug_request(string $slug): bool {
        if (! isset($_SERVER['REQUEST_URI'])) {
            return false;
        }
        $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $home_path   = trim(parse_url(home_url(), PHP_URL_PATH) ?? '', '/');

        if (!empty($home_path) && stripos($request_uri, $home_path) === 0) {
            $request_uri = trim(substr($request_uri, strlen($home_path)), '/');
        }

        $normalized_uri  = strtolower(preg_replace('#/index\.php$#', '', $request_uri));
        $normalized_slug = strtolower(trim($slug, '/'));

        return $normalized_uri === $normalized_slug;
    }
}
