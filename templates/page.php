<?php if (!empty($persons)) : ?>
    <div itemscope itemtype="https://schema.org/ProfilePage">
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
            <div class="faudir-error">
                <?php echo esc_html($person['message']); ?>
            </div>
            <?php else: ?>
            <?php if (!empty($person)) : ?>
            <?php
             $personal_title_cpt = '';
             $first_name_cpt = '';
             $nobility_title_cpt = '';
             $last_name_cpt = '';
             $title_suffix_cpt = '';
             $email_output_cpt = '';
             $phone_output_cpt = '';
             $function_label_cpt = '';
             $organization_name_cpt = '';
             $content_en = '';
             $content = '';
             $teaser_lang = '';
             $featured_image_url = '';
             
             // Check if a CPT with the same ID exists
             $contact_posts = get_posts([
                 'post_type' => 'custom_person',
                 'meta_key' => 'person_id',
                 'meta_value' => $person['identifier'],
                 'posts_per_page' => 1, // Only fetch one post matching the person ID
             ]);
 
            // If there are contact posts, process them
        if (!empty($contact_posts)) {
                // Loop through each contact post
                foreach ($contact_posts as $post) : {
                    // Check if the post has a UnivIS ID (person_id)
                    $identifier = get_post_meta($post->ID, 'person_id', true);
                    
                    // Compare the identifier with the current person's identifier
                    if ($identifier === $person['identifier']) {
                        // Use $post->ID instead of get_the_ID() to get the correct metadata
                        $personal_title_cpt = get_post_meta($post->ID, 'person_title', true);
                        $first_name_cpt = get_post_meta($post->ID, 'person_given_name', true);
                        $nobility_title_cpt = get_post_meta($post->ID, 'person_nobility_name', true);
                        $last_name_cpt = get_post_meta($post->ID, 'person_family_name', true);
                        $title_suffix_cpt = get_post_meta($post->ID, 'person_suffix', true);
                        $email_output_cpt = get_post_meta($post->ID, 'person_email', true);
                        $phone_output_cpt = get_post_meta($post->ID, 'person_telephone', true);
                        $function_label_cpt = get_post_meta($post->ID, 'person_function', true);
                        $organization_name_cpt = get_post_meta($post->ID, 'person_organization', true);
                        $content_en = get_post_meta($post->ID, '_content_en', true);
                        $content = apply_filters('the_content', $post->post_content);
                        $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');
                        
                        // New Code to Add: Handling multiple languages (de_DE and en)
                        $locale = get_locale(); // Get the current locale
                        $content_en = get_post_meta($post->ID, '_content_en', true); // English content from post meta
                        $content_de = apply_filters('the_content', $post->post_content); // Default post content (assumed to be in German)

                        // Ensure $content_en is set
                        $content_en = isset($content_en) ? $content_en : '';
                        $teaser_text_key = ($locale === 'de_DE' || $locale === 'de_DE_formal' ) ? '_teasertext_de' : '_teasertext_en';
                        $teaser_lang = get_post_meta($post->ID, $teaser_text_key, true);    
                    }
                }
            endforeach;
        }?>
            <div class="contact-page">     
                <div class="contact-page-img-container">
                <div style="flex-grow: 1; max-width:70%" itemscope itemtype="https://schema.org/Person">
                        
                        <!-- Full name with title -->
                        <?php
                        $options = get_option('rrze_faudir_options');
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                        $longVersion = "";
                        if ($hard_sanitize) {
                            $prefix = $person['personalTitle'];
                            $prefixes = array(
                                '' => __('Not specified', 'rrze-faudir'),
                                'Dr.' => __('Doktor', 'rrze-faudir'),
                                'Prof.' => __('Professor', 'rrze-faudir'),
                                'Prof. Dr.' => __('Professor Doktor', 'rrze-faudir'),
                                'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
                                'Prof. Dr. em.' => __('Professor Doktor (Emeritus)', 'rrze-faudir'),
                                'PD' => __('Privatdozent', 'rrze-faudir'),
                                'PD Dr.' => __('Privatdozent Doktor', 'rrze-faudir')
                            );
                            // Check if the prefix exists in the array and display the long version
                            $longVersion = isset($prefixes[$prefix]) ? $prefixes[$prefix] : __('Unknown', 'rrze-faudir');
                        }

                        // Define fields for structured data
                        $personal_title = "";
                        $first_name = "";
                        $nobility_title = "";
                        $last_name = "";
                        $title_suffix = "";
                        
                        if (in_array('personalTitle', $show_fields) && !in_array('personalTitle', $hide_fields)) {
                            $personal_title = (isset($person['personalTitle']) && !empty($person['personalTitle']) ? esc_html($person['personalTitle']) : '');
                        }
                        if (in_array('firstName', $show_fields) && !in_array('firstName', $hide_fields)) {
                            $first_name = (isset($person['givenName']) && !empty($person['givenName']) ? esc_html($person['givenName']) : '');
                        }
                        if (in_array('titleOfNobility', $show_fields) && !in_array('titleOfNobility', $hide_fields)) {
                            $nobility_title = (isset($person['titleOfNobility']) && !empty($person['titleOfNobility']) ? esc_html($person['titleOfNobility']) : '');
                        }
                        if (in_array('familyName', $show_fields) && !in_array('familyName', $hide_fields)) {
                            $last_name = (isset($person['familyName']) && !empty($person['familyName']) ? esc_html($person['familyName']) : '');
                        }
                        if (in_array('personalTitleSuffix', $show_fields) && !in_array('personalTitleSuffix', $hide_fields)) {
                            $title_suffix = (isset($person['personalTitleSuffix']) && !empty($person['personalTitleSuffix']) ? esc_html($person['personalTitleSuffix']) : '');
                        }
                        
                        $fullName = trim(($longVersion ? $longVersion : ($personal_title ? $personal_title : $personal_title_cpt)) . ' ' 
                        . ($first_name ? $first_name : $first_name_cpt) . ' ' 
                        .($nobility_title ? $nobility_title : $nobility_title_cpt)  . ' ' 
                        . ($last_name ? $last_name : $last_name_cpt) . ' ' 
                        . ($title_suffix ? $title_suffix : $title_suffix_cpt));
                        ?>
                        
                        <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                        <?php if (!empty($url)) : ?>
                            <a href="<?php echo esc_url($url); ?>" itemprop="url">
                                <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                            </a>
                        <?php else : ?>
                        <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                        <?php endif; ?>
                        </section>
                        <?php
                        // Initialize output strings for email and phone
                        $email_output = '';
                        $phone_output = '';

                       // Check if email should be shown and output only if an email is available
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $email = (isset($person['email']) && !empty($person['email'])) 
                                ? esc_html($person['email']) 
                                : esc_html($email_output_cpt); // Custom post type email

                            // Only display the email if it's not empty
                            if (!empty($email)) {
                                echo '<p>' . esc_html__('Email:', 'rrze-faudir') . ' <span itemprop="email">' . esc_html($email) . '</span></p>';
                            }
                        }
                        // Check if phone should be shown and include N/A if not available
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $phone = (isset($person['telephone']) && !empty($person['telephone']) 
                            ? esc_html($person['telephone']) 
                            : esc_html($phone_output_cpt));
                            // Only display the email if it's not empty
                            if (!empty($phone)) {
                                echo '<p>' . esc_html__('Phone:', 'rrze-faudir') . ' <span itemprop="phone">' . esc_html($phone) . '</span></p>';
                            }
                        }
                        ?>
                        <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                            <div>
                                <h3><?php echo esc_html__('Social Profiles:', 'rrze-faudir'); ?></h3>
                                <ul style="list-style: none; padding: 0;">
                                    <?php 
                                    // FontAwesome icon mapping for platforms
                                    $iconMap = [
                                        'github' => 'fab fa-github',
                                        'xing' => 'fab fa-xing',
                                        'bluesky' => 'fas fa-cloud',
                                        'twitter' => 'fab fa-twitter',
                                        'facebook' => 'fab fa-facebook',
                                        'linkedin' => 'fab fa-linkedin',
                                        'instagram' => 'fab fa-instagram',
                                        'youtube' => 'fab fa-youtube',
                                        'tiktok' => 'fab fa-tiktok',
                                        'whatsapp' => 'fab fa-whatsapp',
                                        'snapchat' => 'fab fa-snapchat-ghost',
                                        'reddit' => 'fab fa-reddit',
                                        'pinterest' => 'fab fa-pinterest',
                                        'telegram' => 'fab fa-telegram',
                                        'discord' => 'fab fa-discord',
                                        'medium' => 'fab fa-medium',
                                        'vimeo' => 'fab fa-vimeo',
                                        'twitch' => 'fab fa-twitch',
                                        'spotify' => 'fab fa-spotify',
                                        'slack' => 'fab fa-slack',
                                        'dribbble' => 'fab fa-dribbble',
                                        'behance' => 'fab fa-behance',
                                        'flickr' => 'fab fa-flickr',
                                        'mastodon' => 'fab fa-mastodon',
                                        'goodreads' => 'fas fa-book',
                                        'strava' => 'fab fa-strava',
                                        'rss' => 'fas fa-rss',
                                        'zoom' => 'fas fa-video',
                                        'bsky' => 'fas fa-cloud', // Alias for Bluesky
                                    ];
                                
                                    foreach ($person['contacts'][0]['socials'] as $social) : 
                                        $platform = strtolower($social['platform']);
                                        $url = $social['url'];
                                        $iconClass = isset($iconMap[$platform]) ? $iconMap[$platform] : 'fas fa-link'; // Default to link icon if not found
                                    ?>
                                        <li style="margin-bottom: 8px;">
                                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
                                                <i class="<?php echo esc_attr($iconClass); ?>" style="margin-right: 8px;"></i>
                                                <?php echo esc_html(ucfirst($platform)); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php
                        if (!empty($teaser_lang)) :
                            ?>
                                <div class="teaser-second-language">
                                    <?php echo wp_kses_post($teaser_lang); ?>
                                </div>
                            <?php
                            endif;
                            ?>
                     </div>

                <?php if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" itemprop="image" />
                    <?php else :
                        if (!empty($featured_image_url)) : ?>
                            <img src="<?php echo esc_url($featured_image_url); ?>" alt="Person Image" itemprop="image" />
                        <?php endif;
                    endif; ?>
                </div>
                <!-- Array to track displayed organizations -->
                <?php
                $displayedOrganizations = []; // To track displayed organizations
                ?>
           <?php if (!empty($person['contacts'])) : ?>
            <?php 
            $weekdayMap = [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday',
            ]; 
            foreach ($person['contacts'] as $contact) {
                $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : $organization_name_cpt;
                $locale = get_locale();
                $isGerman = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_DE_formal') !== false;
                
                // Determine function label
                $functionLabel = '';
                if (!empty($contact['functionLabel'])) {
                    $functionLabel = $isGerman ? 
                        (isset($contact['functionLabel']['de']) ? $contact['functionLabel']['de'] : '') : 
                        (isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : '');
                } elseif (!empty($function_label_cpt)) {
                    $functionLabel = $function_label_cpt;
                }
                
                // Display each organization and associated details
            ?>
            <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) { ?>
                <p><strong><?php echo esc_html__('Organization:', 'rrze-faudir'); ?></strong> 
                    <span itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                        <span itemprop="name"><?php echo esc_html($organizationName); ?></span>
                    </span>
                </p>
                <?php } ?>
                <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) { ?>
                    <?php if (!empty($functionLabel)) : ?>
                    <strong><?php echo esc_html__('Function:', 'rrze-faudir'); ?></strong>
                    <p itemprop="jobTitle"><?php echo esc_html($functionLabel); ?></p>
                <?php else : ?>
                    <span><?php echo esc_html__('No function available.', 'rrze-faudir'); ?></span>
                <?php endif; ?>
                <?php } ?>
                
                <h3><?php echo esc_html__('Organization Address:', 'rrze-faudir'); ?></h3>
                <div>
                    <?php if (!empty($contact['organization_address'])) : ?>
                        <p>
                            <?php if (!empty($contact['organization_address']['phone'])) : ?>
                                <strong><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($contact['organization_address']['phone']); ?><br>
                            <?php endif; ?>
                            
                            <?php if (!empty($contact['organization_address']['mail'])) : ?>
                                <strong><?php echo esc_html__('Mail:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($contact['organization_address']['mail']); ?><br>
                            <?php endif; ?>
                            
                            <?php if (!empty($contact['organization_address']['url'])) : ?>
                                <strong><?php echo esc_html__('Url:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($contact['organization_address']['url']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($contact['organization_address']['street'])) : ?>
                                <strong><?php echo esc_html__('Street:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($contact['organization_address']['street']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($contact['organization_address']['zip'])) : ?>
                                <strong><?php echo esc_html__('ZIP Code:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($contact['organization_address']['zip']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($contact['organization_address']['city'])) : ?>
                                <strong><?php echo esc_html__('City:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($contact['organization_address']['city']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($contact['organization_address']['faumap'])) : ?>
                                <strong><?php echo esc_html__('Map:', 'rrze-faudir'); ?></strong>
                                <a href="<?php echo esc_url($contact['organization_address']['faumap']); ?>" target="_blank">
                                    <?php echo esc_html__('View on Map', 'rrze-faudir'); ?>
                                </a><br>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                            
                <h3><?php echo esc_html__('Workplaces:', 'rrze-faudir'); ?></h3>
                <div>
                <?php if (empty($contact['workplaces'])) : ?>
                    <p><?php echo esc_html__('No workplaces available.', 'rrze-faudir'); ?></p>
                <?php else : ?>
                    <?php foreach ($contact['workplaces'] as $workplace) : ?>
                        <p>
                            <?php if (!empty($workplace['street'])) : ?>
                                <strong><?php echo esc_html__('Street:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($workplace['street']); ?><br>
                            <?php endif; ?>
                            
                            <?php if (!empty($workplace['zip'])) : ?>
                                <strong><?php echo esc_html__('ZIP Code:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($workplace['zip']); ?><br>
                            <?php endif; ?>
                            
                            <?php if (!empty($workplace['city'])) : ?>
                                <strong><?php echo esc_html__('City:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($workplace['city']); ?><br>
                            <?php endif; ?>
                            
                            <?php if (!empty($workplace['faumap'])) : ?>
                                <strong><?php echo esc_html__('Map:', 'rrze-faudir'); ?></strong>
                                <a href="<?php echo esc_url($workplace['faumap']); ?>" target="_blank">
                                    <?php echo esc_html__('View on Map', 'rrze-faudir'); ?>
                                </a><br>
                            <?php endif; ?>
                            
                            <?php if (!empty($workplace['mails'])) : ?>
                                <strong><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></strong>
                                <ul>
                                    <?php foreach ($workplace['mails'] as $email) : ?>
                                        <li><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                                    
                            <?php if (!empty($workplace['phones'])) : ?>
                                <strong><?php echo esc_html__('Phone numbers:', 'rrze-faudir'); ?></strong>
                                <ul>
                                    <?php foreach ($workplace['phones'] as $phone) : ?>
                                        <li><?php echo esc_html($phone); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                                    
                            <?php if (!empty($workplace['officeHours'])) : ?>
                                <strong><?php echo esc_html__('Office Hours:', 'rrze-faudir'); ?></strong>
                                <ul>
                                    <?php foreach ($workplace['officeHours'] as $officeHour) : ?>
                                        <li>
                                            <strong><?php echo esc_html($weekdayMap[$officeHour['weekday']] ?? 'Unknown'); ?>:</strong>
                                            <?php echo esc_html($officeHour['from'] . ' - ' . $officeHour['to']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </p>
                        <hr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            <?php } ?>
        <?php endif; ?>

                      
                <?php if ($locale === 'de_DE' || $locale === 'de_DE_formal' && !empty($content_de)): ?>
                    <section class="card-section-title"><?php esc_html__('Content', 'rrze-faudir'); ?></section>
                    <div class="content-second-language">
                        <?php echo wp_kses_post($content_de); ?>
                    </div>
                <?php elseif ($locale !== 'de_DE' || $locale === 'de_DE_formal' && !empty($content_en)): ?>
                    <section class="card-section-title"><?php esc_html__('Content', 'rrze-faudir'); ?></section>
                    <div class="content-second-language">
                        <?php echo wp_kses_post($content_en); ?>
                    </div>
                <?php endif; ?>

            </div> <!-- End of shortcode-contact-card -->
            <?php else : ?>
                <div class="contact-page"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div> <!-- End of shortcode-contacts-wrapper -->
<?php else : ?>
    <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
<?php endif; ?>