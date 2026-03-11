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
        $current_id = (int) get_the_ID();
        $parent_id  = wp_is_post_revision($current_id) ? (int) wp_get_post_parent_id($current_id) : $current_id;
        $person_id = (string) get_post_meta($parent_id, 'person_id', true);
                
        ?>

        <article id="post-<?php echo $parent_id; ?>" <?php post_class(['post']); ?>>
            <div id="content">
                <div class="content-container">
                    <?php 
                    // Execute the shortcode with the person ID and page format
                    
                    $config = new Config();
                    $opt = $config->getOptions();

                    $showfields = $config->getDefaultFieldlistByFormat('page', 'person');
                    

                    if (!empty($opt['output_fields_endpoint'])) {
                        $showfields = (array) $opt['output_fields_endpoint'];
                    }

                    $shortcode = new Shortcode($config);
                    echo $shortcode->renderPersonPage((string) $person_id, $showfields);         
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
