<?php
// Template file for RRZE FAUDIR

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();
?>

<main id="main" class="site-main faudir">
    <?php
    while (have_posts()) :
        the_post();
        
        // Get the person ID from post meta
        $person_id = get_post_meta(get_the_ID(), 'person_id', true);
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div id="content">
                <div class="content-container">
                    <?php 
                    // Execute the shortcode with the person ID and page format
                    echo do_shortcode('[faudir identifier="' . esc_attr($person_id) . '" format="page"]');
                    ?>
                </div>
            </div>
        </article>

    <?php
    endwhile;
    ?>
</main>

<?php
get_footer();
