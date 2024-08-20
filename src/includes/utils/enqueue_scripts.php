<?php
// Utility class to enqueue scripts and styles for RRZE FAUDIR

class EnqueueScripts {
    public static function register() {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue() {
        wp_enqueue_style('rrze-faudir', plugin_dir_url(__FILE__) . '../../assets/css/rrze-faudir.css');
        wp_enqueue_script('rrze-faudir', plugin_dir_url(__FILE__) . '../../assets/js/rrze-faudir.js', [], false, true);
    }
}
?>
