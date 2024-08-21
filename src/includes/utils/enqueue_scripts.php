<?php
// Utility class to enqueue scripts and styles for RRZE FAUDIR

class EnqueueScripts
{
    public static function register()
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);
    }
    public static function enqueue_frontend()
    {
        wp_enqueue_style('rrze-faudir', plugin_dir_url(__FILE__) . '../../../assets/css/rrze-faudir.css');
        wp_enqueue_script('rrze-faudir', plugin_dir_url(__FILE__) . '../../../assets/js/rrze-faudir.js', ['jquery'], false, true);
        // Localize script for frontend AJAX
        wp_localize_script('rrze-faudir', 'rrze_faudir_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce')
        ));
    }
    public static function enqueue_admin($hook)
    {
        // Ensure the script is only enqueued on the plugin settings page
        if ($hook !== 'toplevel_page_rrze-faudir') {
            return;
        }
        wp_enqueue_style('rrze-faudir', plugin_dir_url(__FILE__) . '../../../assets/css/rrze-faudir.css');
        wp_enqueue_script('rrze-faudir', plugin_dir_url(__FILE__) . '../../../assets/js/rrze-faudir.js', ['jquery'], false, true);
        // Localize script for admin AJAX
        wp_localize_script('rrze-faudir', 'rrze_faudir_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce')
        ));
    }
}
