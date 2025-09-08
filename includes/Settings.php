<?php
namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Config;
use RRZE\FAUdir\API;
use RRZE\FAUdir\Maintenance;

class Settings {

    public function __construct() {}

    /* -----------------------------
     * Hooks
     * ----------------------------- */
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

    public function add_admin_menu(): void {
        add_options_page(
            __('RRZE FAUdir Settings', 'rrze-faudir'),
            __('RRZE FAUdir', 'rrze-faudir'),
            'manage_options',
            'rrze-faudir',
            [$this, 'settings_page']
        );
    }

    /* -----------------------------
     * Settings init (Sections & Fields)
     * ----------------------------- */
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

        // Alle Sections & Fields sauber in einer Methode registrieren
        $this->register_sections_and_fields();
    }

    private function register_sections_and_fields(): void {
        /* --- Suche: Personen --- */
        add_settings_section(
            'rrze_faudir_contacts_search_section',
            __('Search Contacts', 'rrze-faudir'),
            [$this, 'contacts_search_section_cb'],
            'rrze_faudir_settings_contacts_search'
        );

        /* --- Suche: Organisationen --- */
        add_settings_section(
            'rrze_faudir_org_search_section',
            __('Search Organizations', 'rrze-faudir'),
            [$this, 'org_search_section_cb'],
            'rrze_faudir_settings_org_search'
        );

        /* --- Shortcodes: Personen-Ausgabe --- */
        add_settings_section(
            'rrze_faudir_shortcode_section',
            __('Default output fields for persons', 'rrze-faudir'),
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

        /* --- Profilseite --- */
        add_settings_section(
            'rrze_faudir_profilpage_section',
            __('Profilpage', 'rrze-faudir'),
            [$this, 'profilpage_section_cb'],
            'rrze_faudir_settings_profilpage'
        );
        add_settings_field(
            'rrze_faudir_profilpage_output_fields',
            __('Data fields that are shown on the profil page', 'rrze-faudir'),
            [$this, 'render_profilpage_output_fields'],
            'rrze_faudir_settings_profilpage',
            'rrze_faudir_profilpage_section'
        );

        /* --- Shortcodes: ORG/Folders-Ausgabe --- */
        add_settings_section(
            'rrze_faudir_shortcode_org_section',
            __('Default output fields for organisations and folders', 'rrze-faudir'),
            [$this, 'shortcode_section_cb'],
            'rrze_faudir_settings_org_shortcode'
        );
        add_settings_field(
            'rrze_faudir_default_org_output_fields',
            __('Output fields for formats', 'rrze-faudir'),
            [$this, 'default_output_org_fields_render'],
            'rrze_faudir_settings_org_shortcode',
            'rrze_faudir_shortcode_org_section'
        );

        /* --- API --- */
        add_settings_section(
            'rrze_faudir_api_section',
            __('API Settings', 'rrze-faudir'),
            [$this, 'api_section_cb'],
            'rrze_faudir_settings_api' 
        );

        // Felder der API-Sektion registrieren
        add_settings_field(
            'rrze_faudir_api_key',
            __('API Key', 'rrze-faudir'),
            [$this, 'api_key_render'],
            'rrze_faudir_settings_api',
            'rrze_faudir_api_section'
        );

        if (is_plugin_active('fau-person/fau-person.php')) {
            add_settings_field(
                'rrze_faudir_import_fau_person',
                __('Import', 'rrze-faudir'),
                [$this, 'import_fau_person_render'],
                'rrze_faudir_settings_api',
                'rrze_faudir_api_section'
            );
        }


        /* --- Cache --- */
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

       

        /* --- Sonstiges --- */
        add_settings_section(
            'rrze_faudir_misc_section',
            __('General Settings', 'rrze-faudir'),
            [$this, 'misc_section_cb'],
            'rrze_faudir_settings_uri'
        );
        add_settings_field(
            'rrze_faudir_person_slug',
            __('Person Slug', 'rrze-faudir'),
            [$this, 'render_person_slug'],
            'rrze_faudir_settings_uri',
            'rrze_faudir_misc_section'
        );
        add_settings_field(
            'rrze_faudir_redirect_archivpage_uri',
            __('Index page', 'rrze-faudir'),
            [$this, 'misc_section_message_render'],
            'rrze_faudir_settings_uri',
            'rrze_faudir_misc_section'
        );
        
        add_settings_field(
            'rrze_faudir_default_normalize_honorificPrefix',
            __('Normalize Honorific Prefix', 'rrze-faudir'),
            [$this, 'render_normalize_honorific_prefix'],
            'rrze_faudir_settings_uri',     
            'rrze_faudir_misc_section'     
        );
        
         /* --- Fehlerbehandlung --- */
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
        
        
    }

    /* -----------------------------
     * Tab-Definition (zentral)
     * ----------------------------- */
    private function get_tabs(): array {
        return [
            'general' => [
                'label' => __('General', 'rrze-faudir'),
                'type'  => 'form',
                'group' => 'rrze_faudir_settings',
                'pages' => [
                    'rrze_faudir_settings_uri',   
                    'rrze_faudir_settings_error', 
                ],
                'after' => [$this, 'render_orgs_search_tab'],
            ],
            'contacts' => [
                'label'  => __('Search Contacts', 'rrze-faudir'),
                'type'   => 'custom',
                'render' => [$this, 'render_contacts_tab'],
            ],
           
            'output' => [
                'label' => __('Default Output Fields (Persons)', 'rrze-faudir'),
                'type'  => 'form',
                'group' => 'rrze_faudir_settings',
                'pages' => ['rrze_faudir_settings_shortcode'],
            ],
            'profile' => [
                'label' => __('Profile Page', 'rrze-faudir'),
                'type'  => 'form',
                'group' => 'rrze_faudir_settings',
                'pages' => ['rrze_faudir_settings_profilpage'],
            ],
            'org_output' => [
                'label' => __('Default Output Fields (Organizations/Folders)', 'rrze-faudir'),
                'type'  => 'form',
                'group' => 'rrze_faudir_settings',
                'pages' => ['rrze_faudir_settings_org_shortcode'],
            ],
            'advanced' => [
                'label' => __('Advanced Settings', 'rrze-faudir'),
                'type'  => 'form',
                'group' => 'rrze_faudir_settings',
                'pages' => [
                    'rrze_faudir_settings_api',
                    'rrze_faudir_settings_cache'
                ],
                'after' => [$this, 'render_reset_box'],
            ],
        ];
    }

    /* -----------------------------
     * Settings-Seite (Tabs + Inhalt)
     * ----------------------------- */
    public function settings_page(): void {
        $tabs   = $this->get_tabs();
        $active = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : array_key_first($tabs);
        if (!isset($tabs[$active])) {
            $active = array_key_first($tabs);
        }

        echo '<div class="wrap faudir-settings">';
        echo '<h1>' . esc_html__('FAUdir Settings', 'rrze-faudir') . '</h1>';

        $this->render_nav($tabs, $active);

        echo '<div class="tab-content">';
        $tab = $tabs[$active];

        if ($tab['type'] === 'form') {
            $this->render_settings_form($tab['group'], $tab['pages'], $tab['after'] ?? null);
        } elseif ($tab['type'] === 'custom' && is_callable($tab['render'])) {
            call_user_func($tab['render']);
        } else {
            echo '<p>' . esc_html__('Nothing to display.', 'rrze-faudir') . '</p>';
        }

        echo '</div></div>';
    }

    private function render_nav(array $tabs, string $active): void {
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $key => $tab) {
            $url   = add_query_arg(['page' => 'rrze-faudir', 'tab' => $key], admin_url('options-general.php'));
            $class = 'nav-tab' . ($active === $key ? ' nav-tab-active' : '');
            echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($tab['label']) . '</a>';
        }
        echo '</h2>';
    }

    private function render_settings_form(string $group, array $pages, ?callable $after = null): void {
        echo '<form action="options.php" method="post">';
        settings_fields($group);
        foreach ($pages as $page) {
            do_settings_sections($page);
            echo '<hr>';
        }
        submit_button();
        echo '</form>';
        if (is_callable($after)) {
            call_user_func($after);
        }
    }

    /* -----------------------------
     * Custom-Tab: Kontakte suchen
     * ----------------------------- */
    public function render_contacts_tab(): void {
        ?>
        <h2><?php echo esc_html__('Search Contacts', 'rrze-faudir'); ?></h2>

        <form id="search-person-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="rrze_faudir_search_person">
            <?php wp_nonce_field('rrze_faudir_search_person', 'rrze_faudir_search_nonce'); ?>

            <table class="form-table"><tbody>
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
                    <th scope="row"><label for="person-id"><?php echo esc_html__('Search Terms', 'rrze-faudir'); ?></label></th>
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
            </tbody></table>

            <button type="submit" class="button button-primary" disabled><?php echo esc_html__('Search', 'rrze-faudir'); ?></button>
        </form>

        <div id="contacts-list">
            <?php
            if (isset($_GET['search_results'])) {
                echo wp_kses_post(urldecode($_GET['search_results']));
            }
            ?>
        </div>
        <?php
    }

    /* -----------------------------
     * Custom-Tab: Organisationen suchen
     * ----------------------------- */
    public function render_orgs_search_tab(): void {
        // Aktuelle Default-Org (Anzeige + Löschen)
        $default_org = get_option('rrze_faudir_options', [])['default_organization'] ?? null;

        echo '<hr><h2>' . esc_html__('Organizations', 'rrze-faudir') . '</h2>';

        if ($default_org) {
            echo '<div id="default-organization">';
            echo '<h3>' . esc_html__('Current Default Organization', 'rrze-faudir') . '</h3>';
            echo '<p>' . esc_html__('This is the organization that will be used by default in shortcodes and blocks.', 'rrze-faudir') . '</p>';
            echo '<p><strong>' . esc_html__('Name', 'rrze-faudir') . ':</strong> ' . esc_html($default_org['name']) . '</p>';
            echo '<p><strong>' . esc_html__('Organization Number', 'rrze-faudir') . ':</strong> ' . esc_html($default_org['orgnr']) . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
            wp_nonce_field('delete_default_organization');
            echo '<input type="hidden" name="action" value="delete_default_organization">';
            echo '<button type="submit" class="button button-secondary" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete the default organization?', 'rrze-faudir')) . '\');">';
            echo esc_html__('Delete Default Organization', 'rrze-faudir') . '</button></form>';
            echo '</div>';
        }

        // Such-Form + Ergebnis-Container (IDs bleiben gleich -> AJAX weiter funktionsfähig)
        echo '<h3>' . esc_html__('Search Organizations', 'rrze-faudir') . '</h3>';
        echo '<form id="search-org-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="rrze_faudir_search_org">';
        wp_nonce_field('rrze_faudir_search_org', 'rrze_faudir_search_org_nonce');
        echo '<table class="form-table"><tr>';
        echo '<th scope="row"><label for="org-search">' . esc_html__('Search Term', 'rrze-faudir') . '</label></th>';
        echo '<td><input type="text" id="org-search" name="org-search" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter organization name or identifier', 'rrze-faudir') . '</p>';
        echo '</td></tr></table>';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Search', 'rrze-faudir') . '</button>';
        echo '</form>';

        echo '<div id="organizations-list"></div>';
    }


    /* -----------------------------
     * Reset-Box (unter Advanced)
     * ----------------------------- */
    public function render_reset_box(): void {
        ?>
        <hr>
        <div class="danger-zone">
            <h3><?php echo esc_html__('Reset to Default Settings', 'rrze-faudir'); ?></h3>
            <p><?php echo esc_html__('Click the button below to reset all settings to their default values.', 'rrze-faudir'); ?></p>
            <button type="button" class="button button-secondary" id="reset-to-defaults-button">
                <?php echo esc_html__('Reset to Default Values', 'rrze-faudir'); ?>
            </button>
        </div>
        <script>
        (function($){
            $('#reset-to-defaults-button').on('click', function(){
                if (!confirm('<?php echo esc_js(__('Are you sure you want to reset all settings to their default values?', 'rrze-faudir')); ?>')) return;
                $.post(ajaxurl, {
                    action: 'rrze_faudir_reset_defaults',
                    security: '<?php echo esc_js(wp_create_nonce('rrze_faudir_reset_defaults_nonce')); ?>'
                }).done(function(resp){
                    if (resp && resp.success) {
                        alert('<?php echo esc_js(__('Settings have been reset to default values.', 'rrze-faudir')); ?>');
                        location.reload();
                    } else {
                        alert('<?php echo esc_js(__('Failed to reset settings. Please try again.', 'rrze-faudir')); ?>');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /* -----------------------------
     * Section Callbacks
     * ----------------------------- */
    public function api_section_cb(): void {
        echo '<p>' . esc_html__('Configure the API settings for accessing the FAU person and institution directory.', 'rrze-faudir') . '</p>';
    }
    public function cache_section_cb(): void {
        echo '<p>' . esc_html__('Configure caching settings for the plugin.', 'rrze-faudir') . '</p>';
    }
    public function error_section_cb(): void {
        echo '<p>' . esc_html__('Handle error messages for invalid contact entries.', 'rrze-faudir') . '</p>';
    }
    public function shortcode_section_cb(): void {
        echo '<p>' . esc_html__('Configure the default fields for the output formats.', 'rrze-faudir') . '</p>';
    }
    public function profilpage_section_cb(): void {
        echo '<p>' . esc_html__('Configure the default output fields for the profile page of a single person.', 'rrze-faudir') . '</p>';
    }
    public function misc_section_cb(): void {
        echo '<p>' . esc_html__('Configure other advanced settings.', 'rrze-faudir') . '</p>';
    }
    public function contacts_search_section_cb(): void {
        echo '<p>' . esc_html__('Search for FAU contacts by ID, name or email.', 'rrze-faudir') . '</p>';
    }
    public function org_search_section_cb(): void {
        echo '<p>' . esc_html__('Search for FAU organizations by name or identifier.', 'rrze-faudir') . '</p>';
    }

    /* -----------------------------
     * Feld-Renderer (aus deinem Code)
     * ----------------------------- */
    public function render_profilpage_output_fields(): void {
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

    public function import_fau_person_render(): void {
        echo '<button type="button" class="button button-secondary" id="import-fau-person-button">' . esc_html__('Import contact entries from FAU Person', 'rrze-faudir') . '</button>';
        echo '<p class="description">' . esc_html__('Click the button to restart the import of contact entries from FAU Person. Notice, that this will only import contact entries, which refer to a public viewable person in FAUdir.', 'rrze-faudir') . '</p>';
    }

    public function no_cache_logged_in_render(): void {
        $options = get_option('rrze_faudir_options');
        $checked = !empty($options['no_cache_logged_in']);

        echo '<label>';
        // Hidden-Fallback, damit Abwählen als 0 gepostet wird
        echo '<input type="hidden" name="rrze_faudir_options[no_cache_logged_in]" value="0">';
        echo '<input type="checkbox" name="rrze_faudir_options[no_cache_logged_in]" value="1" ' . checked(true, $checked, false) . '>';
        echo '<span>' . esc_html__('Disable caching for logged-in editors.', 'rrze-faudir') . '</span>';
        echo '</label>';
    }


    public function cache_timeout_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['cache_timeout']) ? max((int)$options['cache_timeout'], 15) : 15;
        echo '<label><input type="number" name="rrze_faudir_options[cache_timeout]" value="' . esc_attr($value) . '" min="15">';
        echo '<p class="description">' . esc_html__('Set the cache timeout in minutes (minimum 15 minutes).', 'rrze-faudir') . '</p></label>';
    }

    public function transient_time_for_org_id_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['transient_time_for_org_id']) ? max((int)$options['transient_time_for_org_id'], 1) : 1;
        echo '<input type="number" name="rrze_faudir_options[transient_time_for_org_id]" value="' . esc_attr($value) . '" min="1">';
        echo '<p class="description">' . esc_html__('Set the transient time in days for intermediate stored organization identifiers (minimum 1 day).', 'rrze-faudir') . '</p>';
    }

    public function cache_org_timeout_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['cache_org_timeout']) ? (int)$options['cache_org_timeout'] : 1;
        echo '<label><input type="number" name="rrze_faudir_options[cache_org_timeout]" value="' . esc_attr($value) . '" min="1">';
        echo '<p class="description">' . esc_html__('Set the cache timeout in days for organization identifiers.', 'rrze-faudir') . '</p></label>';
    }

    public function clear_cache_render(): void {
        echo '<button type="button" class="button button-secondary" id="clear-cache-button">' . esc_html__('Clear Cache Now', 'rrze-faudir') . '</button>';
        echo '<p class="description">' . esc_html__('Click the button to clear all cached data.', 'rrze-faudir') . '</p>';
    }

    public function error_message_render(): void {
        $options = get_option('rrze_faudir_options');
        if (!isset($options['show_error_message'])) {
            $config = new Config();
            $options['show_error_message'] = (int) $config->get('show_error_message');
        }
        $checked = !empty($options['show_error_message']);

        echo '<label>';
        echo '<input type="hidden" name="rrze_faudir_options[show_error_message]" value="0">';
        echo '<input type="checkbox" name="rrze_faudir_options[show_error_message]" value="1" ' . checked(true, $checked, false) . '>';
        echo '<span>' . esc_html__('Show error messages for incorrect contact entries.', 'rrze-faudir') . '</span>';
        echo '</label>';
    }


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

   public function fallback_link_faudir_render(): void {
        $options = get_option('rrze_faudir_options');
        $checked = !empty($options['fallback_link_faudir']);

        echo '<label>';
        echo '<input type="hidden" name="rrze_faudir_options[fallback_link_faudir]" value="0">';
        echo '<input type="checkbox" name="rrze_faudir_options[fallback_link_faudir]" value="1" ' . checked(true, $checked, false) . '>';
        echo '<span>' . esc_html__('On using profil links, fallback to the public faudir portal, if no local custom post is avaible.', 'rrze-faudir') . '</span>';
        echo '</label>';
    }


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

    public function misc_section_message_render(): void {
        $options = get_option('rrze_faudir_options');
        $value   = isset($options['redirect_archivpage_uri']) ? esc_url($options['redirect_archivpage_uri']) : '';

        echo '<label><input type="text" name="rrze_faudir_options[redirect_archivpage_uri]" value="' . esc_attr($value) . '" class="regular-text" placeholder="/">';
        echo '<p class="description">' . esc_html__('Optional: Path to a local page, which is used as index for all contact entries.', 'rrze-faudir') . '</p></label>';
    }

    public function render_person_slug(): void {
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

    public function render_normalize_honorific_prefix(): void {
        $options = get_option('rrze_faudir_options');
        if (!isset($options['default_normalize_honorificPrefix'])) {
            $config = new Config();
            $options['default_normalize_honorificPrefix'] = (int) $config->get('default_normalize_honorificPrefix');
        }
        $checked = !empty($options['default_normalize_honorificPrefix']);
        
        
        echo '<label>';
        echo '<input type="hidden" name="rrze_faudir_options[default_normalize_honorificPrefix]" value="0">';   
        echo '<input type="checkbox" name="rrze_faudir_options[default_normalize_honorificPrefix]" value="1" ' . checked(1, $checked, false) . ' />';
        echo ' <span>' . esc_html__('Use official short form of the academic degree', 'rrze-faudir') . '</span>';
        echo '</label>';
    }

    /* -----------------------------
     * Default-Ausgabefelder (Persons) – gefiltert (keine org-*)
     * ----------------------------- */
    public function default_output_fields_render(): void {
        $options        = get_option('rrze_faudir_options');
        $default_fields = $options['default_output_fields'] ?? [];

        $config           = new Config();
        $available_fields = (array) $config->get('avaible_fields');
        $formatnames      = (array) $config->get('formatnames');
        $fieldlist        = (array) $config->getAvaibleFieldlist();

        $person_fields = array_filter(
            $available_fields,
            static fn($label, $key) => strpos((string) $key, 'org-') !== 0,
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($default_fields)) {
            $default_fields = array_keys($person_fields);
        } else {
            $default_fields = array_values(array_filter(
                (array) $default_fields,
                static fn($f) => strpos((string) $f, 'org-') !== 0
            ));
        }

        echo '<table class="faudir-attributs">';
        echo '<tr><th>' . esc_html__('Output data', 'rrze-faudir') . '</th><th>' . esc_html__('Fieldname for Show/Hide-Attribut in Shortcodes', 'rrze-faudir') . '</th><th>' . esc_html__('Avaible in formats', 'rrze-faudir') . '</th></tr>';

        foreach ($person_fields as $field => $label) {
            $checked = in_array($field, $default_fields, true);
            echo '<tr>';
            echo '<th><label for="' . esc_attr('rrze_faudir_default_output_fields_' . $field) . '">';
            echo '<input type="checkbox" id="' . esc_attr('rrze_faudir_default_output_fields_' . $field) . '" name="rrze_faudir_options[default_output_fields][]" value="' . esc_attr($field) . '" ' . checked($checked, true, false) . '>';
            echo esc_html($label) . '</label></th>';
            echo '<td><code>' . esc_html($field) . '</code></td><td>';

            $canuse = '';
            foreach ($fieldlist as $fl => $entries) {
                if (strpos((string) $fl, 'org-') === 0) { continue; }
                if (!empty($entries) && in_array($field, (array) $entries, true)) {
                    if (!empty($canuse)) { $canuse .= ', '; }
                    $canuse .= ($formatnames[$fl] ?? $fl) . ' (<code>' . esc_html($fl) . '</code>)';
                }
            }
            if (!empty($canuse)) { echo $canuse; }
            echo '</td></tr>';
        }
        echo '</table>';
        echo '<p class="description">' . esc_html__('Select the fields to display by default in shortcodes and blocks.', 'rrze-faudir') . '</p>';
    }

    /* -----------------------------
     * Default-Ausgabefelder (Orgs/Folders) – nur Felder, die in org-* Formaten vorkommen
     * ----------------------------- */
    public function default_output_org_fields_render(): void {
        $options = get_option('rrze_faudir_options');

        $config           = new Config();
        $available_fields = (array) $config->get('avaible_fields_org');   // fieldKey => Label
        $formatnames      = (array) $config->get('formatnames');          // formatKey => Name
        $fieldlist        = (array) $config->getAvaibleFieldlist();       // formatKey => [fieldKey, ...]

        $org_format_keys = array_values(array_filter(
            array_keys($fieldlist),
            static fn($k) => is_string($k) && strpos($k, 'org-') === 0 && is_array($fieldlist[$k])
        ));

        $org_field_keys_map = [];
        foreach ($org_format_keys as $fmt) {
            foreach ((array) $fieldlist[$fmt] as $f) {
                $org_field_keys_map[$f] = true;
            }
        }
        $org_field_keys = array_keys($org_field_keys_map);

        if (empty($org_field_keys)) {
            echo '<p class="description">' .
                 esc_html__('No organization/folder fields available for selection.', 'rrze-faudir') .
                 '</p>';
            return;
        }

        $org_fields = [];
        foreach ($org_field_keys as $key) {
            $org_fields[$key] = $available_fields[$key] ?? $key;
        }

        $org_default_fields = $options['default_org_output_fields'] ?? null;
        if ($org_default_fields === null) {
            $fallback_defaults = (array) ($options['default_output_fields'] ?? []);
            $org_default_fields = array_values(array_intersect($fallback_defaults, $org_field_keys));
        }
        if (empty($org_default_fields)) {
            $org_default_fields = $org_field_keys;
        }

        echo '<table class="faudir-attributs">';
        echo '<tr>'
           . '<th>' . esc_html__('Output data (organizations/folders)', 'rrze-faudir') . '</th>'
           . '<th>' . esc_html__('Fieldname for Show/Hide-Attribut in Shortcodes', 'rrze-faudir') . '</th>'
           . '<th>' . esc_html__('Avaible in formats', 'rrze-faudir') . '</th>'
           . '</tr>';

        foreach ($org_fields as $field => $label) {
            $checked = in_array($field, $org_default_fields, true);

            echo '<tr>';
            echo '<th><label for="' . esc_attr('rrze_faudir_default_org_output_fields_' . $field) . '">';
            echo '<input type="checkbox" id="' . esc_attr('rrze_faudir_default_org_output_fields_' . $field) . '" '
               . 'name="rrze_faudir_options[default_org_output_fields][]" '
               . 'value="' . esc_attr($field) . '" ' . checked($checked, true, false) . '>';
            echo esc_html($label) . '</label></th>';

            echo '<td><code>' . esc_html($field) . '</code></td>';

            $canuse_parts = [];
            foreach ($org_format_keys as $fmt) {
                $entries = (array) $fieldlist[$fmt];
                if (in_array($field, $entries, true)) {
                    $alias     = ($fmt === 'org-compact') ? 'compact' : $fmt;
                    $labelName = $formatnames[$fmt] ?? $alias;
                    $canuse_parts[] = esc_html($labelName) . ' (<code>org-' . esc_html($alias) . '</code>)';
                }
            }
            echo '<td>' . implode(', ', $canuse_parts) . '</td>';

            echo '</tr>';
        }

        echo '</table>';
        echo '<p class="description">'
           . esc_html__('Select the organization/folder fields to display by default in shortcodes and blocks.', 'rrze-faudir')
           . '</p>';
    }

    /* -----------------------------
     * Admin-Post/AJAX Handler
     * ----------------------------- */
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
 //           add_settings_error('rrze_faudir_messages', 'default_org_deleted', __('Default organization has been deleted.', 'rrze-faudir'), 'updated');
        }

        wp_redirect(add_query_arg(['page' => 'rrze-faudir', 'tab' => 'orgs', 'settings-updated' => 'true'], admin_url('options-general.php')));
        exit;
    }

    public function reset_defaults(): void {
        check_ajax_referer('rrze_faudir_reset_defaults_nonce', 'security');

        $config = new Config();
        $default_settings = $config->getAll();
        update_option('rrze_faudir_options', $default_settings);

        wp_send_json_success(__('Settings have been reset to default values.', 'rrze-faudir'));
    }

    public function clear_cache(): void {
        global $wpdb;

        $prefix         = '_transient_faudir_';
        $prefix_timeout = '_transient_timeout_faudir_';

        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix_timeout . '%'));

        wp_send_json_success(__('All cache cleared successfully.', 'rrze-faudir'));
    }

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
        if (!empty($personId))   { $queryParts[] = 'identifier=' . $personId; }
        if (!empty($givenName))  { $queryParts[] = 'givenName[ireg]=' . $givenName; }
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

    //        add_settings_error('rrze_faudir_messages', 'default_org_saved', __('Default organization has been saved.', 'rrze-faudir'), 'updated');
        }

        wp_redirect(add_query_arg(['page' => 'rrze-faudir', 'tab' => 'orgs', 'settings-updated' => 'true'], admin_url('options-general.php')));
        exit;
    }

    public function sanitize_options(array $new_options): array {
        // Bisherige Optionen laden und sicherstellen, dass es ein Array ist
        // $existing = get_option('rrze_faudir_options', []);
        $config = new \RRZE\FAUdir\Config();
        $existing = $config->getOptions();
        
        
        if (!is_array($existing)) {
            $existing = [];
        }

        // Merge: nur übermittelte Keys überschreiben, Rest behalten
        $merged = array_merge($existing, $new_options);

        // --- Skalare Felder ---
        if (array_key_exists('api_key', $new_options)) {
            $merged['api_key'] = sanitize_text_field($new_options['api_key']);
        }
        if (array_key_exists('business_card_title', $new_options)) {
            $merged['business_card_title'] = sanitize_text_field($new_options['business_card_title']);
        }
        if (array_key_exists('jobtitle_format', $new_options)) {
            $merged['jobtitle_format'] = sanitize_text_field($new_options['jobtitle_format']);
        }
        if (array_key_exists('person_slug', $new_options)) {
            $merged['person_slug'] = sanitize_title($new_options['person_slug']);
        }
        if (array_key_exists('redirect_archivpage_uri', $new_options)) {
            $merged['redirect_archivpage_uri'] = esc_url_raw($new_options['redirect_archivpage_uri']);
        }
        if (array_key_exists('cache_timeout', $new_options)) {
            $merged['cache_timeout'] = max(15, (int) $new_options['cache_timeout']);
        }
        if (array_key_exists('transient_time_for_org_id', $new_options)) {
            $merged['transient_time_for_org_id'] = max(1, (int) $new_options['transient_time_for_org_id']);
        }

        // --- Checkboxen (nur wenn im POST enthalten) ---
        $checkboxes = [
            'no_cache_logged_in',
            'fallback_link_faudir',
            'show_error_message',
            'default_normalize_honorificPrefix',
        ];

        foreach ($checkboxes as $cb) {
            if (array_key_exists($cb, $new_options)) {
                $merged[$cb] = !empty($new_options[$cb]) ? 1 : 0;
            }
        }


        // --- Arrays von Feldlisten ---
        if (array_key_exists('default_output_fields', $new_options)) {
            $merged['default_output_fields'] = array_values(array_unique(array_map(
                'sanitize_text_field',
                (array) $new_options['default_output_fields']
            )));
        }
        if (array_key_exists('default_org_output_fields', $new_options)) {
            $merged['default_org_output_fields'] = array_values(array_unique(array_map(
                'sanitize_text_field',
                (array) $new_options['default_org_output_fields']
            )));
        }
        if (array_key_exists('output_fields_endpoint', $new_options)) {
            $merged['output_fields_endpoint'] = array_values(array_unique(array_map(
                'sanitize_text_field',
                (array) $new_options['output_fields_endpoint']
            )));
        }

        // --- Strukturierte Werte (nur wenn im POST enthalten) ---
        if (array_key_exists('default_organization', $new_options) && is_array($new_options['default_organization'])) {
            $d = $new_options['default_organization'];
            $merged['default_organization'] = [
                'ids'   => array_values(array_map('sanitize_text_field', (array)($d['ids'] ?? []))),
                'name'  => sanitize_text_field($d['name'] ?? ''),
                'orgnr' => sanitize_text_field($d['orgnr'] ?? ''),
            ];
        }

        return $merged;
    }


}
