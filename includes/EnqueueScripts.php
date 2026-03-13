<?php
// Utility class to enqueue scripts and styles for RRZE FAUDIR
namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Config;

class EnqueueScripts {


    public function register(): void {
        // Assets global REGISTRIEREN (Frontend + Backend verfügbar, aber noch NICHT geladen)
        add_action('init', [self::class, 'register_shared_assets']);

        // Nur im Backend laden (CPT-Editor & Settings)
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);

        // Nur im Block-Editor laden, und nur für unseren CPT
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_block_editor']);

        // Enqueue
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_conditionally'], 5);
        

    }

    /** Assets global registrieren – Version = Plugin-Version */
    public static function register_shared_assets(): void {
        $version = self::get_plugin_version();

        wp_register_style(
            'rrze-faudir',
            RRZE_PLUGIN_URL . 'assets/css/rrze-faudir.css',
            [],
            $version
        );

        wp_register_script(
            'rrze-faudir-admin-js',
            RRZE_PLUGIN_URL . 'assets/js/rrze-faudir-admin.js',
            ['jquery'],
            $version,
            true
        );
    }
    
    
    /*
     * Enqueue conditional durchführen
     */

    public static function enqueue_frontend_conditionally(): void {
        if (apply_filters('rrze_faudir/enqueue_frontend_globally', false)) {
            self::enqueue_frontend();
            return;
        }

        if (is_admin()) {
            return;
        }

        // 1) CPT-Single immer als "needs"
        $config = new \RRZE\FAUdir\Config();
        $postType = (string) $config->get('person_post_type');

        if ($postType !== '' && is_singular($postType)) {
            self::enqueue_frontend();
            return;
        }

        // 2) Content-basiert (Shortcode/Block/oEmbed-URL)
        $postId = (int) get_queried_object_id();
        if ($postId <= 0) {
            return;
        }

        $post = get_post($postId);
        if (!$post || empty($post->post_content)) {
            return;
        }

        $needs = false;
        $content = (string) $post->post_content;

        if (function_exists('has_shortcode') && has_shortcode($content, 'faudir')) {
            $needs = true;
        }

        if (!$needs && function_exists('has_block')) {
            if (has_block('rrze-faudir/block', $post) || has_block('rrze-faudir/service', $post)) {
                $needs = true;
            }
        }

        if (!$needs) {
            $personPrefix = (string) \RRZE\FAUdir\Constants::FAUDIR_PUBLIC_PERSON_PREFIX;
            $orgPrefix = (string) \RRZE\FAUdir\Constants::FAUDIR_PUBLIC_ORG_PREFIX;

            $patterns = [];

            if ($personPrefix !== '') {
                $patterns[] = preg_quote($personPrefix, '~') . '[A-Za-z0-9]+(?:[/?#][^\s<"]*)?';
            }

            if ($orgPrefix !== '') {
                $patterns[] = preg_quote($orgPrefix, '~') . '[A-Za-z0-9]+(?:[/?#][^\s<"]*)?';
            }

            if (!empty($patterns)) {
                $pattern = '~(?:' . implode('|', $patterns) . ')~i';

                if (preg_match($pattern, $content)) {
                    $needs = true;
                }
            }
        }

        $needs = (bool) apply_filters('rrze_faudir/enqueue_frontend_on_demand', $needs, $post);

        if ($needs) {
            self::enqueue_frontend();
        }
    }
    
    
    /** FRONTEND: nur per Opt-in-Filter oder explizitem Aufruf laden */
    public static function enqueue_frontend(): void {
        wp_enqueue_style('rrze-faudir');
    }

    /** ADMIN: nur auf CPT-Editor & Settings-Seite laden */
    public static function enqueue_admin(string $hook): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        $config    = new Config();
        $post_type = $config->get('person_post_type');

        // Settings-Seite erkennen (IDs ggf. anpassen)
        $is_settings =
            ($hook === 'settings_page_rrze-faudir') ||
            ($screen && in_array($screen->id, ['settings_page_rrze-faudir', 'toplevel_page_rrze-faudir'], true));

        // CPT-Editor erkennen (post.php / post-new.php für unseren CPT)
        $is_cpt_editor =
            $screen &&
            in_array($screen->base, ['post', 'post-new'], true) &&
            $screen->post_type === $post_type;

        if (!$is_settings && !$is_cpt_editor) {
            return;
        }

        // Frontend-CSS im Backend verwenden
        wp_enqueue_style('rrze-faudir');

        // Admin-Script + Daten (falls benötigt)
        wp_enqueue_script('rrze-faudir-admin-js');
        wp_localize_script('rrze-faudir-admin-js', 'rrzeFaudirAjax', [
            'ajax_url'              => admin_url('admin-ajax.php'),
            'api_nonce'             => wp_create_nonce('rrze_faudir_api_nonce'),
            'confirm_clear_cache'   => __('Are you sure you want to clear the cache?', 'rrze-faudir'),
            'confirm_import'        => __('Are you sure you want to import contacts from FAU person?', 'rrze-faudir'),
            'edit_text'             => __('Edit', 'rrze-faudir'),
            'add_text'              => __('Add', 'rrze-faudir'),
            'saving_text'           => __('Saving...', 'rrze-faudir'),
            'saved_text'            => __('Saved', 'rrze-faudir'),
            'save_text'             => __('Save as Default Organization', 'rrze-faudir'),
            'org_saved_text'        => __('Organization has been saved as default.', 'rrze-faudir'),
            'error_saving_text'     => __('Error saving organization.', 'rrze-faudir'),
            'refresh_action'        => 'rrze_faudir_refresh_person_data',
            'refresh_nonce'         => wp_create_nonce('rrze_faudir_refresh_person_data'),
            'refresh_success_text'      => __('Data successfully loaded from FAUdir.', 'rrze-faudir'),
            'refresh_reload_confirm'    => __('We need to reload this page. Please confirm.', 'rrze-faudir'),
            'refresh_reload_ok'         => __('OK', 'rrze-faudir'),
            'refresh_reload_cancel'     => __('Cancel', 'rrze-faudir'),            
            'refresh_unknown_text'  => __('Unknown error while refreshing person data.', 'rrze-faudir'),
            'refresh_failed_text'   => __('Request failed while refreshing person data.', 'rrze-faudir'),
        ]);
    }

    /** Block-Editor: nur für unseren CPT laden */
    public static function enqueue_block_editor(): void {
        if (!function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $config    = new Config();
        $post_type = $config->get('person_post_type');

        // Nur im Editor und nur bei unserem CPT
        if (in_array($screen->base, ['post', 'post-new'], true) && $screen->post_type === $post_type) {
            wp_enqueue_style('rrze-faudir');
        }
    }

    /**
     * Explizites "On-Demand"-Laden fürs Frontend (z. B. in Shortcode/Block-Render)
     * Aufruf: \RRZE\FAUdir\EnqueueScripts::enqueue_frontend_on_demand();
     */
    public static function enqueue_frontend_on_demand(): void {
        self::enqueue_frontend();
    }

    /** Plugin-Version aus dem Header der Hauptdatei (Fallback: RRZE_PLUGIN_VERSION) */
    private static function get_plugin_version(): ?string {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        if (defined('RRZE_PLUGIN_VERSION') && RRZE_PLUGIN_VERSION) {
            return $cached = (string) RRZE_PLUGIN_VERSION;
        }
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data = get_plugin_data(RRZE_PLUGIN_FILE, false, false);
        $ver  = isset($data['Version']) ? trim((string) $data['Version']) : '';
        return $cached = ($ver !== '' ? $ver : null);
    }
}
