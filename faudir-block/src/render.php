<?php
function rrze_faudir_render_block($attributes)  {
    $shortcode_output = do_shortcode('[faudir identifier="37b507c088" format="page"]');
    return '<div class="my-block-preview">' . $shortcode_output . '</div>';
}
