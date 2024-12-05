<?php

if (!empty($persons)) : ?>
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
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                        $personal_title = '';
                        $first_name = '';
                        $nobility_title = '';
                        $last_name = '';
                        $title_suffix = '';
                        if (in_array('personalTitle', $show_fields) && !in_array('personalTitle', $hide_fields)) {
                            $personal_title = isset($person['personalTitle']) && !empty($person['personalTitle']) 
                                ? esc_html($person['personalTitle'])
                                : '';
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
                            $title_suffix = (isset($person['personalTitleSuffix']) && !empty($person['personalTitleSuffix']) ? esc_html($person['personalTitleSuffix']) : '');
                        }
                        // Construct the full name
                        $fullName = trim(
                            ($personal_title) . ' ' .
                                ($first_name) . ' ' .
                                ($nobility_title) . ' ' .
                                ($last_name) . ' ' .
                                '(' . ($title_suffix) . ')'
                        );

                        // Image
                        if (count($persons) === 1 && !empty($image_url)) {
                            ?>
                            <div itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                                <meta itemprop="identifier" content="<?php echo esc_attr($person['identifier']); ?>_image" />
                                <?php
                                $image_id = attachment_url_to_postid($image_url);
                                $image_meta = wp_get_attachment_metadata($image_id);
                                $width = isset($image_meta['width']) ? $image_meta['width'] : '';
                                $height = isset($image_meta['height']) ? $image_meta['height'] : '';
                                $caption = wp_get_attachment_caption($image_id);
                                ?>
                                <img src="<?php echo esc_url($image_url); ?>" 
                                     alt="<?php echo esc_attr($fullName . ' Image'); ?>" 
                                     itemprop="contentUrl" />
                                <?php if ($width): ?><meta itemprop="width" content="<?php echo esc_attr($width); ?>" /><?php endif; ?>
                                <?php if ($height): ?><meta itemprop="height" content="<?php echo esc_attr($height); ?>" /><?php endif; ?>
                                <?php if ($caption): ?>
                                    <meta itemprop="caption" content="<?php echo esc_attr($caption); ?>" />
                                <?php endif; ?>
                            </div>
                            <?php
                        } elseif (!empty($featured_image_url)) {
                            ?>
                            <div itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                                <meta itemprop="identifier" content="<?php echo esc_attr($person['identifier']); ?>_featured_image" />
                                <?php
                                $image_id = attachment_url_to_postid($featured_image_url);
                                $image_meta = wp_get_attachment_metadata($image_id);
                                $width = isset($image_meta['width']) ? $image_meta['width'] : '';
                                $height = isset($image_meta['height']) ? $image_meta['height'] : '';
                                $caption = wp_get_attachment_caption($image_id);
                                ?>
                                <img src="<?php echo esc_url($featured_image_url); ?>" 
                                     alt="<?php echo esc_attr($fullName . ' Image'); ?>" 
                                     itemprop="contentUrl" />
                                <?php if ($width): ?><meta itemprop="width" content="<?php echo esc_attr($width); ?>" /><?php endif; ?>
                                <?php if ($height): ?><meta itemprop="height" content="<?php echo esc_attr($height); ?>" /><?php endif; ?>
                                <?php if ($caption): ?>
                                    <meta itemprop="caption" content="<?php echo esc_attr($caption); ?>" />
                                <?php endif; ?>
                            </div>
                            <?php
                        } else {
                            echo '<img src="' . esc_url(plugins_url('rrze-faudir/assets/images/platzhalter-unisex.png', dirname(__FILE__, 2))) . '" alt="' . esc_attr($fullName . ' Image') . '" itemprop="image" />';
                        }

                        // Name
                        if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) {
                            echo '<section class="card-section-title" aria-label="' . esc_attr($fullName) . '">';
                            if (!empty($final_url)) {
                                echo '<a href="' . esc_url($final_url) . '" aria-labelledby="name-' . esc_attr($person['identifier']) . '">';
                                echo FaudirUtils::getPersonNameHtml([
                                    'hard_sanitize' => $hard_sanitize,
                                    'personal_title' => $personal_title,
                                    'first_name' => $first_name,
                                    'nobility_title' => $nobility_title,
                                    'last_name' => $last_name,
                                    'title_suffix' => $title_suffix,
                                    'identifier' => $person['identifier']
                                ]);
                                echo '</a>';
                            } else {
                                echo FaudirUtils::getPersonNameHtml([
                                    'hard_sanitize' => $hard_sanitize,
                                    'personal_title' => $personal_title,
                                    'first_name' => $first_name,
                                    'nobility_title' => $nobility_title,
                                    'last_name' => $last_name,
                                    'title_suffix' => $title_suffix,
                                    'identifier' => $person['identifier']
                                ]);
                            }
                            echo '</section>';
                        }
                        ?>

                        <!-- Function -->
                        <?php if (!empty($person['contacts'])) : ?>
                            <?php
                            $displayedFunctions = []; // Track displayed functions to avoid duplicates
                            
                            $displayed_contacts = get_post_meta($post->ID, 'displayed_contacts', true) ?: []; // Retrieve displayed contact indexes
                            ?>

                            <?php foreach ($person['contacts'] as $index => $contact)  : ?>
                                <?php
                                // Check if the current contact index is in $displayed_contacts
                                if (!in_array($index, $displayed_contacts) && !empty($displayed_contacts)) {
                                    continue; // Skip this contact if it's not selected to be displayed
                                }
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
                                        <p itemprop="jobTitle"><?php echo esc_html($function); ?></p>
                                <?php
                                    }
                                }
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Social Media -->
                        <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                            <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                                <ul class="socialmedia">
                                    <?php foreach ($person['contacts'][0]['socials'] as $social) :
                                        $icon_data = get_social_icon_data($social['platform']);
                                    ?>
                                        <li>
                                            <span class="screen-reader-text"> <?php echo esc_html(ucfirst($icon_data['name'])); ?>: </span>
                                            <a href="<?php echo esc_url($social['url']); ?>"><?php echo esc_url($social['url']); ?></a>
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