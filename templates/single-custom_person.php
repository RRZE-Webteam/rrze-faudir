<?php
get_header();
?>

<main id="main" class="site-main">

<?php
    while (have_posts()) :
        the_post();
        ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div id="content">
            <div class="content-container">
            <div class="contact-page">
                <div class="contact-page-img-container">
                    <div style="flex-grow: 1; max-width:70%">
                        <!-- Full name with title -->
                        <?php

                            $fields = [
                                'person_id' => __('Person ID', 'rrze-faudir'),
                                'person_name' => __('Name', 'rrze-faudir'),
                                'person_email' => __('Email', 'rrze-faudir'),
                                'person_telephone' => __('Telephone', 'rrze-faudir'),
                                'person_given_name' => __('Given Name', 'rrze-faudir'),
                                'person_family_name' => __('Family Name', 'rrze-faudir'),
                                'person_title' => __('Title', 'rrze-faudir'),
                                'person_suffix' => __('Suffix', 'rrze-faudir'),
                                'person_nobility_name' => __('Nobility Name', 'rrze-faudir'),
                                'person_organization' => __('Organization', 'rrze-faudir'),
                                'person_function' => __('Function', 'rrze-faudir'),
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
                        
                            if (!empty($email_output)) {
                                echo '<p><strong>' . esc_html__('Email:', 'rrze-faudir') . '</strong> ' . esc_html($email_output) . '</p>';
                            }
                            
                            if (!empty($phone_output)) {
                                echo '<p><strong>' . esc_html__('Phone:', 'rrze-faudir') . '</strong> ' . esc_html($phone_output) . '</p>';
                            }
                            ?>
                            <?php 
                            // Get organizations meta
                            $contacts = get_post_meta(get_the_ID(), 'person_contacts', true) ?: array();

                            if (empty($contacts)) {
                                $contacts = array(array(
                                    'organization' => '',
                                    'workplace' => '',
                                    'address' => '',
                                    'function_en' => '',
                                    'function_de' => '',
                                    'socials' => array('')
                                ));
                            }
                            $locale = get_locale();
                            $show_german = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_SIE' ) !== false;

                            // Iterate through organizations and display the data
                            foreach ($contacts as $index => $org) : ?>
                                <div class="organization-block">
                                    <h4><?php echo esc_html__('Organization: ', 'rrze-faudir') . ' ' . esc_html($org['organization']); ?></h4>
                                    
                                    <div class="social-wrapper">
                                        <h5><?php echo esc_html__('Social Media:', 'rrze-faudir'); ?></h5>
                                        <?php 
                                        // Parse each line to extract platform and URL
                                        if (!empty($org['socials'])) {
                                            // If it's an array, use it directly
                                            if (is_array($org['socials'])) {
                                                $socials_line = $org['socials'];
                                            } else {
                                                // If it's a string, split it into an array
                                                $socials_line = explode("\n", $org['socials']);
                                            }
                                        } else {
                                            $socials_line = [];
                                        }

                                        // Define a map of platform names to their icons (you can customize the paths or use Font Awesome classes)
                                        $social_icons = [
                                            'Github' => '<i class="fab fa-github"></i>',
                                            'Xing' => '<i class="fab fa-xing"></i>',
                                            'Bluesky' => '<i class="fab fa-twitter"></i>', // Adjust icon if needed
                                        ];
                                        $social_icons = [
                                            'Github' => '<i class="fab fa-github"></i>',
                                            'Xing' => '<i class="fab fa-xing"></i>',
                                            'Bluesky' => '<i class="fas fa-cloud"></i>',
                                            'Twitter' => '<i class="fab fa-twitter"></i>',
                                            'Facebook' => '<i class="fab fa-facebook"></i>',
                                            'Linkedin' => '<i class="fab fa-linkedin"></i>',
                                            'Instagram' => '<i class="fab fa-instagram"></i>',
                                            'Youtube' => '<i class="fab fa-youtube"></i>',
                                            'Tiktok' => '<i class="fab fa-tiktok"></i>',
                                            'Whatsapp' => '<i class="fab fa-whatsapp"></i>',
                                            'Snapchat' => '<i class="fab fa-snapchat-ghost"></i>',
                                            'Reddit' => '<i class="fab fa-reddit"></i>',
                                            'Pinterest' => '<i class="fab fa-pinterest"></i>',
                                            'Telegram' => '<i class="fab fa-telegram"></i>',
                                            'Discord' => '<i class="fab fa-discord"></i>',
                                            'Medium' => '<i class="fab fa-medium"></i>',
                                            'Vimeo' => '<i class="fab fa-vimeo"></i>',
                                            'Twitch' => '<i class="fab fa-twitch"></i>',
                                            'Spotify' => '<i class="fab fa-spotify"></i>',
                                            'Slack' => '<i class="fab fa-slack"></i>',
                                            'Dribbble' => '<i class="fab fa-dribbble"></i>',
                                            'Behance' => '<i class="fab fa-behance"></i>',
                                            'Flickr' => '<i class="fab fa-flickr"></i>',
                                            'Mastodon' => '<i class="fab fa-mastodon"></i>',
                                            'Goodreads' => '<i class="fas fa-book"></i>',
                                            'Strava' => '<i class="fab fa-strava"></i>',
                                            'Rss' => '<i class="fas fa-rss"></i>',
                                            'Zoom' => '<i class="fas fa-video"></i>',
                                            'Bsky' => '<i class="fas fa-cloud"></i>', 
                                        ];
                                    
                                        foreach ($socials_line as $line) :
                                            if (trim($line) !== '') :
                                                // Split the line into platform and URL using ':' delimiter
                                                list($platform, $url) = array_map('trim', explode(':', $line, 2));
                                                $icon = $social_icons[$platform] ?? ''; // Get the icon if available
                                                ?>
                                                <p>
                                                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo $icon . ' ' . esc_html($platform); ?>
                                                    </a>
                                                </p>
                                            <?php endif; 
                                        endforeach; ?>
                                    </div>

                                    <div class="address-wrapper">
                                        <h5><?php echo esc_html__('Organization Address:', 'rrze-faudir'); ?></h5>
                                        <?php 
                                        $address_lines = explode("\n", $org['address'] ?? '');
                                        foreach ($address_lines as $line) :
                                            if (trim($line) !== '') : ?>
                                                <p><?php echo esc_html($line); ?></p>
                                            <?php endif; 
                                        endforeach; ?>
                                    </div>
                                    <div class="workplace-wrapper">
                                        <h5><?php echo esc_html__('Workplace:', 'rrze-faudir'); ?></h5>
                                        <?php 
                                        // Define weekday mapping
                                        $weekdayMap = [
                                            1 => 'Monday',
                                            2 => 'Tuesday',
                                            3 => 'Wednesday',
                                            4 => 'Thursday',
                                            5 => 'Friday',
                                            6 => 'Saturday',
                                            7 => 'Sunday',
                                        ];
                                        
                                        // Separate workplace lines and office hours
                                        $workplace_lines = explode("\n", $org['workplace'] ?? '');
                                        $office_hours_line = '';
                                    
                                        foreach ($workplace_lines as $line) :
                                            $trimmedLine = trim($line);
                                        
                                            // Detect the "Office Hours" line and store it for parsing
                                            if (stripos($trimmedLine, 'Office Hours:') !== false) {
                                                $office_hours_line = $trimmedLine;
                                                continue; // Skip displaying this line as a regular workplace line
                                            }
                                        
                                            // Otherwise, treat as a regular workplace line
                                            if ($trimmedLine !== '') {
                                                echo '<p>' . esc_html($trimmedLine) . '</p>';
                                            }
                                        endforeach;
                                    
                                        // Check and display office hours if found
                                        if ($office_hours_line) {
                                            echo '<strong>' . esc_html__('Office Hours:', 'rrze-faudir') . '</strong><ul>';
                                            
                                            // Debug: Show extracted office hours line
                                            echo '<!-- Debug: Office Hours Line Detected: ' . esc_html($office_hours_line) . ' -->';
                                            
                                            // Extract individual office hours segments
                                            preg_match_all('/Weekday (\d+): (\d{2}:\d{2}) - (\d{2}:\d{2})/', $office_hours_line, $matches, PREG_SET_ORDER);
                                        
                                            // Debug: Show if matches are found
                                            if (empty($matches)) {
                                                echo '<!-- Debug: No matches found for office hours pattern -->';
                                            }
                                        
                                            foreach ($matches as $match) {
                                                $weekday = $weekdayMap[$match[1]] ?? 'Unknown';
                                                $from = $match[2];
                                                $to = $match[3];
                                                echo '<li><strong>' . esc_html($weekday) . ':</strong> ' . esc_html($from . ' - ' . $to) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<!-- Debug: No office hours line found -->';
                                        }
                                        ?>
                                    </div>


                                    <?php if (!$show_german) : ?>
                                    <div class="functions-wrapper">
                                        <h5><?php echo esc_html__('Function', 'rrze-faudir'); ?></h5>
                                            <p><?php echo esc_html($org['function_en']); ?></p>
                                    </div>
                                    <?php elseif ($show_german) : ?>
                                    <div class="functions-wrapper">
                                        <h5><?php echo esc_html__('Function', 'rrze-faudir'); ?></h5>
                                            <p><?php echo esc_html($org['function_de']); ?></p>
                                    </div>
                                    <?php endif ;?>
                                </div>
                                <hr>
                            <?php endforeach; ?>

                            <?php
                            $locale = get_locale();
                            $content_en = get_post_meta(get_the_ID(), '_content_en', true);

                            $content_de = get_the_content();
                            $content_en = isset($content_en) ? $content_en : ''; // Ensure $content_en is set
                                                        
                            $teaser_text_key = ($locale === 'de_DE' || $locale === 'de_SIE' ) ? '_teasertext_de' : '_teasertext_en';
                            $teaser_lang = get_post_meta(get_the_ID(), $teaser_text_key, true);
                            if (!empty($teaser_lang)) :
                            ?>
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
                    <?php
                    $allowed_tags = array_merge(
                        wp_kses_allowed_html('post'),
                        [
                            'img' => [
                                'src'    => true,
                                'alt'    => true,
                                'class'  => true,
                                'width'  => true,
                                'height' => true,
                                'loading' => true,
                            ],
                        ]
                    );
                     if ($locale === 'de_DE' || $locale === 'de_SIE' && !empty($content_de)): ?>
                        <section class="card-section-title"><?php esc_html__('Content', 'rrze-faudir'); ?></section>
                        <div class="content-second-language">
                            <?php echo wp_kses(do_shortcode($content_de), $allowed_tags); ?>
                        </div>
                    <?php elseif ($locale === 'en_US' || $locale === 'en_GB' && !empty($content_en)): ?>
                        <section class="card-section-title"><?php esc_html__('Content', 'rrze-faudir'); ?></section>
                        <div class="content-second-language">
                    
                            <?php echo wp_kses(do_shortcode($content_en), $allowed_tags); ?>
                        </div>
                    <?php endif; ?>

                    </div>
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
