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

                                $fullName = trim($personal_title . ' ' . $first_name . ' ' . $nobility_title . ' ' . $last_name . ' ' . $title_suffix);
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
                                $show_german = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_DE_formal') !== false;

                                // Iterate through organizations and display the data
                                foreach ($contacts as $index => $org) : ?>
                                    <div class="organization-block">
                                    <span class="screen-reader-text"><?php echo esc_html__('Organization: ', 'rrze-faudir'); ?></span>
                                    <h4><?php echo esc_html($org['organization']); ?></h4>
                                        <div class="social-wrapper">
                                            <span class="screen-reader-text"><?php echo esc_html__('Social Media:', 'rrze-faudir'); ?></span>
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

                                            $iconMap = require plugin_dir_path(RRZE_PLUGIN_FILE) . 'includes/config/icons.php';

                                            foreach ($socials_line as $line) :
                                                if (trim($line) !== '') :
                                                    // Split the line and make sure we have both platform and URL
                                                    $parts = array_map('trim', explode(':', $line, 2));
                                                    if (count($parts) === 2) {
                                                        $icon_data = get_social_icon_data($parts[0]);
                                            ?>
                                                        <a href="<?php echo esc_url($parts[1]); ?>" 
                                                           class="<?php echo esc_attr($icon_data['css_class']); ?>"
                                                           style="background-image: url('<?php echo esc_url($icon_data['icon_url']); ?>'); display: inline-block; padding-left: 20px; background-size: contain; background-repeat: no-repeat; margin-right: 10px;width: 48px;height: 48px;"
                                                           target="_blank" 
                                                           rel="noopener noreferrer">
                                                           <span class="screen-reader-text"> <?php echo esc_html(ucfirst($icon_data['name'])); ?></span>
                                                        </a>
                                        <?php
                                                }
                                                endif;
                                            endforeach; ?>
                                        </div>
                                        <br>
                                        <div class="organization-address">
                                        <span class="screen-reader-text"><?php echo esc_html__('Organization Address:', 'rrze-faudir'); ?></span>
                                        <?php
                                        // Split the address into lines
                                        $address_lines = explode("\n", $org['address'] ?? '');

                                        foreach ($address_lines as $line) :
                                            $trimmedLine = trim($line);
                                            if ($trimmedLine !== '') :
                                                if (strpos($trimmedLine, ':') !== false) {
                                                    [$label, $value] = array_map('trim', explode(':', $trimmedLine, 2));
                                                    // Get icon data for the label
                                                    $icon_data = get_social_icon_data($label); 
                                                    ?>
                                                    <p>
                                                        <span 
                                                           class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                           style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>'); display: inline-block; padding-left: 20px; background-size: contain; background-repeat: no-repeat;" 
                                                           target="_blank" 
                                                           rel="noopener noreferrer">
                                                            <?php echo esc_html($value); ?>
                                                        </span>
                                                    </p>
                                                    <?php
                                                } else {
                                                    // For lines without a colon, render them as plain text
                                                    ?>
                                                    <p><?php echo esc_html($trimmedLine); ?></p>
                                                    <?php
                                                }
                                            endif;
                                        endforeach;
                                        ?>
                                    </div><br>
                                    <?php
                                        /*
                                        <div class="workplace-wrapper">
                                            <span class="screen-reader-text"><?php echo esc_html__('Workplace:', 'rrze-faudir'); ?></span>
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
                                                                            
                                            // Fetch workplace information from the API
                                            $workplace_lines = explode("\n", $org['workplace'] ?? '');
                                            $office_hours_line = '';
                                                                            
                                            // Start rendering workplace information
                                            echo '<div class="workplace-info">';
                                            foreach ($workplace_lines as $line) {
                                                $trimmedLine = trim($line);
                                                                            
                                                if ($trimmedLine !== '') {
                                                    // Detect the "Office Hours" line and store it for parsing
                                                    if (stripos($trimmedLine, 'Office Hours:') !== false) {
                                                        $office_hours_line = $trimmedLine;
                                                        continue; // Skip displaying this line as a regular workplace line
                                                    }
                                                                            
                                                    // Split the line into label and value
                                                    if (strpos($trimmedLine, ':') !== false) {
                                                        [$label, $value] = array_map('trim', explode(':', $trimmedLine, 2));
                                                                            
                                                        // Get icon data for the label
                                                        $icon_data = get_social_icon_data($label);
                                                        ?>
                                                        <p>
                                                            <span 
                                                                class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                style="background-image: url('<?php echo esc_url($icon_data['icon_url']); ?>'); display: inline-block; padding-left: 20px; background-size: contain; background-repeat: no-repeat;" 
                                                                target="_blank" 
                                                                rel="noopener noreferrer">
                                                                <?php echo esc_html($value); ?>
                                                            </span>
                                                        </p>
                                                        <?php
                                                    } else {
                                                        // Render lines without a colon as regular text
                                                        ?>
                                                        <p><?php echo esc_html($trimmedLine); ?></p>
                                                        <?php
                                                    }
                                                }
                                            }
                                            echo '</div>';
                                                                            
                                            // Check and display office hours if found
                                            if ($office_hours_line) {
                                                echo '<div class="office-hours">';
                                                echo '<span class="screen-reader-text">' . esc_html__('Office Hours:', 'rrze-faudir') . '</span><ul>';
                                                                            
                                                // Extract individual office hours segments
                                                preg_match_all('/Weekday (\d+): (\d{2}:\d{2}) - (\d{2}:\d{2})/', $office_hours_line, $matches, PREG_SET_ORDER);
                                                                            
                                                if (empty($matches)) {
                                                    echo '<!-- Debug: No matches found for office hours pattern -->';
                                                }
                                                foreach ($matches as $match) {
                                                    $weekday = $weekdayMap[$match[1]] ?? 'Unknown';
                                                    $from = $match[2];
                                                    $to = $match[3];
                                                    echo '<li><strong>' . esc_html($weekday) . ':</strong> ' . esc_html($from . ' - ' . $to) . '</li>';
                                                }
                                                                            
                                                echo '</ul></div>';
                                            } else {
                                                echo '<!-- Debug: No office hours line found -->';
                                            }                                          
                                            ?>
                                        </div>
                                        */
                                        ?>
                                        


                                        <?php if (!$show_german) : ?>
                                            <div class="functions-wrapper">
                                            <span class="screen-reader-text"><?php echo esc_html__('Function', 'rrze-faudir'); ?></span>
                                                <p><?php echo esc_html($org['function_en']); ?></p>
                                            </div>
                                        <?php elseif ($show_german) : ?>
                                            <div class="functions-wrapper">
                                            <span class="screen-reader-text"><?php echo esc_html__('Function', 'rrze-faudir'); ?></span>
                                                <p><?php echo esc_html($org['function_de']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                <?php endforeach; ?>

                                <?php
                                $locale = get_locale();
                                $content_en = get_post_meta(get_the_ID(), '_content_en', true);

                                $content_de = get_the_content();
                                $content_en = isset($content_en) ? $content_en : ''; // Ensure $content_en is set

                                $teaser_text_key = ($locale === 'de_DE' || $locale === 'de_DE_formal') ? '_teasertext_de' : '_teasertext_en';
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
                            <?php else : ?>
                                <img src="<?php echo esc_url(plugins_url('rrze-faudir/assets/images/platzhalter-unisex.png', dirname(__FILE__, 2))); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
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
                        if ($locale === 'de_DE' || $locale === 'de_DE_formal' && !empty($content_de)): ?>
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
