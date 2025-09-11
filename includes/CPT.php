<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Config;
use RRZE\FAUdir\API;
use RRZE\FAUdir\Organization;
use RRZE\FAUdir\Contact;

/**
 * Class CPT
 */

class CPT {
    protected $config;
    protected string $settings_page_slug = 'rrze-faudir';
    protected string $person_search_tab  = 'contacts'; 
    protected string $canonical_meta_key = 'canonical_url';


    public function __construct() {
        $this->config = new Config();

        // Register CPT & Taxonomies
        add_action('init', [$this, 'register_post_type'], 15);
        add_action('init', [$this, 'register_taxonomy'], 15);
        add_action('init', [$this, 'register_legacy_taxonomy'], 15);

        // Meta Box
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);


        // Save Hook inkl. Migration (nur person_id + displayed_contacts)
        add_action('save_post', [$this, 'save_meta']);

        // AJAX
        add_action('wp_ajax_fetch_person_attributes', [$this, 'fetch_person_attributes']);
        add_action('wp_ajax_rrze_faudir_create_custom_person', [$this, 'ajax_create_custom_person']);

        // REST-Meta
        add_action('init', [$this, 'register_person_meta_for_rest'], 15);

        // REST Antwort anreichern (nur Taxonomy, keine API-Felder)
        $post_type = $this->config->get('person_post_type');
        add_filter("rest_prepare_{$post_type}", [$this, 'add_taxonomy_to_person_rest'], 10, 3);
        
        // Umleitung bei "Add New" (post-new.php für diesen CPT)
        add_action('load-post-new.php', [$this, 'redirect_add_new_to_search']);

        // "Add New" Untermenü ersetzen
        add_action('admin_menu', [$this, 'replace_add_new_submenu'], 99);
        
        
        // Canonical URL verwalten, wenn vorhanden
        add_filter('get_canonical_url', [$this, 'maybe_override_canonical'], 10, 2);
        
