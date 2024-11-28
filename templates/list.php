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

                        $longVersion = $hard_sanitize ? FaudirUtils::getAcademicTitleLongVersion($person['personalTitle'] ?? '') : '';
                        $personal_title = '';
                        $first_name = '';
                        $nobility_title = '';
                        $last_name = '';
                        $title_suffix = '';
                        if (in_array('personalTitle', $show_fields) && !in_array('personalTitle', $hide_fields)) {
                            $personal_title = isset($person['personalTitle']) && !empty($person['personalTitle']) 
                                ? esc_html($person['personalTitle'])
                                : '';
                            
                            if ($personal_title && $hard_sanitize) {
                                $personal_title = FaudirUtils::getAcademicTitleLongVersion($personal_title);
                            }
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
                            ($personal_title) . ' ' .
                                ($first_name) . ' ' .
                                ($nobility_title) . ' ' .
                                ($last_name) . ' ' .
                                ($title_suffix)
                        );
                        ?>
                        <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
                            <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                                <?php if (!empty($final_url)) : ?>
                                    <a href="<?php echo esc_url($final_url); ?>">
                                        <?php echo FaudirUtils::getPersonNameHtml([
                                            'personal_title' => $personal_title,
                                            'first_name' => $first_name,
                                            'nobility_title' => $nobility_title,
                                            'last_name' => $last_name,
                                            'title_suffix' => $title_suffix,
                                            'identifier' => $person['identifier']
                                        ]); ?>
                                    </a>
                                <?php else : echo FaudirUtils::getPersonNameHtml([
                                    'personal_title' => $personal_title,
                                    'first_name' => $first_name,
                                    'nobility_title' => $nobility_title,
                                    'last_name' => $last_name,
                                    'title_suffix' => $title_suffix,
                                    'identifier' => $person['identifier']
                                ]); ?>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                        <?php if (!empty($person['contacts'])) : ?>
                        <?php
                        // Initialize arrays to keep track of unique emails and phones
                        $unique_emails = [];
                        $unique_phones = [];

                        // Include person's email and telephone if available
                        if (!empty($person['email'])) {
                            $unique_emails[] = $person['email'];
                            echo '<span>';
                            $icon_data = get_social_icon_data('email');
                            ?>
                            <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                  style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                            <span class="screen-reader-text"><?php echo esc_html__('Email:', 'rrze-faudir'); ?></span>
                            <a href="mailto:<?php echo esc_attr($person['email']); ?>"><?php echo esc_html($person['email']); ?></a>
                            <?php
                            echo '</span>';
                        }
                    
                        if (!empty($person['telephone'])) {
                            $unique_phones[] = $person['telephone'];
                            echo '<span>';
                            $icon_data = get_social_icon_data('phone');
                            ?>
                            <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                  style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                            <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                            <?php echo esc_html($person['telephone']); ?>
                            <?php
                            echo '</span>';
                        }
                        ?>
<div>
                        <?php foreach ($person['contacts'] as $contact) : ?>
                            
                                <?php if (!empty($contact['workplaces'])) : ?>
                                    <?php foreach ($contact['workplaces'] as $workplace) : ?>
                                        <span>
                                            <?php if (!empty($workplace['mails'])) : ?>
                                                <?php foreach ($workplace['mails'] as $email) : ?>
                                                    <?php if (!in_array($email, $unique_emails)) : // Check if email is already added ?>
                                                        <span>
                                                            <?php $icon_data = get_social_icon_data('email'); ?>
                                                            <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                  style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                            <span class="screen-reader-text"><?php echo esc_html__('Email:', 'rrze-faudir'); ?></span>
                                                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                                        </span>
                                                        <?php $unique_emails[] = $email; // Add to the array of unique emails ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                                    
                                            <?php if (!empty($workplace['phones'])) : ?>
                                                <?php foreach ($workplace['phones'] as $phone) : ?>
                                                    <?php if (!in_array($phone, $unique_phones)) : // Check if phone is already added ?>
                                                        <span>
                                                            <?php $icon_data = get_social_icon_data('phone'); ?>
                                                            <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                                  style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                            <span class="screen-reader-text"><?php echo esc_html__('Phone:', 'rrze-faudir'); ?></span>
                                                            <?php echo esc_html($phone); ?>
                                                        </span>
                                                        <?php $unique_phones[] = $phone; // Add to the array of unique phones ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                                    
                                            <?php if (!empty($workplace['url'])) : ?>
                                                <span>
                                                    <?php $icon_data = get_social_icon_data('url'); ?>
                                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                                          style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                                    <span class="screen-reader-text"><?php echo esc_html__('Url:', 'rrze-faudir'); ?></span>
                                                    <?php echo esc_html($workplace['url']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            
                        <?php endforeach; ?>
                        </div>
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
                    </li>
                <?php else : ?>
                    <li><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else : ?>
        <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
    <?php endif; ?>
</ul>