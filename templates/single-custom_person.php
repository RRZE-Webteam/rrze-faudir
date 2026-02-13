<?php
// Template file for RRZE FAUDIR
use RRZE\FAUdir\Config;
use RRZE\FAUdir\Shortcode;
use RRZE\FAUdir\EnqueueScripts;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

EnqueueScripts::enqueue_frontend_on_demand();

get_header();

?>

<main id="main" class="site-main faudir-custom-post">
    <?php
    if (have_posts()) {
        the_post();
        
        // Get the person ID from post meta
        $person_id = get_post_meta(get_the_ID(), 'person_id', true);
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(['post']); ?>>
            <div id="content">
                <div class="content-container">
                    <?php 
                    // Execute the shortcode with the person ID and page format
                    
                    $config = new Config();
                    $opt = $config->getOptions();

                    $showfields = [];
                    if (!empty($opt['output_fields_endpoint'])) {
                        $showfields = (array) $opt['output_fields_endpoint'];
                    } elseif (!empty($opt['default_output_fields_endpoint'])) {
                        $showfields = (array) $opt['default_output_fields_endpoint'];
                    }

                    $shortcode = new Shortcode();

                    echo $shortcode->renderPersonPage((string) $person_id, $showfields);

                        // $output_escaped = Shortcode::render($atts);
                        // echo  apply_filters('the_content', $output_escaped);
                        
                     
                    ?>
                </div>
            </div>
        </article>

    <?php
    }
    ?>
</main>

<?php
get_footer();
