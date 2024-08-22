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
        wp_enqueue_style('rrze-faudir', plugin_dir_url(__FILE__) . '../../assets/css/rrze-faudir.css');
        wp_enqueue_script('rrze-faudir', plugin_dir_url(__FILE__) . '../../assets/js/rrze-faudir.js', ['jquery'], false, true);

        // Retrieve API key from settings
        $api_key = get_option('rrze_faudir_api_key', '');

        // Localize script with API key
        wp_localize_script('rrze-faudir', 'rrze_faudir_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce'),
            'api_key' => $api_key // Pass API key to JavaScript
        ));
    }
    public static function enqueue_admin($hook)
    {
        if ($hook !== 'toplevel_page_rrze-faudir') {
            return;
        }
    
        // Enqueue CSS for the admin page
        wp_enqueue_style('rrze-faudir', plugin_dir_url(__FILE__) . '../../assets/css/rrze-faudir.css');
    
        // Enqueue the admin.js script
        wp_enqueue_script('rrze-faudir-admin-js', plugin_dir_url(__FILE__) . '../../assets/js/admin.js', ['jquery'], false, true);
    
        // Get the API key from the options table
        $api_key = get_option('rrze_faudir_api_key', '');
    
        // Localize the script with relevant data
        wp_localize_script('rrze-faudir-admin-js', 'rrze_faudir_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce'),
            'api_key' => $api_key // Pass API key to JavaScript
        ));
    }
    
}