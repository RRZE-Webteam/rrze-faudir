<?php if (!empty($persons)): ?>
    <?php foreach ($persons as $person): ?>
        <?php if (isset($person['error'])): ?>
            <div class="faudir-error">
                <?php echo esc_html($person['message']); ?>
            </div>
        <?php else: ?>
            <?php if (!empty($person)): ?>
                <?php
                $featured_image_url = '';

                // Check if a custom post type (CPT) with the same ID exists
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

                // If there are contact posts, populate the CPT variables
                if (!empty($contact_posts)) {
                    // Loop through each contact post
                    foreach ($contact_posts as $post): {
                            // Check if the post has a UnivIS ID (person_id)
                            $identifier = get_post_meta($post->ID, 'person_id', true);

                            // Compare the identifier with the current person's identifier
                            if ($identifier === $person['identifier']) {
                                // Use $post->ID instead of get_the_ID() to get the correct metadata
                                $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');
                            }
                        }
                    endforeach;
                }
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

                <div class="shortcode-contact-kompakt" itemscope itemtype="https://schema.org/Person">
                    <?php if (count($persons) === 1 && !empty($image_url)): ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                    <?php elseif (!empty($featured_image_url)): ?>
                        <img src="<?php echo esc_url($featured_image_url); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                    <?php else: ?>
                        <img src="<?php echo esc_url(plugins_url('rrze-faudir/assets/images/platzhalter-unisex.png', dirname(__FILE__, 2))); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                    <?php endif; ?>
                    <div style="flex-grow: 1;">
                        <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)): ?>
                            <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                                <?php if (!empty($final_url)): ?>
                                    <a href="<?php echo esc_url($final_url); ?>" itemprop="url">
                                        <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                                    </a>
                                <?php else: ?>
                                    <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                        <?php
                        // Initialize output strings for email and phone
                        $email_output = '';
                        $phone_output = '';
                        // Check if email should be shown and include N/A if it is not available
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $email = (isset($person['email']) && !empty($person['email']))
                                ? esc_html($person['email'])
                                : ''; // Custom post type email
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
                                : '');
                            // Only display the email if it's not empty
                            if (!empty($phone)) {
                                echo '<p>' . esc_html__('Phone:', 'rrze-faudir') . ' <span itemprop="phone">' . esc_html($phone) . '</span></p>';
                            }
                        }
                        ?>
                        <?php
                        $displayedOrganizations = [];
                        $organizationName = ''; // To track displayed organizations
                        ?>
                        <?php if (!empty($person['contacts'])): ?>
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
                            ?><?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) { ?>
                            <h4>
                                <span itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                                    <span itemprop="name"><?php echo esc_html($organizationName); ?></span>
                                </span>
                            </h4>
                        <?php } ?>
                        <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) { ?>
                            <?php if (!empty($functionLabel)): ?>
                                <span itemprop="jobTitle"><?php echo esc_html($functionLabel); ?></span>
                            <?php else: ?>
                                <p><?php echo esc_html__('No function available.', 'rrze-faudir'); ?></p>
                            <?php endif; ?>
                        <?php } ?>

                        <span class="screen-reader-text"><?php echo esc_html__('Organization Address:', 'rrze-faudir'); ?></span>
                        <div>
                            <?php if (!empty($contact['organization_address'])): ?>
                                <p>
                                    <?php if (!empty($contact['organization_address']['phone'])): ?>
                                        <?php
                                            // Fetch icon data for "phone"
                                            $icon_data = get_social_icon_data('phone'); 
                                        ?>
                                        <span 
                                            class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                            style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>');"></span>
                                            <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                                            <?php echo esc_html($contact['organization_address']['phone']); ?>
                                        </span><br>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['organization_address']['mail'])): ?>
                                        <?php
                                            // Fetch icon data for "phone"
                                            $icon_data = get_social_icon_data('email'); 
                                        ?>
                                        <span 
                                            class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                            style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>'); "></span>
                                            <span class="screen-reader-text"><?php echo esc_html__('Mail:', 'rrze-faudir'); ?></span>
                                            <?php echo esc_html($contact['organization_address']['mail']); ?>
                                        </span><br>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['organization_address']['url'])): ?>
                                        <?php
                                            // Fetch icon data for "phone"
                                            $icon_data = get_social_icon_data('url'); 
                                        ?>
                                        <span 
                                            class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                            style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>'); "></span>
                                            <span class="screen-reader-text"><?php echo esc_html__('Url:', 'rrze-faudir'); ?></span>
                                            <?php echo esc_html($contact['organization_address']['url']); ?>
                                        </span><br>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['organization_address']['street'])): ?>
                                        <span class="screen-reader-text"><?php echo esc_html__('Street:', 'rrze-faudir'); ?></span>
                                        <?php echo esc_html($contact['organization_address']['street']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['organization_address']['zip'])): ?>
                                        <span class="screen-reader-text"><?php echo esc_html__('ZIP Code:', 'rrze-faudir'); ?></span>
                                        <?php echo esc_html($contact['organization_address']['zip']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['organization_address']['city'])): ?>
                                        <span class="screen-reader-text"><?php echo esc_html__('City:', 'rrze-faudir'); ?></span>
                                        <?php echo esc_html($contact['organization_address']['city']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['organization_address']['faumap'])): ?>
                                        <span class="screen-reader-text"><?php echo esc_html__('Map:', 'rrze-faudir'); ?></span>
                                        <a href="<?php echo esc_url($contact['organization_address']['faumap']); ?>" target="_blank">
                                            <?php echo esc_html__('View on Map', 'rrze-faudir'); ?>
                                        </a><br>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php
                        /*
                        <span class="screen-reader-text"><?php echo esc_html__('Workplaces:', 'rrze-faudir'); ?></span>
                        <div>
                            <?php if (empty($contact['workplaces'])): ?>
                                <p><?php echo esc_html__('No workplaces available.', 'rrze-faudir'); ?></p>
                            <?php else: ?>
                                <?php foreach ($contact['workplaces'] as $workplace): ?>
                                    <p>
                                        <?php if (!empty($workplace['street'])): ?>
                                            <strong><?php echo esc_html__('Street:', 'rrze-faudir'); ?></strong>
                                            <?php echo esc_html($workplace['street']); ?><br>
                                        <?php endif; ?>

                                        <?php if (!empty($workplace['zip'])): ?>
                                            <strong><?php echo esc_html__('ZIP Code:', 'rrze-faudir'); ?></strong>
                                            <?php echo esc_html($workplace['zip']); ?><br>
                                        <?php endif; ?>

                                        <?php if (!empty($workplace['city'])): ?>
                                            <strong><?php echo esc_html__('City:', 'rrze-faudir'); ?></strong>
                                            <?php echo esc_html($workplace['city']); ?><br>
                                        <?php endif; ?>

                                        <?php if (!empty($workplace['faumap'])): ?>
                                            <strong><?php echo esc_html__('Map:', 'rrze-faudir'); ?></strong>
                                            <a href="<?php echo esc_url($workplace['faumap']); ?>" target="_blank">
                                                <?php echo esc_html__('View on Map', 'rrze-faudir'); ?>
                                            </a><br>
                                        <?php endif; ?>

                                        <?php if (!empty($workplace['mails'])): ?>
                                            <strong><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></strong>
                                    <ul>
                                        <?php foreach ($workplace['mails'] as $email): ?>
                                            <li><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($workplace['phones'])): ?>
                                    <strong><?php echo esc_html__('Phone numbers:', 'rrze-faudir'); ?></strong>
                                    <ul>
                                        <?php foreach ($workplace['phones'] as $phone): ?>
                                            <li><?php echo esc_html($phone); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($workplace['officeHours'])): ?>
                                    <strong><?php echo esc_html__('Office Hours:', 'rrze-faudir'); ?></strong>
                                    <ul>
                                        <?php foreach ($workplace['officeHours'] as $officeHour): ?>
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
                        */?>
                    <?php
                            } ?>
                <?php endif; ?>
                <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                    <?php if (!empty($person['contacts'][0]['socials'])): ?>
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
                                        <span class="screen-reader-text"><?php echo esc_html(ucfirst($icon_data['name'])); ?></span>
                                        </a>
                                <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                    </div>
                    <div>
                        <?php
                        if (in_array('kompaktButton', $show_fields) && !in_array('kompaktButton', $hide_fields)) {
                            $options = get_option('rrze_faudir_options', []);

                            if (isset($button_text) && !empty($button_text)) {
                                $button_title = $button_text;
                            } else {
                                $button_title = isset($options['business_card_title']) && !empty($options['business_card_title'])
                                    ? $options['business_card_title']
                                    : esc_html__('Call up business card', 'rrze-faudir');
                            }

                            if (!empty($final_url)) {
                                echo '<a href="' . esc_url($final_url) . '" itemprop="url" class="business-card-link button-link">' . esc_html($button_title) . '</a>';
                            }
                        }
                        ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>

<?php else: ?>
    <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?></div>
<?php endif; ?>