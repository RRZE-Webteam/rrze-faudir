<?php
namespace RRZE\FAUdir;

if (!defined('ABSPATH')) { exit; }

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Config;
use RRZE\FAUdir\API;
use RRZE\FAUdir\Maintenance;

class Settings {
    /** 
     * Konstruktor.
     * Optionale Eingaben: keine.
     * Rückgabe: void.
     */
    public function __construct() {}

    /**
     * Registriert alle benötigten Hooks für Admin-Menüs, Settings und AJAX.
     * Optionale Eingaben: keine.
     * Rückgabe: void.
     */
    public function register_hooks(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);

        // Admin-Post + AJAX
        add_action('admin_post_delete_default_organization', [$this, 'delete_default_organization']);
        add_action('admin_post_save_default_organization',   [$this, 'save_default_organization']);

        add_action('wp_ajax_rrze_faudir_reset_defaults',     [$this, 'reset_defaults']);
        add_action('wp_ajax_rrze_faudir_clear_cache',        [$this, 'clear_cache']);
        add_action('wp_ajax_rrze_faudir_import_fau_person',  [$this, 'import_fau_person']);

        add_action('wp_ajax_rrze_faudir_search_person',      [$this, 'search_person_ajax']);
        add_action('wp_ajax_rrze_faudir_search_org',         [$this, 'search_org_callback']);
    }

    /**
     * Fügt die Einstellungsseite unter "Einstellungen" hinzu.
     * Optionale Eingaben: keine.
     * Rückgabe: void.
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('RRZE FAUdir Settings', 'rrze-faudir'),
            __('RRZE FAUdir', 'rrze-faudir'),
            'manage_options',
            'rrze-faudir',
            [$this, 'settings_page']
        );
    }

    /**
     * Initialisiert die Settings (Defaults, Register, Sektionen, Felder).
     * Optionale Eingaben: keine.
     * Rückgabe: void.
     */
    public function settings_init(): void {
        // Defaults initialisieren
        $config           = new Config();
        $default_settings = $config->getOverwiteableOptions();
        $options          = get_option('rrze_faudir_options', []);
        $settings         = wp_parse_args($options, $default_settings);
        update_option('rrze_faudir_options', $settings);

        register_setting('rrze_faudir_settings', 'rrze_faudir_options', [
            'sanitize_callback' => [$this, 'sanitize_options'],
        ]);

        // Sektionen & Felder
        add_settings_section(
            'rrze_faudir_contacts_search_section',
            __('Search Contacts', 'rrze-faudir'),
            [$this, 'contacts_search_section_cb'],
            'rrze_faudir_settings_contacts_search'
        );

        add_settings_section(
            'rrze_faudir_org_search_section',
            __('Search Organizations', 'rrze-faudir'),
            [$this, 'org_search_section_cb'],
            'rrze_faudir_settings_org_search'
        );

        add_settings_section(
            'rrze_faudir_shortcode_section',
            __('Default Output Fields', 'rrze-faudir'),
            [$this, 'shortcode_section_cb'],
            'rrze_faudir_settings_shortcode'
        );
        add_settings_field(
            'rrze_faudir_default_output_fields',
            __('Output fields for formats', 'rrze-faudir'),
            [$this, 'default_output_fields_render'],
            'rrze_faudir_settings_shortcode',
            'rrze_faudir_shortcode_section'
        );
        add_settings_field(
            'rrze_faudir_business_card_title',
            __('Kompakt Card Button Title', 'rrze-faudir'),
            [$this, 'business_card_title_render'],
            'rrze_faudir_settings_shortcode',
            'rrze_faudir_shortcode_section'
        );
        add_settings_field(
            'rrze_faudir_fallback_link_faudir',
            __('Fallback FAUdir link', 'rrze-faudir'),
            [$this, 'fallback_link_faudir_render'],
            'rrze_faudir_settings_shortcode',
            'rrze_faudir_shortcode_section'
        );
        add_settings_field(
            'rrze_faudir_jobtitle_format',
            __('Format', 'rrze-faudir') . ' ' . __('Jobtitle', 'rrze-faudir'),
            [$this, 'jobtitle_format_render'],
            'rrze_faudir_settings_shortcode',
            'rrze_faudir_shortcode_section'
        );

        add_settings_section(
            'rrze_faudir_profilpage_section',
            __('Profilpage', 'rrze-faudir'),
            [$this, 'profilpage_section_cb'],
            'rrze_faudir_settings_profilpage'
        );
        add_settings_field(
            'rrze_faudir_profilpage_output_fields',
            __('Data fields that are shown on the profil page', 'rrze-faudir'),
            [$this, 'profilpage_output_fields_render'],
            'rrze_faudir_settings_profilpage',
            'rrze_faudir_profilpage_section'
        );

        add_settings_section(
            'rrze_faudir_api_section',
            __('API Settings', 'rrze-faudir'),
            [$this, 'api_section_cb'],
            'rrze_faudir_settings'
        );
        add_settings_field(
            'rrze_faudir_api_key',
            __('API Key', 'rrze-faudir'),
            [$this, 'api_key_render'],
            'rrze_faudir_settings',
            'rrze_faudir_api_section'
        );

        // Network function ggf. nachladen
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (is_plugin_active('fau-person/fau-person.php')) {
            add_settings_field(
                'rrze_faudir_import_fau_person',
                __('Import', 'rrze-faudir'),
                [$this, 'import_fau_person_render'],
                'rrze_faudir_settings',
                'rrze_faudir_api_section'
            );
        }

        add_settings_section(
            'rrze_faudir_cache_section',
            __('Cache Settings', 'rrze-faudir'),
            [$this, 'cache_section_cb'],
            'rrze_faudir_settings_cache'
        );
        add_settings_field(
            'rrze_faudir_no_cache_logged_in',
            __('No Caching for Logged-in Editors', 'rrze-faudir'),
            [$this, 'no_cache_logged_in_render'],
            'rrze_faudir_settings_cache',
            'rrze_faudir_cache_section'
        );
        add_settings_field(
            'rrze_faudir_cache_timeout',
            __('Cache Timeout (in minutes)', 'rrze-faudir'),
            [$this, 'cache_timeout_render'],
            'rrze_faudir_settings_cache',
            'rrze_faudir_cache_section'
        );
        add_settings_field(
            'rrze_faudir_transient_time_for_org_id',
            __('Transient Time for Organization ID (in days)', 'rrze-faudir'),
            [$this, 'transient_time_for_org_id_render'],
            'rrze_faudir_settings_cache',
            'rrze_faudir_cache_section'
        );
        add_settings_field(
            'rrze_faudir_clear_cache',
            __('Clear All Cache', 'rrze-faudir'),
            [$this, 'clear_cache_render'],
            'rrze_faudir_settings_cache',
            'rrze_faudir_cache_section'
        );

        add_settings_section(
            'rrze_faudir_error_section',
            __('Error Handling', 'rrze-faudir'),
            [$this, 'error_section_cb'],
            'rrze_faudir_settings_error'
        );
        add_settings_field(
            'rrze_faudir_error_message',
            __('Show Error Message for Invalid Contacts', 'rrze-faudir'),
            [$this, 'error_message_render'],
            'rrze_faudir_settings_error',
            'rrze_faudir_error_section'
        );

        add_settings_section(
            'rrze_faudir_misc_section',
            __('Misc Settings', 'rrze-faudir'),
            [$this, 'misc_section_cb'],
            'rrze_faudir_settings_advanced'
        );
        add_settings_field(
            'rrze_faudir_person_slug',
            __('Person Slug', 'rrze-faudir'),
            [$this, 'person_slug_render'],
            'rrze_faudir_settings_advanced',
            'rrze_faudir_misc_section'
        );
        add_settings_field(
            'rrze_faudir_redirect_archivpage_uri',
            __('Index page', 'rrze-faudir'),
            [$this, 'misc_section_message_render'],
            'rrze_faudir_settings_advanced',
            'rrze_faudir_misc_section'
        );
    }

    /**
     * Sektionstext: API.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function api_section_cb(): void {
        echo '<p>' . esc_html__('Configure the API settings for accessing the FAU person and institution directory.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Cache.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function cache_section_cb(): void {
        echo '<p>' . esc_html__('Configure caching settings for the plugin.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Fehlerbehandlung.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function error_section_cb(): void {
        echo '<p>' . esc_html__('Handle error messages for invalid contact entries.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Shortcode/Output.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function shortcode_section_cb(): void {
        echo '<p>' . esc_html__('Configure the default fields for the output formats.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Profilseite.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function profilpage_section_cb(): void {
        echo '<p>' . esc_html__('Configure the default output fields for the profile page of a single person.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Sonstiges.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function misc_section_cb(): void {
        echo '<p>' . esc_html__('Configure other advanced settings.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Kontakte-Suche.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function contacts_search_section_cb(): void {
        echo '<p>' . esc_html__('Search for FAU contacts by ID, name or email.', 'rrze-faudir') . '</p>';
    }

    /**
     * Sektionstext: Organisationen-Suche.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function org_search_section_cb(): void {
        echo '<p>' . esc_html__('Search for FAU organizations by name or identifier.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: Profilseiten-Ausgabefelder (Checkbox-Liste).
     * Optionale Eingaben: keine (liest Optionen).
     * Rückgabe: void (echo).
     */
    public function profilpage_output_fields_render(): void {
        $config = new Config();
        $opt    = $config->getOptions();
        $available_fields = $config->get('avaible_fields');

        if (empty($opt['output_fields_endpoint']) && !empty($opt['default_output_fields_endpoint'])) {
            $opt['output_fields_endpoint'] = $opt['default_output_fields_endpoint'];
        }

        echo '<table class="faudir-attributs">';
        echo '<tr><th>' . esc_html__('Output data', 'rrze-faudir') . '</th><th>' . esc_html__('Default value', 'rrze-faudir') . '</th></tr>';

        foreach ($available_fields as $field => $label) {
            $checked = in_array($field, $opt['output_fields_endpoint'] ?? [], true);

            echo '<tr>';
            echo '<th><label for="' . esc_attr('rrze_faudir_profilpage_output_fields' . $field) . '">';
            echo '<input type="checkbox" id="' . esc_attr('rrze_faudir_profilpage_output_fields' . $field) . '" name="rrze_faudir_options[output_fields_endpoint][]" value="' . esc_attr($field) . '" ' . checked($checked, true, false) . '>';
            echo esc_html($label) . '</label></th>';
            echo '<td>' . (in_array($field, $opt['default_output_fields_endpoint'] ?? [], true) ? __('Visible', 'rrze-faudir') : __('Invisible', 'rrze-faudir')) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p class="description">' . esc_html__('Select the fields to display in the profil page of a single person.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: API Key Eingabe.
     * Optionale Eingaben: keine (liest Optionen).
     * Rückgabe: void (echo).
     */
    public function api_key_render(): void {
        if (FaudirUtils::isUsingNetworkKey()) {
            echo '<p>' . esc_html__('The API key is being used from the network installation.', 'rrze-faudir') . '</p>';
            return;
        }
        $options = get_option('rrze_faudir_options');
        $apiKey  = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo '<label><input type="text" name="rrze_faudir_options[api_key]" value="' . $apiKey . '" size="50">';
        echo '<p class="description">' . esc_html__('Enter your API key here.', 'rrze-faudir') . '</p></label>';
    }

    /**
     * Render: Import-Button für altes Plugin.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function import_fau_person_render(): void {
        echo '<button type="button" class="button button-secondary" id="import-fau-person-button">' . esc_html__('Import contact entries from FAU Person', 'rrze-faudir') . '</button>';
        echo '<p class="description">' . esc_html__('Click the button to restart the import of contact entries from FAU Person. Notice, that this will only import contact entries, which refer to a public viewable person in FAUdir.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: "Kein Cache für eingeloggte Redakteure".
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function no_cache_logged_in_render(): void {
        $options = get_option('rrze_faudir_options');
        echo '<label><input type="checkbox" name="rrze_faudir_options[no_cache_logged_in]" value="1" ' . checked(1, $options['no_cache_logged_in'] ?? 0, false) . '>';
        echo '<span>' . esc_html__('Disable caching for logged-in editors.', 'rrze-faudir') . '</span></label>';
    }

    /**
     * Render: Cache-Timeout (Minuten).
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function cache_timeout_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['cache_timeout']) ? max((int)$options['cache_timeout'], 15) : 15;
        echo '<label><input type="number" name="rrze_faudir_options[cache_timeout]" value="' . esc_attr($value) . '" min="15">';
        echo '<p class="description">' . esc_html__('Set the cache timeout in minutes (minimum 15 minutes).', 'rrze-faudir') . '</p></label>';
    }

    /**
     * Render: Transient-Laufzeit für Org-IDs (Tage).
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function transient_time_for_org_id_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['transient_time_for_org_id']) ? max((int)$options['transient_time_for_org_id'], 1) : 1;
        echo '<input type="number" name="rrze_faudir_options[transient_time_for_org_id]" value="' . esc_attr($value) . '" min="1">';
        echo '<p class="description">' . esc_html__('Set the transient time in days for intermediate stored organization identifiers (minimum 1 day).', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: (falls genutzt) Cache-Timeout für Orgs (deprecated/optional).
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function cache_org_timeout_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['cache_org_timeout']) ? (int)$options['cache_org_timeout'] : 1;
        echo '<label><input type="number" name="rrze_faudir_options[cache_org_timeout]" value="' . esc_attr($value) . '" min="1">';
        echo '<p class="description">' . esc_html__('Set the cache timeout in days for organization identifiers.', 'rrze-faudir') . '</p></label>';
    }

    /**
     * Render: Cache leeren Button.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function clear_cache_render(): void {
        echo '<button type="button" class="button button-secondary" id="clear-cache-button">' . esc_html__('Clear Cache Now', 'rrze-faudir') . '</button>';
        echo '<p class="description">' . esc_html__('Click the button to clear all cached data.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: Fehlermeldungen anzeigen Checkbox.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function error_message_render(): void {
        $options = get_option('rrze_faudir_options');
        if (!isset($options['show_error_message'])) {
            $config = new Config();
            $options['show_error_message'] = $config->get('show_error_message');
        }
        echo '<label><input type="checkbox" name="rrze_faudir_options[show_error_message]" value="1" ' . checked(1, $options['show_error_message'] ?? 0, false) . '>';
        echo '<span>' . esc_html__('Show error messages for incorrect contact entries.', 'rrze-faudir') . '</span></label>';
    }

    /**
     * Render: Titel für "Business Card" Linktext.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function business_card_title_render(): void {
        $options = get_option('rrze_faudir_options');
        $config  = new Config();
        $default_title = $config->get('business_card_title');

        $value = isset($options['business_card_title']) && !empty($options['business_card_title'])
            ? sanitize_text_field($options['business_card_title'])
            : $default_title;

        if (!isset($options['business_card_title']) || empty($options['business_card_title'])) {
            $options['business_card_title'] = $default_title;
            update_option('rrze_faudir_options', $options);
        }

        echo '<input type="text" name="rrze_faudir_options[business_card_title]" value="' . esc_attr($value) . '" size="50">';
        echo '<p class="description">' . esc_html__('Link title for optional links pointing to the users detail page.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: Fallback-Link zum öffentlichen FAUdir.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function fallback_link_faudir_render(): void {
        $options = get_option('rrze_faudir_options');
        echo '<label><input type="checkbox" name="rrze_faudir_options[fallback_link_faudir]" value="1" ' . checked(1, $options['fallback_link_faudir'] ?? 0, false) . '>';
        echo '<span>' . esc_html__('On using profil links, fallback to the public faudir portal, if no local custom post is avaible.', 'rrze-faudir') . '</span></label>';
    }

    /**
     * Render: Formatvorlage für Jobtitel.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function jobtitle_format_render(): void {
        $options = get_option('rrze_faudir_options');
        $config  = new Config();
        $default_format = $config->get('jobtitle_format');

        $value = isset($options['jobtitle_format']) && !empty($options['jobtitle_format'])
            ? sanitize_text_field($options['jobtitle_format'])
            : $default_format;

        if (!isset($options['jobtitle_format']) || empty($options['jobtitle_format'])) {
            $options['jobtitle_format'] = $default_format;
            update_option('rrze_faudir_options', $options);
        }

        echo '<input type="text" name="rrze_faudir_options[jobtitle_format]" value="' . esc_attr($value) . '" size="50">';
        echo '<p class="description">' . esc_html__('Define the format of jobtitles.', 'rrze-faudir') . '<br>' .
            esc_html__('You might use the variables #orgname# (Name of the defined organisation), #functionlabel# (function label by FAUdir) and #alternatename# (short or alternative name for the organization) here and other strings, but no HTML or special chars.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: Hinweis/URL für Index-Seite (Archiv-Redirect).
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function misc_section_message_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['redirect_archivpage_uri']) ? esc_url($options['redirect_archivpage_uri']) : '';

        echo '<label><input type="text" name="rrze_faudir_options[redirect_archivpage_uri]" value="' . esc_attr($value) . '" class="regular-text" placeholder="/">';
        echo '<p class="description">' . esc_html__('Optional: Path to a local page, which is used as index for all contact entries.', 'rrze-faudir') . '</p></label>';
    }

    /**
     * Render: Slug für Person-CPT.
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function person_slug_render(): void {
        $options      = get_option('rrze_faudir_options', []);
        $default_slug = 'faudir';
        $slug         = !empty($options['person_slug']) ? sanitize_text_field($options['person_slug']) : $default_slug;

        if (!isset($options['person_slug']) || empty($options['person_slug'])) {
            $options['person_slug'] = $default_slug;
            update_option('rrze_faudir_options', $options);
        }

        echo '<input type="text" class="regular-text" id="rrze_faudir_person_slug" name="rrze_faudir_options[person_slug]" value="' . esc_attr($slug) . '" size="10">';
        echo '<p class="description">' . esc_html__('Enter the slug for the person post type.', 'rrze-faudir') . '</p>';
    }

    /**
     * Render: Default-Ausgabefelder (Checkbox-Liste + Format-Verfügbarkeit).
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo).
     */
    public function default_output_fields_render(): void {
        $options        = get_option('rrze_faudir_options');
        $default_fields = $options['default_output_fields'] ?? [];

        $config           = new Config();
        $available_fields = $config->get('avaible_fields');
        $formatnames      = $config->get('formatnames');
        $fieldlist        = $config->getAvaibleFieldlist();

        if (empty($default_fields)) {
            $default_fields = array_keys((array) $available_fields);
        }

        echo '<table class="faudir-attributs">';
        echo '<tr><th>' . esc_html__('Output data', 'rrze-faudir') . '</th><th>' . esc_html__('Fieldname for Show/Hide-Attribut in Shortcodes', 'rrze-faudir') . '</th><th>' . esc_html__('Avaible in formats', 'rrze-faudir') . '</th></tr>';

        foreach ((array) $available_fields as $field => $label) {
            $checked = in_array($field, $default_fields, true);

            echo '<tr>';
            echo '<th><label for="' . esc_attr('rrze_faudir_default_output_fields_' . $field) . '">';
            echo '<input type="checkbox" id="' . esc_attr('rrze_faudir_default_output_fields_' . $field) . '" name="rrze_faudir_options[default_output_fields][]" value="' . esc_attr($field) . '" ' . checked($checked, true, false) . '>';
            echo esc_html($label) . '</label></th>';

            echo '<td><code>' . esc_html($field) . '</code></td><td>';

            $canuse = '';
            foreach ((array) $fieldlist as $fl => $entries) {
                if (!empty($entries) && in_array($field, (array) $entries, true)) {
                    if (!empty($canuse)) { $canuse .= ', '; }
                    if ($fl === 'org-compact') {
                        $canuse .= $formatnames[$fl] . ' (<code>compact</code>)';
                    } else {
                        $canuse .= $formatnames[$fl] . ' (<code>' . $fl . '</code>)';
                    }
                }
            }
            if (!empty($canuse)) {
                echo $canuse;
            }
            echo '</td></tr>';
        }
        echo '</table>';
        echo '<p class="description">' . esc_html__('Select the fields to display by default in shortcodes and blocks.', 'rrze-faudir') . '</p>';
    }

    /**
     * Rendert die komplette Settings-Seite (Tabs + Inhalte).
     * Optionale Eingaben: keine.
     * Rückgabe: void (echo/HTML).
     */
    public function settings_page(): void {
        ?>
        <div class="wrap faudir-settings">
            <h1><?php echo esc_html(__('FAUdir Settings', 'rrze-faudir')); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="#tab-1" class="nav-tab"><?php echo esc_html__('Search Contacts', 'rrze-faudir'); ?></a>
                <a href="#tab-2" class="nav-tab"><?php echo esc_html__('Search Organizations', 'rrze-faudir'); ?></a>
                <a href="#tab-3" class="nav-tab"><?php echo esc_html__('Default Output Fields', 'rrze-faudir'); ?></a>
                <a href="#tab-4" class="nav-tab"><?php echo esc_html__('Profilpage', 'rrze-faudir'); ?></a>
                <a href="#tab-5" class="nav-tab"><?php echo esc_html__('Advanced Settings', 'rrze-faudir'); ?></a>
            </h2>

            <form action="options.php" method="post">
                <?php settings_fields('rrze_faudir_settings'); ?>

                <div id="tab-3" class="tab-content" style="display:none;">
                    <?php do_settings_sections('rrze_faudir_settings_shortcode'); ?>
                    <?php submit_button(); ?>
                </div>

                <div id="tab-4" class="tab-content" style="display:none;">
                    <?php do_settings_sections('rrze_faudir_settings_profilpage'); ?>
                    <?php submit_button(); ?>
                </div>

                <div id="tab-5" class="tab-content" style="display:none;">
                    <div id="migration-progress" style="margin-top: 1rem;"></div>
                    <?php do_settings_sections('rrze_faudir_settings'); ?>
                    <hr>
                    <?php do_settings_sections('rrze_faudir_settings_cache'); ?>
                    <hr>
                    <?php do_settings_sections('rrze_faudir_settings_error'); ?>
                    <hr>
                    <?php do_settings_sections('rrze_faudir_settings_advanced'); ?>
                    <?php submit_button(); ?>

                    <hr>
                    <div class="danger-zone">
                        <h3><?php echo esc_html__('Reset to Default Settings', 'rrze-faudir'); ?></h3>
                        <p><?php echo esc_html__('Click the button below to reset all settings to their default values.', 'rrze-faudir'); ?></p>
                        <button type="button" class="button button-secondary" id="reset-to-defaults-button">
                            <?php echo esc_html__('Reset to Default Values', 'rrze-faudir'); ?>
                        </button>
                    </div>
                </div>
            </form>

            <div id="tab-1" class="tab-content" style="display:none;">
                <h2><?php echo esc_html__('Search Contacts', 'rrze-faudir'); ?></h2>

                <form id="search-person-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="rrze_faudir_search_person">
                    <?php wp_nonce_field('rrze_faudir_search_person', 'rrze_faudir_search_nonce'); ?>

                    <table class="form-table">
                        <tbody>
                        <tr>
                            <td colspan="2">
                                <p class="description">
                                    <?php echo esc_html__('Please enter at least one search term. If more than one parameter is entered, the search results must contain all values (AND search).', 'rrze-faudir'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Search Options', 'rrze-faudir'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="include-default-org">
                                        <input type="checkbox" id="include-default-org" name="include-default-org" value="1" checked>
                                        <span><?php echo esc_html__('Filter by default organization', 'rrze-faudir'); ?></span>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="person-id"><?php echo esc_html__('Search Terms', 'rrze-faudir'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label for="person-id"><?php echo esc_html__('API-Person-Identifier', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="person-id" name="person-id" class="regular-text" />
                                    </p><br>
                                    <p>
                                        <label for="given-name"><?php echo esc_html__('Given Name', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="given-name" name="given-name" class="regular-text" />
                                    </p><br>
                                    <p>
                                        <label for="family-name"><?php echo esc_html__('Family Name', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="family-name" name="family-name" class="regular-text" />
                                    </p><br>
                                    <p>
                                        <label for="email"><?php echo esc_html__('Email', 'rrze-faudir'); ?>:</label><br>
                                        <input type="text" id="email" name="email" class="regular-text" />
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <button type="submit" class="button button-primary" disabled><?php echo esc_html__('Search', 'rrze-faudir'); ?></button>
                </form>

                <div id="contacts-list">
                    <?php
                    if (isset($_GET['search_results'])) {
                        echo wp_kses_post(urldecode($_GET['search_results']));
                    }
                    ?>
                </div>
            </div>

            <div id="tab-2" class="tab-content" style="display:none;">
                <?php
                $default_org = get_option('rrze_faudir_options', [])['default_organization'] ?? null;
                if ($default_org) : ?>
                    <div id="default-organization">
                        <h2><?php echo esc_html__('Current Default Organization', 'rrze-faudir'); ?></h2>
                        <p><?php echo esc_html__('This is the organization that will be used by default in shortcodes and blocks.', 'rrze-faudir'); ?></p>
                        <p><strong><?php echo esc_html(__('Name', 'rrze-faudir')); ?>:</strong> <?php echo esc_html($default_org['name']); ?></p>
                        <p><strong><?php echo esc_html(__('Organization Number', 'rrze-faudir')); ?>:</strong> <?php echo esc_html($default_org['orgnr']); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                            <?php wp_nonce_field('delete_default_organization'); ?>
                            <input type="hidden" name="action" value="delete_default_organization">
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete the default organization?', 'rrze-faudir')); ?>');">
                                <?php echo esc_html__('Delete Default Organization', 'rrze-faudir'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <h2><?php echo esc_html__('Search Organizations', 'rrze-faudir'); ?></h2>
                <form id="search-org-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="rrze_faudir_search_org">
                    <?php wp_nonce_field('rrze_faudir_search_org', 'rrze_faudir_search_org_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="org-search"><?php echo esc_html__('Search Term', 'rrze-faudir'); ?></label></th>
                            <td>
                                <input type="text" id="org-search" name="org-search" class="regular-text" />
                                <p class="description"><?php echo esc_html__('Enter organization name or identifier', 'rrze-faudir'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Search', 'rrze-faudir'); ?></button>
                </form>

                <div id="organizations-list">
                    <?php
                    if (isset($_GET['org_search_results'])) {
                        echo wp_kses_post(urldecode($_GET['org_search_results']));
                    }
                    ?>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                $($(this).attr('href')).show();
            });

            $('#reset-to-defaults-button').on('click', function() {
                if (confirm('<?php echo esc_js(esc_html__('Are you sure you want to reset all settings to their default values?', 'rrze-faudir')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'rrze_faudir_reset_defaults',
                        security: '<?php echo esc_js(wp_create_nonce('rrze_faudir_reset_defaults_nonce')); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php echo esc_js(esc_html__('Settings have been reset to default values.', 'rrze-faudir')); ?>');
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(esc_html__('Failed to reset settings. Please try again.', 'rrze-faudir')); ?>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Löscht die Default-Organisation (admin-post).
     * Optionale Eingaben: $_POST/Nonce (intern).
     * Rückgabe: void (Redirect).
     */
    public function delete_default_organization(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'rrze-faudir'));
        }
        check_admin_referer('delete_default_organization');

        $options = get_option('rrze_faudir_options', []);
        if (isset($options['default_organization'])) {
            unset($options['default_organization']);
            $options['default_organization'] = null;
            update_option('rrze_faudir_options', $options);
            add_settings_error('rrze_faudir_messages', 'default_org_deleted', __('Default organization has been deleted.', 'rrze-faudir'), 'updated');
        }

        wp_redirect(add_query_arg(['page' => 'rrze-faudir', 'settings-updated' => 'true'], admin_url('options-general.php')));
        exit;
    }

    /**
     * Setzt alle Optionen auf Defaults zurück (AJAX).
     * Optionale Eingaben: Nonce.
     * Rückgabe: JSON (success).
     */
    public function reset_defaults(): void {
        check_ajax_referer('rrze_faudir_reset_defaults_nonce', 'security');

        $config = new Config();
        $default_settings = $config->getAll();
        update_option('rrze_faudir_options', $default_settings);

        wp_send_json_success(__('Settings have been reset to default values.', 'rrze-faudir'));
    }

    /**
     * Löscht alle FAUdir-Transients (AJAX).
     * Optionale Eingaben: keine.
     * Rückgabe: JSON (success).
     */
    public function clear_cache(): void {
        global $wpdb;

        $prefix         = '_transient_faudir_';
        $prefix_timeout = '_transient_timeout_faudir_';

        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix_timeout . '%'));

        wp_send_json_success(__('All cache cleared successfully.', 'rrze-faudir'));
    }

    /**
     * Startet manuellen Migration-Run aus der Settings-Seite (AJAX).
     * Optionale Eingaben: keine.
     * Rückgabe: JSON (Plaintext der Notice).
     */
    public function import_fau_person(): void {
        $config = new Config();
        $config->insertOptions();

        $mnt = new Maintenance($config);
        $mnt->migrate_person_data_on_activation();

        $html = $mnt->rrze_faudir_display_import_notice(false, false);
        $result = '';
        if (!empty($html)) {
            $html   = preg_replace('#<(br|BR)\s*/?>#', "\n", $html);
            $html   = preg_replace('#</?(p|div|li|tr|h[1-6])[^>]*>#i', "\n", $html);
            $text   = strip_tags($html);
            $text   = preg_replace("/[\r\n]+/", "\n", $text);
            $result = trim($text);
        }
        wp_send_json_success($result);
    }

    /**
     * Sucht Personen via API (AJAX) und rendert Ergebnisliste (HTML).
     * Optionale Eingaben (POST): person_id, given_name, family_name, email, include_default_org.
     * Rückgabe: JSON (HTML oder Fehlermeldung).
     */
    public function search_person_ajax(): void {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');

        $personId          = isset($_POST['person_id'])   ? sanitize_text_field($_POST['person_id'])   : '';
        $givenName         = isset($_POST['given_name'])  ? rawurlencode(sanitize_text_field($_POST['given_name']))  : '';
        $familyName        = isset($_POST['family_name']) ? rawurlencode(sanitize_text_field($_POST['family_name'])) : '';
        $email             = isset($_POST['email'])       ? sanitize_email($_POST['email'])            : '';
        
        
        $includeDefaultOrg = isset($_POST['include_default_org']) && $_POST['include_default_org'] === '1';

        $defaultOrg    = get_option('rrze_faudir_options', [])['default_organization'] ?? null;
        $defaultOrgIds = $defaultOrg ? $defaultOrg['ids'] : [];

        $queryParts = [];
        if (!empty($personId))  { $queryParts[] = 'identifier=' . $personId; }
        if (!empty($givenName)) { $queryParts[] = 'givenName[ireg]=' . $givenName; }
        if (!empty($familyName)) { $queryParts[] = 'familyName[ireg]=' . $familyName; }

        $config = new Config();
        $post_type = $config->get('person_post_type');
        $api    = new API($config);

        if (!empty($email)) {
            $response = $api->getContacts(1, 0, ['lq' => 'workplaces.mails[ireg]=' . $email]);
            if (empty($response['data'])) {
                $queryParts[] = 'email[ireg]=' . $email;
            } else {
                $personId = $response['data'][0]['person']['identifier'];
                $queryParts[] = 'identifier=' . $personId;
            }
        }
        if ($includeDefaultOrg && !empty($defaultOrgIds)) {
            $queryParts[] = 'contacts.organization.identifier[reg]=^(' . implode('|', $defaultOrgIds) . ')$';
        }

        $params   = ['lq' => implode('&', $queryParts)];
        $response = $api->getPersons(60, 0, $params);

        foreach ($response['data'] ?? [] as $key => $person) {
            $response['data'][$key]['contacts'] = FaudirUtils::filterContactsByCriteria(
                $person['contacts'],
                $includeDefaultOrg,
                $defaultOrgIds,
                $email
            );
        }

        if (is_string($response)) {
            wp_send_json_error(sprintf(__('Error: %s', 'rrze-faudir'), $response));
            return;
        }

        $contacts = $response['data'] ?? [];
        if (!empty($contacts)) {
            $output = '<div class="contacts-wrapper">';
            foreach ($contacts as $contact) {
                $personalTitle = isset($contact['personalTitle']) ? $contact['personalTitle'] . ' ' : '';
                $name = esc_html($personalTitle . $contact['givenName'] . ' ' . $contact['familyName']);
                $identifier = esc_html($contact['identifier']);

                $output .= '<div class="contact-card">';
                $output .= "<h2 class='contact-name'>{$name}</h2>";
                $output .= "<div class='contact-details'>";
                $output .= "<p><strong>API-Person-Identifier:</strong> {$identifier}</p>";

                if (!empty($contact['email'])) {
                    $output .= "<p><strong>Email:</strong> " . esc_html($contact['email']) . "</p>";
                }
                if (!empty($contact['contacts'])) {
                    foreach ($contact['contacts'] as $contactDetail) {
                        $orgName = esc_html($contactDetail['organization']['name']);
                        $functionLabel = esc_html($contactDetail['functionLabel']['en']);
                        $output .= "<p><strong>" . __('Organization', 'rrze-faudir') . ":</strong> {$orgName} ({$functionLabel})</p>";
                    }
                }
                $output .= "</div>";

                $existing_post = get_posts([
                    'post_type'      => $post_type,
                    'meta_key'       => 'person_id',
                    'meta_value'     => $identifier,
                    'posts_per_page' => 1
                ]);

                if (!empty($existing_post)) {
                    $edit_link = get_edit_post_link($existing_post[0]->ID);
                    $output .= "<a href='" . esc_url($edit_link) . "' class='edit-person button'><span class='dashicons dashicons-edit'></span> " . esc_html__('Edit', 'rrze-faudir') . "</a>";
                } else {
                    $output .= "<button class='add-person button' data-name='" . esc_attr($name) . "' data-id='" . esc_attr($identifier) . "' data-include-default-org='" . ($includeDefaultOrg ? '1' : '0') . "'><span class='dashicons dashicons-plus'></span> Add</button>";
                }

                $output .= '</div>';
            }
            $output .= '</div>';
            wp_send_json_success($output);
        } else {
            wp_send_json_error(__('No contacts found. Please verify the IdM-Kennung or names provided.', 'rrze-faudir'));
        }
    }

    /**
     * Sucht Organisationen via API (AJAX) und rendert Ergebnisliste (HTML).
     * Optionale Eingaben (POST): search_term.
     * Rückgabe: JSON (HTML oder Fehlermeldung).
     */
    public function search_org_callback(): void {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');

        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if (empty($search_term)) {
            wp_send_json_error(__('Please enter a search term', 'rrze-faudir'));
            return;
        }

        $params = [];
        if (preg_match('/^\d+$/', $search_term)) {
            $params['orgnr'] = $search_term;
        } else {
            $params['q'] = $search_term;
        }

        $config   = new Config();
        $api      = new API($config);
        $response = $api->getOrgList(20, 0, $params);

        if (is_string($response)) {
            wp_send_json_error(sprintf(__('Error: %s', 'rrze-faudir'), $response));
            return;
        }

        $organizations = $response['data'] ?? [];
        if (empty($organizations)) {
            wp_send_json_error(__('No organizations found. Please try a different search term.', 'rrze-faudir'));
            return;
        }

        $output = '<div class="organizations-wrapper">';
        foreach ($organizations as $org) {
            $name       = esc_html($org['name']);
            $identifier = esc_html($org['identifier']);
            $disambiguatingDescription = !empty($org['disambiguatingDescription']) ? esc_html($org['disambiguatingDescription']) : '';

            $subOrganizations = $org['subOrganization'] ?? [];
            $identifiers = array_map(function ($subOrg) { return $subOrg['identifier']; }, $subOrganizations);
            $identifiers[] = $org['identifier'];

            $output .= '<div class="organization-card">';
            $output .= "<h2 class='organization-name'>{$name}</h2>";
            $output .= "<div class='organization-details'>";
            $output .= "<p><strong>" . __('Organization ID', 'rrze-faudir') . ":</strong> {$identifier}</p>";
            if (!empty($disambiguatingDescription)) {
                $output .= "<p><strong>" . __('Organization Number', 'rrze-faudir') . ":</strong> {$disambiguatingDescription}</p>";
            }
            if (!empty($org['parentOrganization'])) {
                $parent_name = esc_html($org['parentOrganization']['name']);
                $output .= "<p><strong>" . __('Parent Organization', 'rrze-faudir') . ":</strong> {$parent_name}</p>";
            }
            if (!empty($org['type'])) {
                $type = esc_html($org['type']);
                $output .= "<p><strong>" . __('Type', 'rrze-faudir') . ":</strong> {$type}</p>";
            }
            if (!empty($org['address'])) {
                $output .= "<div class='organization-address'><h3>" . __('Address', 'rrze-faudir') . "</h3>";
                if (!empty($org['address']['street'])) { $output .= "<p>" . esc_html($org['address']['street']) . "</p>"; }
                if (!empty($org['address']['zip']) || !empty($org['address']['city'])) {
                    $output .= "<p>" . esc_html($org['address']['zip'] ?? '') . " " . esc_html($org['address']['city'] ?? '') . "</p>";
                }
                if (!empty($org['address']['phone'])) { $output .= "<p><strong>" . __('Phone', 'rrze-faudir') . ":</strong> " . esc_html($org['address']['phone']) . "</p>"; }
                if (!empty($org['address']['mail']))  { $output .= "<p><strong>" . __('Email', 'rrze-faudir') . ":</strong> " . esc_html($org['address']['mail']) . "</p>"; }
                if (!empty($org['address']['url']))   { $output .= "<p><strong>" . __('Website', 'rrze-faudir') . ":</strong> <a href='" . esc_url($org['address']['url']) . "' target='_blank'>" . esc_html($org['address']['url']) . "</a></p>"; }
                $output .= "</div>";
            }
            $output .= "</div>";

            $output .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display: inline;">';
            $output .= wp_nonce_field('save_default_organization', '_wpnonce', true, false);
            $output .= '<input type="hidden" name="action" value="save_default_organization">';
            $output .= '<input type="hidden" name="org_ids" value="' . esc_attr(json_encode($identifiers)) . '">';
            $output .= '<input type="hidden" name="org_name" value="' . esc_attr($name) . '">';
            $output .= '<input type="hidden" name="org_nr" value="' . esc_attr($disambiguatingDescription) . '">';
            $output .= '<button type="submit" class="button button-primary">' . esc_html__('Save as Default Organization', 'rrze-faudir') . '</button>';
            $output .= '</form>';

            $output .= '</div>';
        }
        $output .= '</div>';

        wp_send_json_success($output);
    }

    /**
     * Speichert die Default-Organisation (admin-post).
     * Optionale Eingaben (POST): org_ids (JSON), org_name, org_nr.
     * Rückgabe: void (Redirect).
     */
    public function save_default_organization(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'rrze-faudir'));
        }
        check_admin_referer('save_default_organization');

        $org_ids = [];
        if (isset($_POST['org_ids'])) {
            $decoded = json_decode(stripslashes((string) $_POST['org_ids']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $org_ids = $decoded;
            }
        }

        $org_name = isset($_POST['org_name']) ? sanitize_text_field($_POST['org_name']) : '';
        $org_nr   = isset($_POST['org_nr'])   ? sanitize_text_field($_POST['org_nr'])   : '';

        if (!empty($org_ids) && !empty($org_name)) {
            $options = get_option('rrze_faudir_options', []);
            $options['default_organization'] = [
                'ids'   => $org_ids,
                'name'  => $org_name,
                'orgnr' => $org_nr,
            ];
            update_option('rrze_faudir_options', $options);

            add_settings_error('rrze_faudir_messages', 'default_org_saved', __('Default organization has been saved.', 'rrze-faudir'), 'updated');
        }

        wp_redirect(add_query_arg(['page' => 'rrze-faudir', 'settings-updated' => 'true'], admin_url('options-general.php')));
        exit;
    }

    /**
     * Sanitizer für rrze_faudir_options – erhält default_organization, falls nicht neu übermittelt.
     * Optionale Eingaben: $new_options (array).
     * Rückgabe: array (bereinigte Optionen).
     */
    public function sanitize_options(array $new_options): array {
        $existing_options = get_option('rrze_faudir_options', []);
        if (isset($existing_options['default_organization']) && !array_key_exists('default_organization', $new_options)) {
            $new_options['default_organization'] = $existing_options['default_organization'];
        }
        return $new_options;
    }
}
