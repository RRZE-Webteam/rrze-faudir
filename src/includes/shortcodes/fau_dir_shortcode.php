<?php
// Shortcode handler for RRZE FAUDIR

class FaudirShortcode {
    public static function register() {
        add_shortcode('faudir_shortcode', [self::class, 'render']);
    }

    public static function render($atts, $content = null) {
        return '<div class="faudir-shortcode">' . do_shortcode($content) . '</div>';
    }
}
?>
