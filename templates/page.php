<?php if (!empty($persons)) : ?>
    <div>
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
                <div class="faudir-error">
                    <?php echo esc_html($person['message']); ?>
                </div>
            <?php else: ?>
                <?php if (!empty($person)) : ?>
                    <?php
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
                    // Get the custom post type URL if available
                    $cpt_url = !empty($contact_posts) ? get_permalink($contact_posts[0]->ID) : '';

                    // Use custom post type URL if multiple persons or no direct URL
                    $final_url = (count($persons) > 1 || empty($url)) ? $cpt_url : $url;

                    // If there are contact posts, process them
                    if (!empty($contact_posts)) {
                        // Loop through each contact post
                        foreach ($contact_posts as $post) : {
                                // Check if the post has a UnivIS ID (person_id)
                                $identifier = get_post_meta($post->ID, 'person_id', true);

                                // Compare the identifier with the current person's identifier
                                if ($identifier === $person['identifier']) {
                                    // Use $post->ID instead of get_the_ID() to get the correct metadata
                                    $content_en = get_post_meta($post->ID, '_content_en', true);
                                    $content = apply_filters('the_content', $post->post_content);
                                    $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');

                                    // New Code to Add: Handling multiple languages (de_DE and en)
                                    $locale = get_locale(); // Get the current locale
                                    $content_en = get_post_meta($post->ID, '_content_en', true); // English content from post meta
                                    $content_de = apply_filters('the_content', $post->post_content); // Default post content (assumed to be in German)

                                    // Ensure $content_en is set
                                    $content_en = isset($content_en) ? $content_en : '';
                                    $teaser_text_key = ($locale === 'de_DE' || $locale === 'de_DE_formal') ? '_teasertext_de' : '_teasertext_en';
                                    $teaser_lang = get_post_meta($post->ID, $teaser_text_key, true);
                                }
                            }
                        endforeach;
                    } ?>
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
                                if (in_array('givenName', $show_fields) && !in_array('givenName', $hide_fields)) {
                                    $first_name = (isset($person['givenName']) && !empty($person['givenName']) ? esc_html($person['givenName']) : '');
                                }
                                if (in_array('titleOfNobility', $show_fields) && !in_array('titleOfNobility', $hide_fields)) {
                                    $nobility_title = (isset($person['titleOfNobility']) && !empty($person['titleOfNobility']) ? esc_html($person['titleOfNobility']) : '');
                                }
                                if (in_array('familyName', $show_fields) && !in_array('familyName', $hide_fields)) {
                                    $last_name = (isset($person['familyName']) && !empty($person['familyName']) ? esc_html($person['familyName']) : '');
                                }
                                if (in_array('personalTitleSuffix', $show_fields) && !in_array('personalTitleSuffix', $hide_fields)) {
                                    $title_suffix = (isset($person['personalTitleSuffix']) && !empty($person['personalTitleSuffix']) ? ' (' . esc_html($person['personalTitleSuffix']) . ')' : '');
                                }

                                // Construct the full name
                                $fullName = trim(
                                    ($longVersion ? $longVersion : $personal_title) . ' ' .
                                        ($first_name) . ' ' .
                                        ($nobility_title) . ' ' .
                                        ($last_name) . ' ' .
                                        ($title_suffix)
                                );
                                ?>
                                <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
                                    <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                                        <?php if (!empty($final_url)) : ?>
                                            <a href="<?php echo esc_url($final_url); ?>" itemprop="url">
                                                <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                                            </a>
                                        <?php else : ?>
                                            <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                                        <?php endif; ?>
                                    </section>
                                <?php endif; ?>
                           
                                <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                                    <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                                        <div>
                                        <span class="screen-reader-text"><?php echo esc_html__('Social Profiles:', 'rrze-faudir'); ?></span>
                                                <?php foreach ($person['contacts'][0]['socials'] as $social):
                                                    $icon_data = get_social_icon_data($social['platform']);
                                                ?>
                                                        <a href="<?php echo esc_url($social['url']); ?>" 
                                                           class="<?php echo esc_attr($icon_data['css_class']); ?>"
                                                           style="background-image: url('<?php echo esc_url($icon_data['icon_url']); ?>');"
                                                           target="_blank" 
                                                           rel="noopener noreferrer">
                                                           <span class="screen-reader-text"> <?php echo esc_html(ucfirst($icon_data['name'])); ?></span>
                                                        </a>
                                                <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (in_array('teasertext', $show_fields) && !in_array('teasertext', $hide_fields)) { ?>
                                    <?php
                                    if (!empty($teaser_lang)) :
                                    ?>
                                        <div class="teaser-second-language">
                                            <?php echo wp_kses_post($teaser_lang); ?>
                                        </div>
                                    <?php
                                    endif;
                                    ?>
                                <?php } ?>
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
                                        $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : '';
                                        $locale = get_locale();
                                        $isGerman = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_DE_formal') !== false;

                                        // Determine function label
                                        $functionLabel = '';
                                        if (!empty($contact['functionLabel'])) {
                                            $functionLabel = $isGerman ?
                                                (isset($contact['functionLabel']['de']) ? $contact['functionLabel']['de'] : '') : (isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : '');
                                        }

                                        // Display each organization and associated details
                                    ?>
                                        <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) { ?>
                                            <h4><span class="screen-reader-text"><?php echo esc_html__('Organization:', 'rrze-faudir'); ?></span>
                                                <span itemprop="worksFor" itemscope itemtype="https://schema.org/Organization">
                                                    <span itemprop="name"><?php echo esc_html($organizationName); ?></span>
                                                </span>
                                        </h4>
                                        <?php } ?>
                                        <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) { ?>
                                            <?php if (!empty($functionLabel)) : ?>
                                                <span class="screen-reader-text"><?php echo esc_html__('Function:', 'rrze-faudir'); ?></span>
                                                <p itemprop="jobTitle"><?php echo esc_html($functionLabel); ?></p>
                                            <?php else : ?>
                                                <span><?php echo esc_html__('No function available.', 'rrze-faudir'); ?></span>
                                            <?php endif; ?>
                                        <?php } ?>

                                        <span class="screen-reader-text"><?php echo esc_html__('Workplaces:', 'rrze-faudir'); ?></span>
                                        <div>
                                        <?php if (empty($contact['workplaces'])) : ?>
                                            <?php
                                            $email_output = '';
                                            $phone_output = '';

                                            if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                                                $email = !empty($person['email']) ? esc_html($person['email']) : '';
                                            
                                                if (!empty($email)) {
                                                    $icon_data = get_social_icon_data('email'); ?>
                                                    <p>
                                                        <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                              style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                        <span class="screen-reader-text"><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></span>
                                                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                                    </p>
                                                <?php }
                                            }
                                        
                                            if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                                                $phone = !empty($person['telephone']) ? esc_html($person['telephone']) : '';
                                            
                                                if (!empty($phone)) {
                                                    $icon_data = get_social_icon_data('phone'); ?>
                                                    <p>
                                                        <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                              style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                        <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                                                        <?php echo esc_html($phone); ?>
                                                    </p>
                                                <?php }
                                            }
                                            ?>

                                            <!-- Fallback message for no workplaces -->
                                            <p><?php echo esc_html__('No workplaces available.', 'rrze-faudir'); ?></p>

                                            <?php else : ?>
                                                <?php if (in_array('workplaces', $show_fields) && !in_array('workplaces', $hide_fields)) : ?>
                                                    <?php foreach ($contact['workplaces'] as $workplace) : ?>
                                                        <p> 
                                                        <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['mails'])) : ?>
                                                                <?php foreach ($workplace['mails'] as $email) : ?>
                                                                    <p><?php $icon_data = get_social_icon_data('email'); ?>
                                                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                    style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                                    <span class="screen-reader-text"><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></span>
                                                                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
                                                                <?php endforeach; ?>        
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['phones'])) : ?>
                                                                <?php foreach ($workplace['phones'] as $phone) : ?>
                                                                    <p><?php $icon_data = get_social_icon_data('phone');?>
                                                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                    style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                                    <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                                                                    <?php echo esc_html($phone); ?></p>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if (in_array('url', $show_fields) && !in_array('url', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['url'])) : ?>
                                                                <?php $icon_data = get_social_icon_data('url');?>
                                                                <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                                <span class="screen-reader-text"><?php echo esc_html__('Url:', 'rrze-faudir'); ?></span>
                                                                <?php echo esc_html($workplace['url']); ?><br>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                            
                                                        <?php if (in_array('room', $show_fields) && !in_array('room', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['room'])) : ?>
                                                                <span class="screen-reader-text"><?php echo esc_html__('Room:', 'rrze-faudir'); ?></span>
                                                                <?php echo esc_html($workplace['room']); ?><br>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if (in_array('floor', $show_fields) && !in_array('floor', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['floor'])) : ?>
                                                                <span class="screen-reader-text"><?php echo esc_html__('Floor:', 'rrze-faudir'); ?></span>
                                                                <?php echo esc_html($workplace['floor']); ?><br>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if (!empty($workplace['street']) || !empty($workplace['zip']) || !empty($workplace['city'])) : ?>
                                                            <span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                                                                <?php if (in_array('street', $show_fields) && !in_array('street', $hide_fields)) : ?>
                                                                    <?php if (!empty($workplace['street'])) : ?>
                                                                        <span class="screen-reader-text"><?php echo esc_html__('Street:', 'rrze-faudir'); ?></span>
                                                                        <?php echo esc_html($workplace['street']); ?><br>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                                <?php if (in_array('zip', $show_fields) && !in_array('zip', $hide_fields)) : ?>
                                                                    <?php if (!empty($workplace['zip'])) : ?>
                                                                        <span class="screen-reader-text"><?php echo esc_html__('ZIP Code:', 'rrze-faudir'); ?></span>
                                                                        <?php echo esc_html($workplace['zip']); ?><br>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                                <?php if (in_array('city', $show_fields) && !in_array('city', $hide_fields)) : ?>
                                                                    <?php if (!empty($workplace['city'])) : ?>
                                                                        <span class="screen-reader-text"><?php echo esc_html__('City:', 'rrze-faudir'); ?></span>
                                                                        <?php echo esc_html($workplace['city']); ?><br>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['faumap'])) : ?>
                                                                <span class="screen-reader-text"><?php echo esc_html__('Map:', 'rrze-faudir'); ?></span>
                                                                <a href="<?php echo esc_url($workplace['faumap']); ?>" target="_blank">
                                                                    <?php echo esc_html__('View on Map', 'rrze-faudir'); ?>
                                                                </a><br>
                                                            <?php endif; ?>
                                                        <?php endif; ?>


                                                        <?php if (in_array('officehours', $show_fields) && !in_array('officehours', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['officeHours'])) : ?>
                                                                <div itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">
                                                                    <meta itemprop="contactType" content="office hours" />
                                                                    <strong><?php echo esc_html__('Office Hours:', 'rrze-faudir'); ?></strong>
                                                                    <ul>
                                                                        <?php foreach ($workplace['officeHours'] as $officeHours) : ?>
                                                                            <li itemscope itemtype="https://schema.org/OpeningHoursSpecification" itemprop="hoursAvailable">
                                                                                <div itemprop="dayOfWeek" content="https://schema.org/<?php echo esc_attr($weekdayMap[$officeHours['weekday']] ?? 'Unknown'); ?>">
                                                                                    <strong><?php echo esc_html($weekdayMap[$officeHours['weekday']] ?? 'Unknown'); ?>:</strong>
                                                                                </div>
                                                                                <span itemprop="opens"><?php echo esc_html($officeHours['from']); ?></span> - 
                                                                                <span itemprop="closes"><?php echo esc_html($officeHours['to']); ?></span>
                                                                                <?php if (!empty($officeHours['comment'])) : ?>
                                                                                    <p itemprop="description">
                                                                                        <?php echo esc_html($officeHours['comment']); ?>
                                                                                    </p>
                                                                                <?php endif; ?>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if (in_array('consultationhours', $show_fields) && !in_array('consultationhours', $hide_fields)) : ?>
                                                            <?php if (!empty($workplace['consultationHours'])) : ?>
                                                                <div itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">
                                                                    <meta itemprop="contactType" content="consultation hours" />
                                                                    <strong><?php echo esc_html__('Consultation Hours:', 'rrze-faudir'); ?></strong>
                                                                    <ul>
                                                                        <?php foreach ($workplace['consultationHours'] as $consultationHours) : ?>
                                                                            <li itemscope itemtype="https://schema.org/OpeningHoursSpecification" itemprop="hoursAvailable">
                                                                                <div itemprop="dayOfWeek" content="https://schema.org/<?php echo esc_attr($weekdayMap[$consultationHours['weekday']] ?? 'Unknown'); ?>">
                                                                                    <strong><?php echo esc_html($weekdayMap[$consultationHours['weekday']] ?? 'Unknown'); ?>:</strong>
                                                                                </div>
                                                                                <span itemprop="opens"><?php echo esc_html($consultationHours['from']); ?></span> - 
                                                                                <span itemprop="closes"><?php echo esc_html($consultationHours['to']); ?></span>
                                                                                <?php if (!empty($consultationHours['comment'])) : ?>
                                                                                    <p itemprop="description">
                                                                                        <?php echo esc_html($consultationHours['comment']); ?>
                                                                                    </p>
                                                                                <?php endif; ?>
                                                                                <p>
                                                                                    <a href="<?php echo esc_url($consultationHours['url']); ?>" itemprop="url">
                                                                                        <?php echo esc_html($consultationHours['url']); ?>
                                                                                    </a>
                                                                                </p>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        </p>
                                                    <hr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>

                            <?php if (count($persons) === 1 && !empty($image_url)) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                            <?php elseif (!empty($featured_image_url)) : ?>
                                <img src="<?php echo esc_url($featured_image_url); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                            <?php else : ?>
                                <img src="<?php echo esc_url(plugins_url('rrze-faudir/assets/images/platzhalter-unisex.png', dirname(__FILE__, 2))); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                            <?php endif; ?>
                        </div>


                        <?php if (in_array('content', $show_fields) && !in_array('content', $hide_fields)) { ?>
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
                        <?php } ?>

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