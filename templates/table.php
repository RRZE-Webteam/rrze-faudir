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
                        $final_url = (count($persons) > 1 || empty($url)) ? $cpt_url : $url;?>
                <tr itemscope itemtype="https://schema.org/Person">
                    <!-- Full Name -->
                    <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
                        <?php
                            $options = get_option('rrze_faudir_options');
                            $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                            $longVersion = "";
                            if($hard_sanitize){
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
                            $first_name= "";
                            $nobility_title= "";
                            $last_name ="";
                            $title_suffix ="";
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

                    
                    <!-- Email (if available) -->
                    <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                                // Get the email from $person array or fallback to custom post type
                                $email = (isset($person['email']) && !empty($person['email'])) 
                                    ? esc_html($person['email']) 
                                    : ''; 

                                // Only display the email if it's not empty
                                if (!empty($email)) {
                                    echo '<td> <span itemprop="email"><i class="fa-regular fa-envelope"></i> ' . esc_html($email) . '</span></td>';
                                }
                                else{
                                    echo '<td> <i class="fa-regular fa-envelope"></i> N/A </td>';
                                }
                            }

                    // Phone (if available)
                    if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                                // Get the email from $person array or fallback to custom post type
                                $phone = (isset($person['telephone']) && !empty($person['telephone']) 
                                ? esc_html($person['telephone']) 
                                : '');
                                // Only display the email if it's not empty
                                if (!empty($phone)) {
                                    echo '<td><span itemprop="phone"><i class="fa-solid fa-phone"></i>' . esc_html($phone) . '</span></td>';
                                }
                                else{
                                    echo '<td><i class="fa-solid fa-phone"></i> N/A </td>';
                                }
                            }?>

                    <?php if (in_array('url', $show_fields) && !in_array('url', $hide_fields)): ?>
                        <td>

                        <?php  foreach ($person['contacts'] as $contact) {
                        foreach ($contact['workplaces'] as $workplace) : ?>
                                <p>
                                    <?php if (!empty($workplace['url'])) : ?>
                                        <i class="fa-solid fa-globe"></i>
                                        <?php echo esc_html($workplace['url']); ?><br>
                                        <?php else: 

                                            echo '<td><i class="fa-solid fa-globe"></i> N/A</td>'; ?>

                                    <?php endif; ?>
                                        
                                </p>
                            <?php endforeach;}  ?>
                        </td>
                    <?php endif; ?>
                    <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                        <td>
                            <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                                <div>
                                    <ul class="social-media-list">
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
                                            'bsky' => 'fas fa-cloud',
                                        ];
                                    
                                        foreach ($person['contacts'][0]['socials'] as $social) : 
                                            $platform = strtolower($social['platform']);
                                            $url = $social['url'];
                                            $iconClass = isset($iconMap[$platform]) ? $iconMap[$platform] : 'fas fa-link'; // Default to link icon if not found
                                        ?>
                                            <li style="margin-bottom: 8px;">
                                                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
                                                <i class="<?php echo esc_attr($iconClass); ?>" 
                                                title="<?php echo esc_attr(ucfirst($platform)); ?>" 
                                                style="margin-right: 8px;"></i>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
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
