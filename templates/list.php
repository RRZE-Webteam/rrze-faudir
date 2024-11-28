<ul class="fau-contacts-list-custom">
    <?php if (!empty($persons)) : ?>
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
                <div class="faudir-error">
                    <?php echo esc_html($person['message']); ?>
                </div>
            <?php else: ?>
                <?php if (!empty($person)) : ?>
                    <?php
                    $teaser_lang = '';

                    $contact_posts = get_posts([
                        'post_type' => 'custom_person',
                        'meta_key' => 'person_id',
                        'meta_value' => $person['identifier'],
                        'posts_per_page' => 1, // Only fetch one post matching the person ID
                    ]);
                    $cpt_url = !empty($contact_posts) ? get_permalink($contact_posts[0]->ID) : '';
                    if (!empty($contact_posts)) {
                        // Loop through each contact post
                        foreach ($contact_posts as $post) : {
                                // Check if the post has a UnivIS ID (person_id)
                                $identifier = get_post_meta($post->ID, 'person_id', true);

                                // Compare the identifier with the current person's identifier
                                if ($identifier === $person['identifier']) {
                                    $locale = get_locale(); 
                                    $teaser_text_key = ($locale === 'de_DE' || $locale === 'de_DE_formal') ? '_teasertext_de' : '_teasertext_en';
                                    $teaser_lang = get_post_meta($post->ID, $teaser_text_key, true);
                                }
                            }
                        endforeach;
                    } 

                    // Use custom post type URL if multiple persons or no direct URL
                    $final_url = (count($persons) > 1 || empty($url)) ? $cpt_url : $url; ?>

                    <li itemscope itemtype="https://schema.org/Person">
                        <!-- Full name with title -->
                        <?php
                        $options = get_option('rrze_faudir_options');
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];

                        $longVersion = '';
                        if ($hard_sanitize) {
                            $prefix = $person['personalTitle'] ?? '';
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
                        <?php if (!empty($person['contacts'])) : ?>
                            <?php foreach ($person['contacts'] as $contact) : ?>
                                <div>
                                    <?php if (!empty($contact['workplaces'])) : ?>
                                        <?php foreach ($contact['workplaces'] as $workplace) : ?>
                                            <span>
                                                <?php if (!empty($workplace['mails'])) : ?>
                                                    
                                                        <?php foreach ($workplace['mails'] as $email) : ?>
                                                            <p>
                                                            <?php $icon_data = get_social_icon_data('email'); ?>
                                                            <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                  style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                            <span class="screen-reader-text"><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></span>
                                                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                                        </p>
                                                        <?php endforeach; ?>
                                                    
                                                <?php endif; ?>
                                                        
                                                <?php if (!empty($workplace['phones'])) : ?>
                                                    
                                                        <?php foreach ($workplace['phones'] as $phone) : ?>
                                                            <p>
                                                            <?php $icon_data = get_social_icon_data('phone'); ?>
                                                            <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                  style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                            <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                                                            <?php echo esc_html($phone); ?>
                                                            </p>
                                                        <?php endforeach; ?>
                                                <?php endif; ?>
                                                        
                                                <?php if (!empty($workplace['url'])) : ?>
                                                    <p>
                                                        <?php $icon_data = get_social_icon_data('url'); ?>
                                                        <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                              style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                        <span class="screen-reader-text"><?php echo esc_html__('Url:', 'rrze-faudir'); ?></span>
                                                        <?php echo esc_html($workplace['url']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php
                        // Initialize output strings for email and phone
                        $email_output = '';
                        $phone_output = '';


                        // Check if email should be shown and output only if an email is available
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $email = (isset($person['email']) && !empty($person['email']))
                                ? esc_html($person['email'])
                                : ''; // Custom post type email

                            // Only display the email if it's not empty
                            if (!empty($email)) {
                                $icon_data = get_social_icon_data('email'); 
                                ?>
                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                    style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                    <span class="screen-reader-text"><?php echo esc_html__('Email:', 'rrze-faudir'); ?></span>
                                    <span itemprop="email"><?php echo esc_html($email) ?></span>
                           <?php }
                        }
                        // Check if phone should be shown and include N/A if not available
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $phone = (isset($person['telephone']) && !empty($person['telephone'])
                                ? esc_html($person['telephone'])
                                : '');
                            // Only display the email if it's not empty
                            if (!empty($phone)) {
                                $icon_data = get_social_icon_data('phone'); 
                                ?>
                                <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                                <span itemprop="phone"> <?php echo esc_html($phone) ?></span>;
                                <?php  }
                        }

                        ?>
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
                    </li>
                <?php else : ?>
                    <li itemprop><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else : ?>
        <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
    <?php endif; ?>
</ul>