        // Set Canonical URL for misc. SEO Plugins 
        add_filter('wpseo_canonical',                     [$this, 'maybe_override_canonical_for_seo']);
        add_filter('rank_math/frontend/canonical',        [$this, 'maybe_override_canonical_for_seo']);
        add_filter('the_seo_framework_rel_canonical',     [$this, 'maybe_override_canonical_for_seo']);

    }

    /* === CPT === */
    public function register_post_type() {
        $post_type = $this->config->get('person_post_type');
        $options   = get_option('rrze_faudir_options');
        $slug      = isset($options['person_slug']) && !empty($options['person_slug'])
            ? sanitize_title($options['person_slug'])
            : 'faudir';

        $args = [
            'labels' => [
                'name'          => __('Persons', 'rrze-faudir'),
                'singular_name' => __('Person', 'rrze-faudir'),
                'menu_name'     => __('Persons', 'rrze-faudir'),
                'add_new_item'  => __('Add New Person (via FAUdir search)', 'rrze-faudir'),
                'edit_item'     => __('Edit Person', 'rrze-faudir'),
            ],
            'public'          => true,
            'has_archive'     => false,
            'rewrite'         => [
                'slug'       => $slug,
                'with_front' => false,
            ],
            // WICHTIG: excerpt aktivieren
            'supports'        => ['title', 'editor', 'thumbnail', 'excerpt'],
            'taxonomies'      => ['custom_taxonomy'],
            'show_in_rest'    => true,
            'rest_base'       => $post_type,
            'menu_position'   => 5,
            'capability_type' => 'post',
            'menu_icon'       => 'dashicons-id',
        ];

        register_post_type($post_type, $args);
    }

    /* === Taxonomies === */
    public function register_taxonomy() {
        $post_type = $this->config->get('person_post_type');
        $taxonomy  = $this->config->get('person_taxonomy');

        if (!taxonomy_exists('custom_taxonomy')) {
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
    }

    // Ehemalige/Legacy-Variante
    public function register_legacy_taxonomy() {
        $post_type = $this->config->get('person_post_type');
        $taxonomy  = $this->config->get('person_taxonomy');

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

    /* === Meta Boxes === */
    public function add_meta_boxes() {
        $post_type = $this->config->get('person_post_type');
        add_meta_box(
            'person_additional_fields',
            __('Additional Fields', 'rrze-faudir'),
            [$this, 'render_person_additional_fields'],
            $post_type,
            'normal',
            'high'
        );
    }

 

    public function render_person_additional_fields($post) {
        wp_nonce_field('save_person_additional_fields', 'person_additional_fields_nonce');

        $post_type = $this->config->get('person_post_type');
        if ($post->post_type !== $post_type) return;

     
     
        // ---- Canonical URL (editierbar) ----
        $canonical_url = get_post_meta($post->ID, $this->canonical_meta_key, true);

        echo "<label for='canonical_url'>" . esc_html__('Canonical URL', 'rrze-faudir') . "</label>";
        
        echo "<p class='description'>" . esc_html__(
            'The canonical URL is the designated, most representative URL for a page like this person info, when that content is available at multiple URLs, a situation known as duplicate content. By specifying the canonical URL, search engines like Google know which page is added to its index, preventing confusion, consolidation of link equity, and improving search result presentation.',
            'rrze-faudir'
        ) . "</p>";
        echo "<input type='url' name='canonical_url' id='canonical_url' placeholder='https://www.fau.de/team/' value='" . esc_attr($canonical_url) . "' style='width: 100%;' /><br><br>";
        $opt = $this->config->getOptions();        
        $usecanonical = $opt['redirect_to_canonicals'];
        
        if ($usecanonical) {
            echo '<p class="description"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ';
            echo esc_html__('Please note that this URL will be used when creating links to the person.', 'rrze-faudir');
            echo '</p>';
        }

 
        
        echo '<hr>';
        echo '<p><strong>' . esc_html__('FAUdir', 'rrze-faudir') . ' ' . esc_html__('Data', 'rrze-faudir') .' ' . esc_html__('(Read-only)', 'rrze-faudir'). '</strong></p>';      
        echo '<p>' . esc_html__(
            'The following data comes from the FAUdir portal. A change of data is only possible by the persons or the appointed contact persons in the FAUdir portal.',
            'rrze-faudir'
        ) . '</p>';
        
           // ----  person_id ist nur sichtbar, aber nicht mehr bearbeitbar (ab 2.4) ----
        $person_id_value = get_post_meta($post->ID, 'person_id', true);

        echo "<label for='person_id'>" . esc_html__('FAUdir Identifier', 'rrze-faudir') . "</label>";
        echo "<p class='description'>" . esc_html__('Internal "API Person Identification" as used by FAUdir.','rrze-faudir') . "</p>";
        echo "<input type='text' id='person_id' value='" . esc_attr($person_id_value) . "' style='width: 100%;' readonly /><br><br>";
      
        
        
        // ---- Live-Daten aus API für Read-only Darstellung ----
        $person_api = null;
        $contacts_api = [];
        if (!empty($person_id_value)) {
            $api    = new API($this->config);
            $params = ['identifier' => $person_id_value];
            $response = $api->getPersons(60, 0, $params);
            if (is_array($response) && isset($response['data'][0])) {
                $person_api = $response['data'][0];

                // Kontakte vorbereiten (read-only Anzeige)
                if (isset($person_api['contacts']) && is_array($person_api['contacts'])) {
                    $org = new Organization();
                    $org->setConfig($this->config);
                    foreach ($person_api['contacts'] as $contactInfo) {
                        $cont = new Contact($contactInfo);
                        $cont->setConfig($this->config);
                        $cont->getContactbyAPI($contactInfo['identifier']);

                        $organizationIdentifier = $contactInfo['organization']['identifier'] ?? '';
                        $org->getOrgbyAPI($organizationIdentifier);

                        $contacts_api[] = [
                            'organization' => $contactInfo['organization']['name'] ?? '',
                            'function_en'  => $contactInfo['functionLabel']['en'] ?? '',
                            'function_de'  => $contactInfo['functionLabel']['de'] ?? '',
                            'socials'      => $cont->getSocialString(),
                            'workplace'    => $cont->getWorkplacesString(),
                            'address'      => $org->getAdressString(),
                        ];
                    }
                }
            }
        }

        // Hilfsfunktion zur Read-only Ausgabe
        $ro = function($label, $value) {
            echo "<label>" . esc_html($label) . "</label>";
            echo "<input type='text' value='" . esc_attr($value) . "' style='width:100%;' readonly /><br><br>";
        };

        // Read-only Felder (nur Anzeige)
        $ro(__('Name', 'rrze-faudir'),
            trim(($person_api['givenName'] ?? '') . ' ' . ($person_api['familyName'] ?? ''))
        );
        $ro(__('Email', 'rrze-faudir'), ($person_api['email'] ?? ''));
        $ro(__('Telephone', 'rrze-faudir'), ($person_api['telephone'] ?? ''));
        $ro(__('Given Name', 'rrze-faudir'), ($person_api['givenName'] ?? ''));
        $ro(__('Family Name', 'rrze-faudir'), ($person_api['familyName'] ?? ''));
        $ro(__('Title', 'rrze-faudir'), ($person_api['honorificPrefix'] ?? ''));
        $ro(__('Suffix', 'rrze-faudir'), ($person_api['honorificSuffix'] ?? ''));
        $ro(__('Nobility Title', 'rrze-faudir'), ($person_api['titleOfNobility'] ?? ''));

        // Kontakte-Bereich (read-only, live)
        echo '<div class="contacts-wrapper">';
        echo '<h3>' . esc_html__('FAUdir', 'rrze-faudir') . ' ' . esc_html__('Contacts (Read-only)', 'rrze-faudir') . '</h3>';

        // Auswahl der anzuzeigenden Kontaktkarte (persistente UI-Einstellung)
        $displayed_contacts = intval(get_post_meta($post->ID, 'displayed_contacts', true));
        if ($displayed_contacts < 0) $displayed_contacts = 0;

        foreach ($contacts_api as $index => $contact) {
            $checked = $activeblock = '';
            if ($index === $displayed_contacts) {
                $checked = 'checked="checked"';
                $activeblock = ' activeblock';
            }

            echo '<div class="organization-block' . $activeblock . '">';
            echo '<div class="organization-header">';
            echo '<h3>' . esc_html__('Contact', 'rrze-faudir') . ' ' . ($index + 1) . '</h3>';
            echo '<label>';
            echo "<input type='radio' name='displayed_contacts' value='" . esc_attr($index) . "' $checked>";
            echo esc_html__('Display this contact', 'rrze-faudir');
            echo '</label>';
            echo '</div>';
            echo '<div class="organization-content' . $activeblock . '">';

            echo '<div class="organization-wrapper">';
            echo '<h4>' . esc_html__('Organization', 'rrze-faudir') . '</h4>';
            echo '<input type="text" value="' . esc_attr($contact['organization'] ?? '') . '" class="widefat" readonly />';
            echo '</div>';

            echo '<div class="function-wrapper">';
            echo '<h4>' . esc_html__('Function (English)', 'rrze-faudir') . '</h4>';
            echo '<input type="text" value="' . esc_attr($contact['function_en'] ?? '') . '" class="widefat" readonly />';
            echo '</div>';

            echo '<div class="function-wrapper">';
            echo '<h4>' . esc_html__('Function (German)', 'rrze-faudir') . '</h4>';
            echo '<input type="text" value="' . esc_attr($contact['function_de'] ?? '') . '" class="widefat" readonly />';
            echo '</div>';

            echo '<div class="socials-wrapper">';
            echo '<h4>' . esc_html__('Socials', 'rrze-faudir') . '</h4>';
            echo '<textarea class="widefat" readonly rows="5">' . esc_textarea($contact['socials'] ?? '') . '</textarea>';
            echo '</div>';

            echo '<div class="workplace-wrapper">';
            echo '<h4>' . esc_html__('Workplace', 'rrze-faudir') . '</h4>';
            echo '<textarea class="widefat" readonly rows="6">' . esc_textarea($contact['workplace'] ?? '') . '</textarea>';
            echo '</div>';

            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; // contacts-wrapper
    }

    /* === Save + Migration === */
    public function save_meta($post_id) {
        if (!isset($_POST['person_additional_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['person_additional_fields_nonce'])), 'save_person_additional_fields')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;


        // Deaktiviert, da die Änderung nicht mehr möglich sein soll. 
        // Die FAUdir Id wird beim Anlegen mittels Ajax erstellt und bleibt fest.
        
    //    if (isset($_POST['person_id'])) {
    //        update_post_meta($post_id, 'person_id', sanitize_text_field(wp_unslash($_POST['person_id'])));
    //    }

        // 'displayed_contacts' (UI-Wahl) weiterhin speichern
        if (isset($_POST['displayed_contacts'])) {
            update_post_meta($post_id, 'displayed_contacts', intval($_POST['displayed_contacts']));
        }
        // Canonical URL speichern (nur http/https, sonst leer)
        if (isset($_POST['canonical_url'])) {
            $raw = wp_unslash((string) $_POST['canonical_url']);
            $value = $this->sanitize_canonical_url($raw);
            if ($value !== '') {
                update_post_meta($post_id, $this->canonical_meta_key, $value);
            } else {
                delete_post_meta($post_id, $this->canonical_meta_key);
            }
        }

        /**
         * === MIGRATION: _teasertext_de/_teasertext_en -> post_excerpt ===
         * - Excerpt bleibt unangetastet, wenn er schon Inhalt hat
         * - Wenn Site-Sprache EN (en_* oder 'en'): zuerst _teasertext_en, sonst _teasertext_de
         * - Wenn Site-Sprache NICHT EN: zuerst _teasertext_de, sonst _teasertext_en
         * - Fallbacks wie beschrieben; am Ende beide Teaser-Metas löschen
         * - _content_en wird IMMER entfernt (ohne Migration)
         */
        $old_teaser_de = get_post_meta($post_id, '_teasertext_de', true);
        $old_teaser_en = get_post_meta($post_id, '_teasertext_en', true);
        $current_post  = get_post($post_id);
        $excerpt       = isset($current_post->post_excerpt) ? $current_post->post_excerpt : '';

        if ('' === trim((string) $excerpt)) {
            $locale = function_exists('get_locale') ? (string) get_locale() : '';
            $loc    = strtolower($locale);
            $is_en  = (strpos($loc, 'en_') === 0) || ($loc === 'en');

            if ($is_en) {
                $candidate = (string) ($old_teaser_en ?: '');
                if ('' === trim($candidate) && !empty($old_teaser_de)) {
                    $candidate = (string) $old_teaser_de;
                }
            } else {
                $candidate = (string) ($old_teaser_de ?: '');
                if ('' === trim($candidate) && !empty($old_teaser_en)) {
                    $candidate = (string) $old_teaser_en;
                }
            }

            if ('' !== trim($candidate)) {
                wp_update_post([
                    'ID'           => $post_id,
                    'post_excerpt' => sanitize_text_field($candidate),
                ]);
            }
        }

        // Alte Metas IMMER entfernen
        delete_post_meta($post_id, '_teasertext_de');
        delete_post_meta($post_id, '_teasertext_en');
        delete_post_meta($post_id, '_content_en');

        // Säuberung: evtl. vorhandene frühere persistente API-Felder entfernen
        $obsolete_api_metas = [
            'person_name',
            'person_email',
            'person_telephone',
            'person_givenName',
            'person_familyName',
            'person_honorificPrefix',
            'person_honorificSuffix',
            'person_titleOfNobility',
            'person_contacts', // falls zuvor gespeichert
        ];
        foreach ($obsolete_api_metas as $meta_key) {
            delete_post_meta($post_id, $meta_key);
        }
    }

    /* === AJAX: Attribute laden (nur Ausgabe, kein Speichern) === */
    public function fetch_person_attributes() {
        check_ajax_referer('custom_person_nonce', 'nonce');

        $person_id = isset($_POST['person_id']) ? sanitize_text_field($_POST['person_id']) : '';
        if (empty($person_id)) {
            wp_send_json_error(__('Invalid person ID.', 'rrze-faudir'));
        }

        $api      = new API($this->config);
        $params   = ['identifier' => $person_id];
        $response = $api->getPersons(60, 0, $params);

        if (is_array($response) && isset($response['data'])) {
            $person = $response['data'][0] ?? null;
            if ($person) {
                $contacts = [];
                if (isset($person['contacts']) && is_array($person['contacts'])) {
                    foreach ($person['contacts'] as $contactInfo) {
                        $contacts[] = [
                            'organization'    => $contactInfo['organization']['name'] ?? '',
                            'organization_id' => $contactInfo['organization']['identifier'] ?? '',
                            'function_en'     => $contactInfo['functionLabel']['en'] ?? '',
                            'function_de'     => $contactInfo['functionLabel']['de'] ?? '',
                        ];
                    }
                }

                wp_send_json_success([
                    'person_name'            => sanitize_text_field(($person['givenName'] ?? '') . ' ' . ($person['familyName'] ?? '')),
                    'person_email'           => sanitize_email($person['email'] ?? ''),
                    'person_telephone'       => sanitize_text_field($person['telephone'] ?? ''),
                    'person_givenName'       => sanitize_text_field($person['givenName'] ?? ''),
                    'person_familyName'      => sanitize_text_field($person['familyName'] ?? ''),
                    'person_honorificSuffix' => sanitize_text_field($person['honorificSuffix'] ?? ''),
                    'person_honorificPrefix' => sanitize_text_field($person['honorificPrefix'] ?? ''),
                    'organizations'          => $contacts,
                ]);
            } else {
                wp_send_json_error(__('No contact found.', 'rrze-faudir'));
            }
        } else {
            wp_send_json_error(__('Error fetching person attributes.', 'rrze-faudir'));
        }
    }

    /* === AJAX: Person anlegen ===
       Legt nur den Beitrag an + person_id, keine API-Felder werden persistiert. */
    public function ajax_create_custom_person() {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');

        $post_type         = $this->config->get('person_post_type');
        $person_name       = isset($_POST['person_name']) ? sanitize_text_field($_POST['person_name']) : '';
        $person_id         = isset($_POST['person_id']) ? sanitize_text_field($_POST['person_id']) : '';
        $includeDefaultOrg = isset($_POST['include_default_org']) && $_POST['include_default_org'] === '1';

        if (empty($person_name) || empty($person_id)) {
            wp_send_json_error('Invalid person data');
        }

        $post_id = wp_insert_post([
            'post_title'  => $person_name,
            'post_type'   => $post_type,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }

        // Nur person_id persistieren
        update_post_meta($post_id, 'person_id', $person_id);

        // displayed_contacts aus Request übernehmen (optional)
        if (isset($_POST['displayed_contacts'])) {
            update_post_meta($post_id, 'displayed_contacts', intval($_POST['displayed_contacts']));
        }

        // Keine weiteren API-Felder/META speichern!
        wp_send_json_success([
            'post_id'  => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'url'),
            'message'  => __('Custom person created successfully!', 'rrze-faudir'),
        ]);
    }

    /* === REST === */
    public function register_person_meta_for_rest() {
        $post_type = $this->config->get('person_post_type');
        // person_id
        register_post_meta($post_type, 'person_id', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function () { return current_user_can('edit_posts'); }
        ]);
        // canonical_url
        register_post_meta($post_type, $this->canonical_meta_key, [
            'show_in_rest'       => true,
            'single'             => true,
            'type'               => 'string',
            'auth_callback'      => function () { return current_user_can('edit_posts'); },
            'sanitize_callback'  => function($value) {
                // Delegiert an unsere Klassenmethode:
                return $this->sanitize_canonical_url($value);
            },
        ]);
        // Keine weiteren API-Felder registrieren.
    }

    public function add_taxonomy_to_person_rest($response, $post, $request) {
        $post_type = $this->config->get('person_post_type');
        $taxonomy  = $this->config->get('person_taxonomy');

        if ($post->post_type === $post_type) {
                // Get custom taxonomy terms
                $terms = wp_get_object_terms($post->ID, $taxonomy);
                if (is_wp_error( $terms ) ) {
                    do_action( 'rrze.log.error', 'FAUdir\CPT (add_taxonomy_to_person_rest): ERROR ON wp_get_object_terms: taxonomy = '.$taxonomy.' posttype = '.$post_type, $terms->get_error_message());           
                    return;
                }
                $term_ids = array_map(function ($term) {
                    return $term->term_id;
                }, $terms);

                // Add taxonomy terms to response
                $response->data['custom_taxonomy'] = $term_ids;

                // Add other meta data
                $person_id = get_post_meta($post->ID, 'person_id', true);
                $person_name = get_post_meta($post->ID, 'person_name', true);

                $response->data['meta'] = array_merge(
                    $response->data['meta'] ?? [],
                    [
                        'person_id' => $person_id,
                        'person_name' => $person_name
                    ]
                );
            }
            return $response;
    }
    /** Erzeugt die Ziel-URL der Personensuche in den Settings */
    protected function get_person_search_url(): string {
        $tab = apply_filters('faudir_person_search_tab', $this->person_search_tab);
        $page = apply_filters('faudir_settings_page_slug', $this->settings_page_slug);

        return add_query_arg(
            ['page' => $page, 'tab' => $tab],
            admin_url('options-general.php')
        );
    }
    
    public function redirect_add_new_to_search(): void {
        if (!function_exists('get_current_screen')) {
            return;
        }
        $screen    = get_current_screen();
        $post_type = $this->config->get('person_post_type');

        // Wir sind auf post-new.php (neuer Beitrag) UND es ist unser CPT?
        // Absicherung sowohl über Screen als auch über GET-Parameter.
        
        $is_our_cpt = false;
        if ($screen && ($screen->post_type ?? '') === $post_type) {
            $is_our_cpt = true;
        } elseif (isset($_GET['post_type']) && sanitize_key($_GET['post_type']) === $post_type) {
            $is_our_cpt = true;
        }
        if ($is_our_cpt) {
            wp_safe_redirect($this->get_person_search_url());
            exit;
        }
    }
    
    /*
     *  „Add New“-Untermenü ersetzen
     */
    public function replace_add_new_submenu(): void {
        $post_type = $this->config->get('person_post_type');
        $parent_slug = 'edit.php?post_type=' . $post_type;

        // Standard-"Add New" entfernen
        remove_submenu_page($parent_slug, 'post-new.php?post_type=' . $post_type);

       // Eigenen Eintrag hinzufügen
        // Capability: 'edit_posts' = für Redakteure sichtbar; 'manage_options' nur für Admins
        $hook = add_submenu_page(
            $parent_slug,
            __('Add New Person', 'rrze-faudir'),
            __('Add New', 'rrze-faudir'),
            'edit_posts',
            'faudir-add-new-person',
            [$this, 'render_add_new_redirect_stub'] // Fallback-Stub, falls Redirect nicht greift
        );

        // WICHTIG: Früh umleiten, bevor irgendwas ausgegeben wird
        if ($hook) {
            add_action("load-$hook", function () {
                wp_safe_redirect($this->get_person_search_url());
                exit;
            });
        }
    }
    /*
     *  Fallback, falls aus irgendeinem Grund load-$hook nicht feuert
     */
    public function render_add_new_redirect_stub(): void {
        echo '<div class="wrap"><p>' . esc_html__('Redirecting to FAUdir search.', 'rrze-faudir') . '</p></div>';
    }
    
    /**
     * Validiert die Canonical URL (nur http/https) und gibt eine bereinigte URL zurück – oder '' bei Ungültigkeit.
     */
    private function sanitize_canonical_url($value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Grundbereinigung
        $url = esc_url_raw($value);

        // Nur vollständige http/https-URLs akzeptieren
        if (function_exists('wp_http_validate_url')) {
            $valid = wp_http_validate_url($url);
            if (!$valid) {
                return '';
            }
            // Sicherheitshalber Schema prüfen
            $scheme = parse_url($valid, PHP_URL_SCHEME);
            return (in_array($scheme, ['http','https'], true)) ? $valid : '';
        }

        // Fallback ohne wp_http_validate_url:
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            return (in_array($scheme, ['http','https'], true)) ? $url : '';
        }

        return '';
    }
    
    /* 
     * Set new canoncial URL for WordPress Header
     */
    public function maybe_override_canonical($canonical, $post = null) {
        $post_type = $this->config->get('person_post_type');
        if (!is_singular($post_type)) {
            return $canonical;
        }

        $post_id = ($post instanceof \WP_Post) ? $post->ID : get_queried_object_id();
        if (!$post_id) {
            return $canonical;
        }

        $custom = get_post_meta($post_id, 'canonical_url', true);
        if ($custom && filter_var($custom, FILTER_VALIDATE_URL)) {
            return esc_url_raw($custom);
        }

        return $canonical;
    }
    
    /*
     * Overwrite Canonical for misc. SEO plugins
     */
    public function maybe_override_canonical_for_seo($canonical) {
        return $this->maybe_override_canonical($canonical, get_post());
    }

}

