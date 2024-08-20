<?php
// Block handler for RRZE FAUDIR

class FaudirBlock {
    public static function register() {
        register_block_type('rrze/faudir-block', [
            'render_callback' => [self::class, 'render'],
        ]);
    }

    public static function render($attributes) {
        return '<div class="faudir-block">' . esc_html($attributes['content']) . '</div>';
    }
}
?>
