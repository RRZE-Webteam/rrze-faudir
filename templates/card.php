<?php if (!empty($persons)) : ?>
    <div class="shortcode-contacts-wrapper" role="list"> <!-- Flex container for the cards -->
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
                <div class="faudir-error">
                    <?php echo esc_html($person['message']); ?>
                </div>
            <?php else: ?>
                <?php if (!empty($person)) : ?>
                    <?php
                    $featured_image_url = '';

                    // Check if a CPT with the same ID exists
                    $contact_posts = get_posts([
                        'post_type' => 'custom_person',
                        'meta_key' => 'person_id',
                        'meta_value' => $person['identifier'],
                        'posts_per_page' => 1, // Only fetch one post matching the person ID
                    ]);
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
                                    $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');
                                }
                            }
                        endforeach;
                    } ?>
                    <article class="shortcode-contact-card" itemscope itemtype="https://schema.org/Person" role="listitem">
                        <!-- Get Full name with title -->
                        <?php
                        $options = get_option('rrze_faudir_options');
                        $longVersion = "";
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
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
                        <!-- Image Section -->
                        <?php if (count($persons) === 1 && !empty($image_url)) : ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                        <?php elseif (!empty($featured_image_url)) : ?>
                            <img src="<?php echo esc_url($featured_image_url); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                        <?php else : ?>
                            <img src="<?php echo esc_url(plugins_url('rrze-faudir/assets/images/platzhalter-unisex.png', dirname(__FILE__, 2))); ?>" alt="<?php echo esc_attr($fullName . ' Image'); ?>" itemprop="image" />
                        <?php endif; ?>


                        <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
                            <section class="card-section-title" aria-label="<?php echo esc_attr($fullName); ?>">
                                <?php if (!empty($final_url)) : ?>
                                    <a href="<?php echo esc_url($final_url); ?>" itemprop="url" aria-labelledby="name-<?php echo esc_attr($person['identifier']); ?>">
                                        <span id="name-<?php echo esc_attr($person['identifier']); ?>" itemprop="name"><?php echo esc_html($fullName); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span id="name-<?php echo esc_attr($person['identifier']); ?>" itemprop="name"><?php echo esc_html($fullName); ?></span>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                        <!-- Contact details (email, phone) -->
                        <?php
                        // Initialize output strings for email and phone
                        $email_output = '';
                        $phone_output = '';


                        // Check if email should be shown and output only if an email is available
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $email = (isset($person['email']) && !empty($person['email'])
                                ? esc_html($person['email'])
                                : ''); // Custom post type email

                            // Only display the email if it's not empty
                            if (!empty($email)) {
                                echo '<p>' . esc_html__('Email:', 'rrze-faudir') . ' <a href="mailto:' . esc_url($email) . '" itemprop="email">' . esc_html($email) . '</a></p>';
                            }
                        }

                        // Check if phone should be shown and include N/A if not available
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            // Get the phone from $person array or fallback to custom post type
                            $phone = (isset($person['telephone']) && !empty($person['telephone'])
                                ? esc_html($person['telephone'])
                                : '');
                            // Only display the phone if it's not empty
                            if (!empty($phone)) {
                                echo '<p>' . esc_html__('Phone:', 'rrze-faudir') . ' <a href="tel:' . esc_html($phone) . '" itemprop="telephone">' . esc_html($phone) . '</a></p>';
                            }
                        }
                        ?>

                        <!-- Unique functions -->
                        <?php if (!empty($person['contacts'])) : ?>
                            <?php
                            $displayedFunctions = []; // Track displayed functions to avoid duplicates
                            ?>
                            <?php foreach ($person['contacts'] as $contact) : ?>
                                <?php
                                if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) {
                                    $locale = get_locale();
                                    $isGerman = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_DE_formal') !== false;

                                    // Determine the appropriate function label based on locale
                                    $function = '';
                                    if (!empty($contact['functionLabel'])) {
                                        $function = $isGerman ?
                                            ($contact['functionLabel']['de'] ?? '') : ($contact['functionLabel']['en'] ?? '');
                                    }

                                    // Check if the function has already been displayed
                                    if (!empty($function) && !in_array($function, $displayedFunctions)) {
                                        // Add the function to the displayed list to prevent duplicates
                                        $displayedFunctions[] = $function;
                                ?>
                                        <p><?php echo esc_html($function); ?></p>
                                <?php
                                    }
                                }
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                            <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                                <ul class="social-media-list">
                                    <?php foreach ($person['contacts'][0]['socials'] as $social) :
                                        $icon_data = get_social_icon_data($social['platform']);
                                    ?>
                                        <li>
                                            <a href="<?php echo esc_url($social['url']); ?>" 
                                               class="<?php echo esc_attr($icon_data['css_class']); ?> social-icon-compact"
                                               style="background-image: url('<?php echo esc_url($icon_data['icon_url']); ?>');display: inline-block; padding-left: 20px; background-size: contain; background-repeat: no-repeat;"
                                               target="_blank" 
                                               rel="noopener noreferrer">
                                                <span class="screen-reader-text"><?php echo esc_html(ucfirst($icon_data['name'])); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>

                    </article> <!-- End of shortcode-contact-card -->
                <?php else : ?>
                    <article itemscope><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?></article>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div> <!-- End of shortcode-contacts-wrapper -->
<?php else : ?>
    <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
<?php endif; ?>