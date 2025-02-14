<?php
// Template file for RRZE FAUDIR

use RRZE\FAUdir\Debug;
use RRZE\FAUdir\FAUdirUtils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="faudir">
    <table class="format-table">
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
                        $final_url = (count($persons) > 1 || empty($url)) ? $cpt_url : $url; 
                        
                        
                        
                   echo Debug::get_html_var_dump($person);
                        
                        ?>
                        <tr itemscope itemtype="https://schema.org/Person">
                            <!-- Full Name -->
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

                            $person_name_html = FaudirUtils::getPersonNameHtml([
                                'hard_sanitize' => $hard_sanitize,
                                'personal_title' => $personal_title,
                                'first_name' => $first_name,
                                'nobility_title' => $nobility_title,
                                'last_name' => $last_name,
                                'title_suffix' => $title_suffix,
                                'identifier' => $person['identifier']
                            ]);
                            ?>
                            <?php if (!empty($person_name_html)): ?>
                                <td>
                                    <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                                        <?php if (!empty($final_url)) : ?>
                                            <a href="<?php echo esc_url($final_url); ?>" itemprop="url">
                                                <?php echo $person_name_html; ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $person_name_html; ?>
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
                                $displayed_contacts = isset($post) ? get_post_meta($post->ID, 'displayed_contacts', true) : [];
                                $displayed_contacts = !empty($displayed_contacts) ? $displayed_contacts : [];

                                foreach ($person['contacts'] as $index => $contact) { // Use index to match against $displayed_contacts
                                    // Check if the current contact index is in $displayed_contacts
                                    if (!in_array($index, $displayed_contacts) && !empty($displayed_contacts)) {
                                        continue; // Skip this contact if it's not selected to be displayed
                                    }
                                    if (!empty($contact['workplaces'])) {
                                        foreach ($contact['workplaces'] as $workplace) {
                                            // Add unique emails from workplaces or fallback to person's email
                                            if (!empty($workplace['mails'])) {
                                                foreach ($workplace['mails'] as $email) {
                                                    if (!in_array($email, $unique_emails)) {
                                                        $unique_emails[] = $email;
                                                    }
                                                }
                                            } elseif (isset($person['email']) && !in_array($person['email'], $unique_emails) && !empty($person['email'])) {
                                                $unique_emails[] = $person['email'];
                                            }

                                            // Add unique phones from workplaces or fallback to person's phone
                                            if (!empty($workplace['phones'])) {
                                                foreach ($workplace['phones'] as $phone) {
                                                    if (!in_array($phone, $unique_phones)) {
                                                        $unique_phones[] = $phone;
                                                    }
                                                }
                                            } elseif (isset($person['telephone']) && !in_array($person['telephone'], $unique_phones) && !empty($person['telephone'])) {
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
                                    <?php $icon_data = []; ?>
                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>"
                                        style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                    <span class="screen-reader-text"><?php echo esc_html__('Emails:', 'rrze-faudir'); ?></span>
                                    <?php if (!empty($unique_emails)) : ?>
                                        <?php echo implode(', ', array_map(function ($email) {
                                            return '<span itemprop="email">' . esc_html($email) . '</span>';
                                        }, $unique_emails)); ?>
                                    <?php else : ?>
                                        <span><?php echo esc_html__('N/A', 'rrze-faudir'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <!-- Render Phone Column -->
                            <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)): ?>
                                <td>
                                    <?php $icon_data = [] ?>
                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>"
                                        style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                    <span class="screen-reader-text"><?php echo esc_html__('Phones:', 'rrze-faudir'); ?></span>
                                    <?php if (!empty($unique_phones)) : ?>
                                        <?php echo implode(', ', array_map(function ($phone) {
                                            return '<span itemprop="telephone">' . esc_html($phone) . '</span>';
                                        }, $unique_phones)); ?>
                                    <?php else : ?>
                                        <span><?php echo esc_html__('N/A', 'rrze-faudir'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <!-- Render URL Column -->
                            <?php if (in_array('url', $show_fields) && !in_array('url', $hide_fields)): ?>
                                <td>
                                    <?php
                                    $icon_data = [];
                                    $urls_displayed = false; ?>
                                    <span class="<?php echo esc_attr($icon_data['css_class']); ?>"
                                        style="background-image: url('<?php echo esc_url($icon_data['icon_address']); ?>')"></span>
                                    <?php if (!empty($person['contacts'])) {
                                        foreach ($person['contacts'] as $contact) {
                                            if (!empty($contact['workplaces'])) {
                                                foreach ($contact['workplaces'] as $workplace) {
                                                    if (!empty($workplace['url'])) {
                                                        $urls_displayed = true; ?>
                                                        <span>
                                                            <a href="<?php echo esc_url($workplace['url']); ?>"
                                                                rel="noopener noreferrer"
                                                                itemprop="url">
                                                                <?php echo esc_html($workplace['url']); ?>
                                                            </a>
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
                                        <ul class="socialmedia">
                                            <?php foreach ($person['contacts'][0]['socials'] as $social):
                                                $icon_data = []
                                            ?>
                                                <li>
                                                    <span class="screen-reader-text"><?php echo esc_html(ucfirst($icon_data['name'])); ?>: </span>
                                                    <a href="<?php echo esc_url($social['url']); ?>" itemprop="sameAs"><?php echo esc_url($social['url']); ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php else : ?>
                        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>