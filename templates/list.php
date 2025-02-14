<?php
// Template file for RRZE FAUDIR

use RRZE\FAUdir\Debug;
use RRZE\FAUdir\FAUdirUtils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="faudir">
    <?php if (!empty($persons)) : ?>
         <ul class="format-list">
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
                <li class="faudir-error"><?php echo esc_html($person['message']); ?> </li>             
            <?php else: ?>
                <?php if (!empty($person)) : ?>
                    <?php
                    $teaser_lang = '';
                    $locale = get_locale();
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

                        $person_name_html = FaudirUtils::getPersonNameHtml([
                            'hard_sanitize' => $hard_sanitize,
                            'personal_title' => $personal_title,
                            'first_name' => $first_name,
                            'nobility_title' => $nobility_title,
                            'last_name' => $last_name,
                            'title_suffix' => $title_suffix,
                            'identifier' => $person['identifier']
                        ]);

                        if (!empty($person_name_html)) { ?>
                            <span class="displayname"><?php if (!empty($final_url)) { ?>
                                    <a href="<?php echo esc_url($final_url); ?>"><?php echo $person_name_html; ?></a>
                                <?php } else { 
                                    echo $person_name_html;
                                } ?>
                            </span>
                        <?php } 
                        // Initialize arrays for unique emails, phones, and a flag for URLs
                        $unique_emails = [];
                        $unique_phones = [];
                        $url_displayed = false;

                        $output = '';
                        $workplaces = [];
                        $socials = [];
                        $org = '';
                        $function = '';
                       
                                    
                                    
                        // Collect emails and phones from workplaces, falling back to person email/phone if necessary
                        if (!empty($person['contacts'])) {
                            $displayed_contacts = get_post_meta($post->ID, 'displayed_contacts', true) ?: []; // Retrieve displayed contact indexes
                            foreach ($person['contacts'] as $index => $contact) { // Use index to match against $displayed_contacts
                                // Check if the current contact index is in $displayed_contacts
                                if (!in_array($index, $displayed_contacts) && !empty($displayed_contacts)) {
                                    continue; // Skip this contact if it's not selected to be displayed
                                }
                                $workplaces = $contact['workplaces'];
                                $socials = $contact['socials'];
                                
                                if (!empty($contact['socials'])) {
                                    $socials = [];

                                    foreach ($contact['socials'] as $item) {
                                        if (isset($item['platform']) && isset($item['url'])) {
                                            $socials[$item['platform']] = $item['url'];
                                        }
                                    }
                                }
   
                                
                                if (!empty($contact['workplaces'])) {
                                    foreach ($contact['workplaces'] as $workplace) {
                                        // Handle emails
                                        if (!empty($workplace['mails'])) {
                                            foreach ($workplace['mails'] as $email) {
                                                if (!in_array($email, $unique_emails)) {
                                                    $unique_emails[] = $email;
                                                }
                                            }
                                        }
                                    
                                        // Handle phones
                                        if (!empty($workplace['phones'])) {
                                            foreach ($workplace['phones'] as $phone) {
                                                if (!in_array($phone, $unique_phones)) {
                                                    $unique_phones[] = $phone;
                                                }
                                            }
                                        }
                                    
                                        // Check if a URL exists
                                        if (!empty($workplace['url'])) {
                                            $url_displayed = true;
                                        }
                                    }
                                }
                            }
                            if (empty($unique_emails) && !empty($person['email'])) {
                                $workplaces['mails'][] = $person['email'];
                            }
                        
                            if (empty($unique_phones) && !empty($person['telephone'])) {
                                $workplaces['phones'][] = $person['telephone'];
                            }
                        }
                        if (!empty($function)) {
                            $workplaces['function'] = $function;
                        }
             //         $output .= Debug::get_html_var_dump($workplaces);
             //           $output .= Debug::get_html_var_dump($show_fields);
             //           $output .= Debug::get_html_var_dump($hide_fields);

                        $reihenfolge = ['function', 'url', 'mails', 'phones', 'street', 'zip', 'city', 'roompos', 'room', 'floor', 'address','faumap'];
                        // Output Workplace Data
                        $listdata = FaudirUtils::getListOutput($workplaces,'span',__('Contactpoints', 'rrze-faudir'),'text-list icon',$show_fields,$hide_fields,$reihenfolge);

                        $socialoutput = '';
                        if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)) {                            
                            if (!empty($socials)) {        
                                $socialoutput = FaudirUtils::getListOutput($socials,'span',__('Portale', 'rrze-faudir'),'icon-list icon');
                            }

                        }
                        if (!empty($listdata)) {
                             $output .= ', '.$listdata;
                        }
                        if (!empty($socialoutput)) {
                             $output .= ' '.$socialoutput;
                        }
                        
                        
                        // Output optional teasertext
                        if (in_array('teasertext', $show_fields) && !in_array('teasertext', $hide_fields)) {
                            if (!empty($teaser_lang)) {  
                               $output .= ' <span class="teasertext">'.wp_kses_post($teaser_lang).'</span>';
                            }
                        } 
                        echo $output;
                        ?>
                        
                    </li>
                <?php else : ?>
                    <li class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
    <?php endif; ?>

</div>