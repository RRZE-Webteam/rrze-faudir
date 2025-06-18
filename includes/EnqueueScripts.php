<?php
// Utility class to enqueue scripts and styles for RRZE FAUDIR

namespace RRZE\FAUdir;

use RRZE\FAUdir\Config;

class EnqueueScripts {

    public function __construct()  {
        // Nothing todo (yet)
    }
    
    public function register():void  {       
        add_action('wp_enqueue_scripts', [self::class, 'register_frontend_styles']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);
    }
    
    public static function register_frontend_styles(): void {
        wp_register_style('rrze-faudir', RRZE_PLUGIN_URL . 'assets/css/rrze-faudir.css');
    }

/*
 * TODO: Prüfen ob man das Frontend.JS irgendwo braucht. Ich sehe keinen Grund.
 * Erstmal daher auskommentiert.
 * Issue #278 

    public static function enqueue_frontend():void {
        wp_register_style('rrze-faudir', RRZE_PLUGIN_URL . 'assets/css/rrze-faudir.css');
        wp_enqueue_style('rrze-faudir');

        wp_enqueue_script(
            'rrze-faudir',
            RRZE_PLUGIN_URL . 'assets/js/rrze-faudir.js',
            ['jquery', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components'], // Add 'wp-element' or other necessary dependencies
            false,
            true
        );

        // Retrieve API key from settings
        $api_key = get_option('rrze_faudir_api_key', '');

        // Localize script with API key
        wp_localize_script('rrze-faudir', 'rrze_faudir_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce'),
            'api_key' => $api_key // Pass API key to JavaScript
        ));
    }
*/

    // Enqueue admin scripts and styles for specific admin pages
    public static function enqueue_admin($hook)  {
        // Check for both settings page and post edit screen
        global $post;
        $config = new Config();
        $post_type = $config->get('person_post_type');
        if ($hook !== 'settings_page_rrze-faudir'
            && ($hook !== 'post.php' && $hook !== 'post-new.php'
                || !isset($post)
                || $post->post_type !== $post_type)) {
            return;
        }
        
        
        // Enqueue Frontend CSS for the admin page
        wp_enqueue_style('rrze-faudir');
       
        // Enqueue the admin.js script
        wp_enqueue_script('rrze-faudir-admin-js', RRZE_PLUGIN_URL . 'assets/js/rrze-faudir-admin.js', ['jquery'], null, true);
        // Localize the script with relevant data
        wp_localize_script('rrze-faudir-admin-js', 'rrzeFaudirAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce'),
            'api_key' => get_option('rrze_faudir_api_key', ''),
            'confirm_clear_cache' => __('Are you sure you want to clear the cache?', 'rrze-faudir'),
            'confirm_import' => __('Are you sure you want to import contacts from FAU person?', 'rrze-faudir'),
            'edit_text' => __('Edit', 'rrze-faudir'),
            'add_text' => __('Adding...', 'rrze-faudir'),
            'saving_text' => __('Saving...', 'rrze-faudir'),
            'saved_text' => __('Saved', 'rrze-faudir'),
            'save_text' => __('Save as Default Organization', 'rrze-faudir'),
            'org_saved_text' => __('Organization has been saved as default.', 'rrze-faudir'),
            'error_saving_text' => __('Error saving organization.', 'rrze-faudir')
        ));
    }
}
