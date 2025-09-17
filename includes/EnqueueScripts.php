<?php
// Utility class to enqueue scripts and styles for RRZE FAUDIR
namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Config;

class EnqueueScripts {

    public function __construct() {}

    public function register(): void {
        // Assets global REGISTRIEREN (Frontend + Backend verfügbar, aber noch NICHT geladen)
        add_action('init', [self::class, 'register_shared_assets']);

        // Nur im Backend laden (CPT-Editor & Settings)
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);

        // Nur im Block-Editor laden, und nur für unseren CPT
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_block_editor']);

        // Optionales Opt-in für Frontend (standardmäßig AUS)
        if (apply_filters('rrze_faudir/enqueue_frontend_globally', false)) {
            add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend']);
        }
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
            'ajax_url'            => admin_url('admin-ajax.php'),
            'api_nonce'           => wp_create_nonce('rrze_faudir_api_nonce'),
            'api_key'             => get_option('rrze_faudir_api_key', ''),
            'confirm_clear_cache' => __('Are you sure you want to clear the cache?', 'rrze-faudir'),
            'confirm_import'      => __('Are you sure you want to import contacts from FAU person?', 'rrze-faudir'),
            'edit_text'           => __('Edit', 'rrze-faudir'),
            'add_text'            => __('Adding...', 'rrze-faudir'),
            'saving_text'         => __('Saving...', 'rrze-faudir'),
            'saved_text'          => __('Saved', 'rrze-faudir'),
            'save_text'           => __('Save as Default Organization', 'rrze-faudir'),
            'org_saved_text'      => __('Organization has been saved as default.', 'rrze-faudir'),
            'error_saving_text'   => __('Error saving organization.', 'rrze-faudir'),
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
        wp_enqueue_style('rrze-faudir');
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
