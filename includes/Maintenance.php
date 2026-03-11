<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Maintenance {
    protected Config $config;
    protected CPT $cpt;
    protected Cron $cron;
    protected Migration $migration;

    public function __construct(Config $config, CPT $cpt) {
        $this->config = $config;
        $this->cpt = $cpt;
        $this->cron = new Cron($this->config);
        $this->migration = new Migration($this->config, $this->cpt);
        
    }

    public function register_hooks(): void {
        // Aktivierungshooks
        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'on_plugin_activation']);
        register_activation_hook(RRZE_PLUGIN_FILE, [$this->cron, 'on_plugin_activation']);
        register_deactivation_hook(RRZE_PLUGIN_FILE, [$this->cron, 'on_plugin_deactivation']);

        // Hinweistext bei Aktivierung
        add_action('admin_notices', [$this, 'maybe_show_activation_notice']);
    
        // Plugin Links
        add_filter('plugin_action_links_' . plugin_basename(RRZE_PLUGIN_FILE), [$this, 'add_plugin_action_links']);
        add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta_links'], 10, 2);
        
        // Slug-Änderung überwachen
        add_action('update_option_rrze_faudir_options', [$this, 'rrze_faudir_flush_rewrite_on_slug_change'], 10, 3);

        // Cron / Scheduler
        $this->cron->register_hooks();

        // Templates / Redirects
        add_action('template_redirect', [$this, 'maybe_disable_canonical_redirect'], 1);
        add_filter('template_include', [$this, 'load_custom_person_template'], 99);
        add_action('template_redirect', [$this, 'custom_cpt_404_message']);
    }

    public function on_plugin_activation(): void {
        flush_rewrite_rules();
    }

    public function rrze_faudir_flush_rewrite_on_slug_change($old_value, $value, $option): void {
        if (
            $option === 'rrze_faudir_options'
            && isset($old_value['person_slug'], $value['person_slug'])
            && $old_value['person_slug'] !== $value['person_slug']
        ) {
            flush_rewrite_rules();
        }
    }

    public function load_custom_person_template($template) {
        $post_type = $this->config->get('person_post_type');

        if (get_query_var($post_type) || is_singular($post_type)) {
            $plugin_template = plugin_dir_path(__DIR__) . '/templates/single-custom_person.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    public function custom_cpt_404_message(): void {
        global $wp_query;
        $post_type = $this->config->get('person_post_type');

        if (isset($wp_query->query_vars['post_type'])
            && $wp_query->query_vars['post_type'] === $post_type
            && empty($wp_query->post)) {
            $this->render_custom_404();
            return;
        }

        $slug = $this->config->get('person_slug');  // !empty($options['person_slug']) ? sanitize_title($options['person_slug']) : 'faudir';
        if ($this->is_slug_request($slug)) {
            $redirect = trim($this->config->get('redirect_archivpage_uri'));
            if (!empty($redirect)) {
                if (str_starts_with($redirect, '/')) {
                    $redirect = home_url($redirect);
                }
                if (filter_var($redirect, FILTER_VALIDATE_URL)) {
                    wp_redirect(esc_url_raw($redirect), 301);
                    exit;
                }
            }
            $this->render_custom_404();
        }
    }

    public function maybe_disable_canonical_redirect(): void {
        $redirect = trim($this->config->get('redirect_archivpage_uri'));
        if (empty($redirect)) {
            return;
        }
        $slug = $this->config->get('person_slug');

        if ($this->is_slug_request($slug)) {
            remove_filter('template_redirect', 'redirect_canonical');
        }
    }

    private function render_custom_404(): void {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);

        ob_start();
        add_action('shutdown', [$this, 'shutdown_render_404'], 0);

        include get_404_template();
        exit;
    }

    public function shutdown_render_404(): void {
        $content = ob_get_clean();
        $new_hero_content = '<div class="hero-container hero-content">'
            . '<p class="presentationtitle">' . __('No contact entry could be found.', 'rrze-faudir') . '</p>'
            . '</div>';

        $updated_content_escaped = preg_replace(
            '/<p class="presentationtitle">.*?<\/p>/s',
            $new_hero_content,
            (string) $content
        );

        echo $updated_content_escaped;
    }

    private function is_slug_request(string $slug): bool {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $request_uri = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $home_path = trim((string) (parse_url(home_url(), PHP_URL_PATH) ?? ''), '/');

        if (!empty($home_path) && stripos($request_uri, $home_path) === 0) {
            $request_uri = trim(substr($request_uri, strlen($home_path)), '/');
        }

        $normalized_uri = strtolower((string) preg_replace('#/index\.php$#', '', $request_uri));
        $normalized_slug = strtolower(trim($slug, '/'));

        return $normalized_uri === $normalized_slug;
    }

    
    public function maybe_show_activation_notice(): void {
        if (!is_admin()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) {
            return;
        }

        if ($screen->base !== 'plugins') {
            return;
        }

        $isActivation = (!empty($_GET['activate']) && $_GET['activate'] === 'true')
            || (!empty($_GET['activate-multi']) && $_GET['activate-multi'] === 'true');

        if (!$isActivation) {
            return;
        }

  //      $plugin = isset($_GET['plugin']) ? (string) $_GET['plugin'] : '';
   //     if ($plugin !== plugin_basename(RRZE_PLUGIN_FILE)) {
   //         return;
   //     }
        // Funktioniert nicht bei unserem Setup - die Pluginliste leitet wieder bei Aktivierung um, so dass der GET-Parameter plugin nicht existiert,

        $settingsUrl = add_query_arg(
            [
                'page' => 'rrze-faudir',
                'tab'  => 'advanced',
            ],
            admin_url('options-general.php')
        );

                
        $dokuUrl = Constants::FAUDIR_DOKU_URL; 
        
        $msg = '';
        $msg .= '<p>';
        $msg .= esc_html__('RRZE FAUdir was activated.', 'rrze-faudir');
        $msg .= '<br>';
        $msg .= esc_html__('Please refer to the', 'rrze-faudir'); 
        $msg .= ' <a href="' . esc_url($dokuUrl) . '">' . esc_html__('documentation', 'rrze-faudir') . '</a> ';
        $msg .= esc_html__('for information, instructions and frequently asked questions and answers regarding usage.', 'rrze-faudir');
        $msg .= '</p>';
        if (FaudirUtils::isFauPersonActive()) {
            $msg .= '<p>';
            $msg .= esc_html__('To import old person entries from FAU Person, access the', 'rrze-faudir');
            $msg .= ' <a href="' . esc_url($settingsUrl) . '">' . esc_html__('advanced settings', 'rrze-faudir') . '</a>.</p>';
        }

        echo '<div class="notice notice-info is-dismissible">';
        echo $msg;
        echo '</div>';
    }
    
    
    /*
     * Settingslinks in der PLuginliste
     */
    public function add_plugin_action_links(array $links): array {
        if (!current_user_can('manage_options')) {
            return $links;
        }

        $settingsUrl = add_query_arg(
            [
                'page' => 'rrze-faudir'
            ],
            admin_url('options-general.php')
        );

        $new = [
            '<a href="' . esc_url($settingsUrl) . '">' . esc_html__('Settings', 'rrze-faudir') . '</a>',
        ];

        return array_merge($new, $links);
    }
    
    /*
     * Dokulink in der Pluginübersicht ergänzen
     */  
    public function add_plugin_row_meta_links(array $links, string $file): array {
        if ($file !== plugin_basename(RRZE_PLUGIN_FILE)) {
            return $links;
        }

        $links[] = '<a href="' . esc_url(Constants::FAUDIR_DOKU_URL) . '">'
            . esc_html__('Documentation', 'rrze-faudir')
            . '</a>';

        return $links;
    }
}