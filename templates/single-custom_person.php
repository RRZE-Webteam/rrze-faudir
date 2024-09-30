<?php
get_header();
?>

<main id="main" class="site-main">

<?php
    while (have_posts()) :
        the_post();
        ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div>
            <div class="contact-page">
                <div class="contact-page-img-container">
                    <div style="flex-grow: 1; max-width:70%">
                        <!-- Full name with title -->
                        <?php

                            $fields = [
                                'person_id' => __('Person ID', 'text-domain'),
                                'person_name' => __('Name', 'text-domain'),
                                'person_email' => __('Email', 'text-domain'),
                                'person_telephone' => __('Telephone', 'text-domain'),
                                'person_given_name' => __('Given Name', 'text-domain'),
                                'person_family_name' => __('Family Name', 'text-domain'),
                                'person_title' => __('Title', 'text-domain'),
                                'person_suffix' => __('Suffix', 'text-domain'),
                                'person_nobility_name' => __('Nobility Name', 'text-domain'),
                                'person_organization' => __('Organization', 'text-domain'),
                                'person_function' => __('Function', 'text-domain'),
                            ];
                            $personal_title = get_post_meta(get_the_ID(), 'person_title', true);
                            $first_name = get_post_meta(get_the_ID(), 'person_given_name', true);
                            $nobility_title = get_post_meta(get_the_ID(), 'person_nobility_name', true);
                            $last_name = get_post_meta(get_the_ID(), 'person_family_name', true);
                            $title_suffix = get_post_meta(get_the_ID(), 'person_suffix', true);

                            $fullName = trim( $personal_title . ' ' . $first_name. ' '. $nobility_title . ' ' . $last_name . ' ' . $title_suffix);
                            ?>
                            <!-- We need to add condition for url when we add CPT -->
                            <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>"><?php echo esc_html($fullName); ?></a></section>
                        
                            <?php
                            // Initialize output strings for email and phone
                            $email_output = get_post_meta(get_the_ID(), 'person_email', true);
                            $phone_output = get_post_meta(get_the_ID(), 'person_telephone', true);
                            $function_label = get_post_meta(get_the_ID(), 'person_function', true);
                            $organization_name = get_post_meta(get_the_ID(), 'person_telephone', true);
                        
                            echo $email_output = '<strong><p>' . __('Email:', 'rrze-faudir') .'</strong>'. esc_html($email_output) .'</p>';
                            echo $phone_output = '<strong><p>' . __('Phone:', 'rrze-faudir') . '</strong>'. esc_html($phone_output) .'</p>';                    

                            ?>

                            <strong><p><?php echo __('Organization:', 'rrze-faudir');?></strong> <?php echo esc_html($organization_name); ?><p>
                            <strong><p><?php echo __('Functions:', 'rrze-faudir');?></strong> <?php echo esc_html($function_label); ?><p>

                            <?php
                            $locale = get_locale();
                            $content_en = get_post_meta(get_the_ID(), '_content_en', true);

                            ?>
                                <h2><?php _e('Content (Second Language)', 'text-domain'); ?></h2>
                                <div class="content-second-language">
                                    <?php echo wp_kses_post((($locale === 'de_DE') ? get_the_content() : $content_en)); ?>
                                </div>
                            <?php
                            $teaser_text_key = ($locale === 'de_DE') ? '_teasertext_de' : '_teasertext_en';
                            $teaser_lang = get_post_meta(get_the_ID(), $teaser_text_key, true);
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
                    <?php $image_url = get_the_post_thumbnail_url($post->ID, 'full'); // You can specify the size ('full', 'medium', 'thumbnail', etc.)

                         ?>
                    <?php if (!empty($image_url)) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" />
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </article>
    <?php
    endwhile;
    ?>
</main>

<?php
get_footer();