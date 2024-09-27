<?php
get_header();
?>

<main id="main" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <div class="entry-content">
                <?php the_content(); ?>

                <h2><?php _e('Additional Information', 'text-domain'); ?></h2>
                <ul>
                    <?php
                    $fields = [
                        'person_id' => __('Person ID', 'text-domain'),
                        'person_name' => __('Name', 'text-domain'),
                        'person_email' => __('Email', 'text-domain'),
                        'person_telephone' => __('Telephone', 'text-domain'),
                        'person_given_name' => __('Given Name', 'text-domain'),
                        'person_family_name' => __('Family Name', 'text-domain'),
                        'person_title' => __('Title', 'text-domain'),
                        'person_pronoun' => __('Pronoun', 'text-domain'),
                        'person_function' => __('Function', 'text-domain'),
                    ];

                    foreach ($fields as $meta_key => $label) :
                        $value = get_post_meta(get_the_ID(), $meta_key, true);
                        if (!empty($value)) :
                    ?>
                        <li><strong><?php echo esc_html($label); ?>:</strong> <?php echo esc_html($value); ?></li>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </ul>

                <?php
                $content_lang = get_post_meta(get_the_ID(), '_content_lang', true);
                if (!empty($content_lang)) :
                ?>
                    <h2><?php _e('Content (Second Language)', 'text-domain'); ?></h2>
                    <div class="content-second-language">
                        <?php echo wp_kses_post($content_lang); ?>
                    </div>
                <?php
                endif;

                $teaser_lang = get_post_meta(get_the_ID(), '_teasertext_lang', true);
                if (!empty($teaser_lang)) :
                ?>
                    <h2><?php _e('Teaser Text (Second Language)', 'text-domain'); ?></h2>
                    <div class="teaser-second-language">
                        <?php echo wp_kses_post($teaser_lang); ?>
                    </div>
                <?php
                endif;
                ?>
            </div>
        </article>
    <?php
    endwhile;
    ?>
</main>

<?php
get_footer();