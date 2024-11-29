<table class="fau-contacts-table-custom">
    <tbody>
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
                <div class="faudir-error">
                    <?php echo esc_html($person['message']); ?>
                </div>
            <?php else: ?>
                <?php if (!empty($person)) : ?>
                    <?php
                    $contact_posts = get_posts([
                        'post_type' => 'custom_person',
                        'meta_key' => 'person_id',
                        'meta_value' => $person['identifier'],
                        'posts_per_page' => 1, // Only fetch one post matching the person ID
                    ]);
                    $cpt_url = !empty($contact_posts) ? get_permalink($contact_posts[0]->ID) : '';

                    // Use custom post type URL if multiple persons or no direct URL
                    $final_url = (count($persons) > 1 || empty($url)) ? $cpt_url : $url; ?>
                    <tr itemscope itemtype="https://schema.org/Person">
                        <!-- Full Name -->
                        <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
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
                            <td>
                                <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                                    <?php if (!empty($final_url)) : ?>
                                        <a href="<?php echo esc_url($final_url); ?>" itemprop="url">
                                            <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                                        </a>
                                    <?php else : ?>
                                        <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                                    <?php endif; ?>
                                </section>
                            </td>
                        <?php endif; ?>
                        <?php
                        // Initialize arrays for unique emails and phones
                        $unique_emails = [];
                        $unique_phones = [];

                        // Add person's email and phone to the arrays as defaults
                        if (!empty($person['email'])) {
                            $unique_emails[] = $person['email'];
                        }
                        if (!empty($person['telephone'])) {
                            $unique_phones[] = $person['telephone'];
                        }

                        // Collect emails and phones from workplaces
                        if (!empty($person['contacts'])) {
                            foreach ($person['contacts'] as $contact) {
                                if (!empty($contact['workplaces'])) {
                                    foreach ($contact['workplaces'] as $workplace) {
                                        // Add unique emails from workplaces or fallback to person's email
                                        if (!empty($workplace['mails'])) {
                                            foreach ($workplace['mails'] as $email) {
                                                if (!in_array($email, $unique_emails)) {
                                                    $unique_emails[] = $email;
                                                }
                                            }
                                        } elseif (!in_array($person['email'], $unique_emails) && !empty($person['email'])) {
                                            $unique_emails[] = $person['email'];
                                        }
                                    
                                        // Add unique phones from workplaces or fallback to person's phone
                                        if (!empty($workplace['phones'])) {
                                            foreach ($workplace['phones'] as $phone) {
                                                if (!in_array($phone, $unique_phones)) {
                                                    $unique_phones[] = $phone;
                                                }
                                            }
                                        } elseif (!in_array($person['telephone'], $unique_phones) && !empty($person['telephone'])) {
                                            $unique_phones[] = $person['telephone'];
                                        }
                                    }
                                }
                            }
                        }
                        ?>

                        <!-- Render Email Column -->
                        <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)): ?>
                            <td>
                                    <?php $icon_data = get_social_icon_data('email'); ?>
                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                          style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                    <span class="screen-reader-text"><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></span>
                                <?php if (!empty($unique_emails)) : ?>
                                    <?php echo implode(', ', array_map('esc_html', $unique_emails)); ?>
                                <?php else : ?>
                                    <span><?php echo esc_html__('N/A', 'rrze-faudir'); ?></span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                            
                        <!-- Render Phone Column -->
                        <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)): ?>
                            <td>
                                    <?php $icon_data = get_social_icon_data('phone'); ?>
                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                          style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                    <span class="screen-reader-text"><?php echo esc_html__('Phones:', 'rrze-faudir'); ?></span>
                                <?php if (!empty($unique_phones)) : ?>
                                
                                    <?php echo implode(', ', array_map('esc_html', $unique_phones)); ?>
                                <?php else : ?>
                                    <span><?php echo esc_html__('N/A', 'rrze-faudir'); ?></span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <!-- Render URL Column -->
                        <?php if (in_array('url', $show_fields) && !in_array('url', $hide_fields)): ?>
                            <td>
                                <?php 
                                $icon_data = get_social_icon_data('url'); 
                                $urls_displayed = false;?>
                                <span class="<?php echo esc_attr($icon_data['css_class']); ?>" 
                                style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                <?php if (!empty($person['contacts'])) {
                                    foreach ($person['contacts'] as $contact) {
                                        if (!empty($contact['workplaces'])) {
                                            foreach ($contact['workplaces'] as $workplace) {
                                                if (!empty($workplace['url'])) {
                                                    $urls_displayed = true; ?>
                                                    <span>
                                                       
                                                        <?php echo esc_html($workplace['url']); ?><br>
                                                    </span>
                                                <?php }
                                            }
                                        }
                                    }
                                }
                                if (!$urls_displayed) { ?>
                                    <?php echo esc_html__('N/A', 'rrze-faudir'); ?>
                                <?php } ?>
                            </td>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                            <td>
                                <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                                    <ul class="social-media-list">
                                        <?php foreach ($person['contacts'][0]['socials'] as $social):
                                            $icon_data = get_social_icon_data($social['platform']);
                                        ?>
                                            <li>
                                                <a href="<?php echo esc_url($social['url']); ?>" 
                                                   class="<?php echo esc_attr($icon_data['css_class']); ?> social-icon-compact"
                                                   style="background-image: url('<?php echo esc_url($icon_data['icon_url']); ?>')"
                                                   target="_blank" 
                                                   rel="noopener noreferrer">
                                                    <span class="screen-reader-text"><?php echo esc_html(ucfirst($icon_data['name'])); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                    </tr>
                <?php else : ?>
                    <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>