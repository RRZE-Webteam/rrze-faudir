<?php
// Template file for RRZE FAUDIR
use RRZE\FAUdir\Config;
use RRZE\FAUdir\Shortcode;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

?>

<main id="main" class="site-main faudir-custom-post">
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
                    
                        $config = new Config;
                        $opt = $config->getOptions(); 
                        
                        $showfields = [];
                        
                        if (isset($opt['output_fields_endpoint']) && (!empty($opt['output_fields_endpoint']))) {
                            $showfields = $opt['output_fields_endpoint'];
                        } elseif (isset($opt['default_output_fields_endpoint']) && (!empty($opt['default_output_fields_endpoint']))) {
                            $showfields = $opt['default_output_fields_endpoint'];
                        }
                       
                        
                        $atts['display'] = 'person';
                        $atts['format'] = 'page';
                        $atts['identifier'] = $person_id;
                        $atts['show'] = implode(', ', $showfields);
                        $output = Shortcode::fetch_fau_data($atts);
                        $content = apply_filters('the_content', $output);
                        echo $content; 
                        
                     
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
