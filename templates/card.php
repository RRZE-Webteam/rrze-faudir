<?php

function rrze_faudir_get_person_name_html($person_data)
{
    error_log(print_r($person_data, true));
    $title_prefix = $person_data['title_prefix'] ?? '';
    $first_name = $person_data['first_name'] ?? '';
    $nobility_title = $person_data['nobility_title'] ?? '';
    $last_name = $person_data['last_name'] ?? '';
    $title_suffix = $person_data['title_suffix'] ?? '';
    $identifier = $person_data['identifier'] ?? '';

    $nameHtml = '';
    $nameHtml .= '<span id="name-' . esc_attr($identifier) . '" itemprop="name">';
    if (!empty($title_prefix)) {
        $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($title_prefix) . '</span>';
    }
    if (!empty($first_name)) {
        $nameHtml .= '<span itemprop="givenName">' . esc_html($first_name) . '</span>';
    }
    if (!empty($nobility_title)) {
        $nameHtml .= '<span>' . esc_html($nobility_title) . '</span>';
    }
    if (!empty($last_name)) {
        $nameHtml .= '<span itemprop="familyName">' . esc_html($last_name) . '</span>';
    }
    if (!empty($title_suffix)) {
        $nameHtml .= '<span itemprop="honorificSuffix">' . esc_html($title_suffix) . '</span>';
    }
    $nameHtml .= '</span>';
    return $nameHtml;
}

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

                        $title_prefix = $longVersion ? $longVersion : $personal_title;

                        // Construct the full name
                        $fullName = trim(
                            ($title_prefix) . ' ' .
                                ($first_name) . ' ' .
                                ($nobility_title) . ' ' .
                                ($last_name) . ' ' .
                                ($title_suffix)
                        );

                        // Image
                        if (count($persons) === 1 && !empty($image_url)) {
                            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($fullName . ' Image') . '" itemprop="image" />';
                        } elseif (!empty($featured_image_url)) {
                            if (!empty($final_url)) {
                                echo '<a href="' . esc_url($final_url) . '" itemprop="url" aria-labelledby="name-' . esc_attr($person['identifier']) . '">';
                                echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($fullName . ' Image') . '" itemprop="image" />';
                                echo '</a>';
                            } else {
                                echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($fullName . ' Image') . '" itemprop="image" />';
                            }
                        } else {
                            echo '<img src="' . esc_url(plugins_url('rrze-faudir/assets/images/platzhalter-unisex.png', dirname(__FILE__, 2))) . '" alt="' . esc_attr($fullName . ' Image') . '" itemprop="image" />';
                        }

                        // Name
                        if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) {
                            echo '<section class="card-section-title" aria-label="' . esc_attr($fullName) . '">';
                            if (!empty($final_url)) {
                                echo '<a href="' . esc_url($final_url) . '" aria-labelledby="name-' . esc_attr($person['identifier']) . '">';
                                echo rrze_faudir_get_person_name_html([
                                    'title_prefix' => $title_prefix,
                                    'first_name' => $first_name,
                                    'nobility_title' => $nobility_title,
                                    'last_name' => $last_name,
                                    'title_suffix' => $title_suffix,
                                    'identifier' => $person['identifier']
                                ]);
                                echo '</a>';
                            } else {
                                echo rrze_faudir_get_person_name_html([
                                    'title_prefix' => $title_prefix,
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

                        <!-- Social Media -->
                        <?php if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)): ?>
                            <?php if (!empty($person['contacts'][0]['socials'])) : ?>
                                <ul class="social-media-list">
                                    <?php foreach ($person['contacts'][0]['socials'] as $social) :
                                        $icon_data = get_social_icon_data($social['platform']);
                                    ?>
                                        <li>
                                            <a href="<?php echo esc_url($social['url']); ?>"
                                                class="<?php echo esc_attr($icon_data['css_class']); ?> social-icon-compact"
                                                style="background-image: url('<?php echo esc_url($icon_data['icon_url']); ?>');"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                itemprop="sameAs">
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