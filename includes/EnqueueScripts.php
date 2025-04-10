<?php
// Utility class to enqueue scripts and styles for RRZE FAUDIR

namespace RRZE\FAUdir;


class EnqueueScripts {
    protected static $pluginFile;

    public function __construct($pluginFile)  {
        self::$pluginFile = $pluginFile;
    }
    
    public function register()  {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_block_editor']);   
    }

    public static function enqueue_frontend()   {               
        wp_enqueue_style('rrze-faudir', RRZE_PLUGIN_URL . 'assets/css/rrze-faudir.css');

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


    // Enqueue admin scripts and styles for specific admin pages
    public static function enqueue_admin($hook)  {
        // Check for both settings page and post edit screen
        global $post;
        if ($hook !== 'settings_page_rrze-faudir'
            && ($hook !== 'post.php' && $hook !== 'post-new.php'
                || !isset($post)
                || $post->post_type !== 'custom_person')) {
            return;
        }
        // Enqueue CSS for the admin page
        wp_enqueue_style('rrze-faudir', RRZE_PLUGIN_URL . 'assets/css/rrze-faudir.css');
        // Enqueue the admin.js script
        wp_enqueue_script('rrze-faudir-admin-js', RRZE_PLUGIN_URL . 'assets/js/rrze-faudir-admin.js', ['jquery'], null, true);
        // Localize the script with relevant data
        wp_localize_script('rrze-faudir-admin-js', 'rrzeFaudirAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_nonce' => wp_create_nonce('rrze_faudir_api_nonce'),
            'api_key' => get_option('rrze_faudir_api_key', ''),
            'confirm_clear_cache' => __('Are you sure you want to clear the cache?', 'rrze-faudir'),
            'edit_text' => __('Edit', 'rrze-faudir'),
            'add_text' => __('Adding...', 'rrze-faudir'),
            'saving_text' => __('Saving...', 'rrze-faudir'),
            'saved_text' => __('Saved', 'rrze-faudir'),
            'save_text' => __('Save as Default Organization', 'rrze-faudir'),
            'org_saved_text' => __('Organization has been saved as default.', 'rrze-faudir'),
            'error_saving_text' => __('Error saving organization.', 'rrze-faudir')
        ));
    }

    // Enqueue block editor specific scripts and styles for Gutenberg
    public static function enqueue_block_editor()    {
        // Enqueue block editor specific JavaScript for Gutenberg
        wp_enqueue_script(
            'rrze-faudir-block-js', // Handle for the block JS
            RRZE_PLUGIN_URL . 'assets/js/rrze-faudir.js', // Path to the compiled block JS
            [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-block-editor',
                'wp-i18n',
                'wp-data'
            ], // Dependencies for block development
            filemtime(plugin_dir_path(self::$pluginFile) . 'assets/js/rrze-faudir.js'), // Versioning
            true // Enqueue in the footer
        );

        // Enqueue block editor specific styles
        wp_enqueue_style(
            'rrze-faudir-block-editor-css', // Handle for block CSS
            RRZE_PLUGIN_URL. 'assets/css/rrze-faudir.css', // Path to the compiled block CSS
            array(), // No dependencies
            filemtime(plugin_dir_path(self::$pluginFile) . 'assets/css/rrze-faudir.css') // Versioning
        );
    }
}
