<ul class="fau-contacts-list-custom">
    <?php if (!empty($persons)) : ?>
        <?php foreach ($persons as $person) : ?>
            <?php
             $personal_title_cpt = '';
             $first_name_cpt = '';
             $nobility_title_cpt = '';
             $last_name_cpt = '';
             $title_suffix_cpt = '';
             $email_output_cpt = '';
             $phone_output_cpt = '';
             $function_label_cpt = '';
             $organization_name_cpt = '';
             $content_en = '';
             $content = '';
             $teaser_lang = '';
             $featured_image_url = '';
             
             // Check if a CPT with the same ID exists
             $contact_posts = get_posts([
                 'post_type' => 'custom_person',
                 'meta_key' => 'person_id',
                 'meta_value' => $person['identifier'],
                 'posts_per_page' => 1, // Only fetch one post matching the person ID
             ]);
 
            // If there are contact posts, process them
        if (!empty($contact_posts)) {
                // Loop through each contact post
                foreach ($contact_posts as $post) : {
                    // Check if the post has a UnivIS ID (person_id)
                    $identifier = get_post_meta($post->ID, 'person_id', true);
                    
                    // Compare the identifier with the current person's identifier
                    if ($identifier === $person['identifier']) {
                        // Use $post->ID instead of get_the_ID() to get the correct metadata
                        $personal_title_cpt = get_post_meta($post->ID, 'person_title', true);
                        $first_name_cpt = get_post_meta($post->ID, 'person_given_name', true);
                        $nobility_title_cpt = get_post_meta($post->ID, 'person_nobility_name', true);
                        $last_name_cpt = get_post_meta($post->ID, 'person_family_name', true);
                        $title_suffix_cpt = get_post_meta($post->ID, 'person_suffix', true);
                        $email_output_cpt = get_post_meta($post->ID, 'person_email', true);
                        $phone_output_cpt = get_post_meta($post->ID, 'person_telephone', true);
                        $function_label_cpt = get_post_meta($post->ID, 'person_function', true);
                        $organization_name_cpt = get_post_meta($post->ID, 'person_organization', true);
                        $content_en = get_post_meta($post->ID, '_content_en', true);
                        $content = apply_filters('the_content', $post->post_content);
                        $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');
        
                        // Log the relevant information for debugging
                        error_log($content_en);
                        error_log('Nob' . $nobility_title_cpt);
                        error_log('Suffix: ' . $title_suffix_cpt);
                        error_log('Post content: ' . $content);
                        
                        // New Code to Add: Handling multiple languages (de_DE and en)
                        $locale = get_locale(); // Get the current locale
                        $content_en = get_post_meta($post->ID, '_content_en', true); // English content from post meta
                        $content_de = apply_filters('the_content', $post->post_content); // Default post content (assumed to be in German)

                        // Ensure $content_en is set
                        $content_en = isset($content_en) ? $content_en : '';
                        $teaser_text_key = ($locale === 'de_DE') ? '_teasertext_de' : '_teasertext_en';
                        $teaser_lang = get_post_meta($post->ID, $teaser_text_key, true);    
                    }
                }
            endforeach;
        }?>
          
            <li itemscope itemtype="https://schema.org/Person">
                <!-- Full name with title -->
                <?php
                $options = get_option('rrze_faudir_options');
                $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];

                $longVersion = '';
                if($hard_sanitize){
                    $prefix = $person['personalTitle'] ?? '';
                    $prefixes = array(
                        '' => __('Keine Angabe', 'rrze-faudir'),
                        'Dr.' => __('Doktor', 'rrze-faudir'),
                        'Prof.' => __('Professor', 'rrze-faudir'),
                        'Prof. Dr.' => __('Professor Doktor', 'rrze-faudir'),
                        'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
                        'Prof. Dr. em.' => __('Professor Doktor (Emeritus)', 'rrze-faudir'),
                        'PD' => __('Privatdozent', 'rrze-faudir'),
                        'PD Dr.' => __('Privatdozent Doktor', 'rrze-faudir')
                    );
                    // Check if the prefix exists in the array and display the long version
                    $longVersion = isset($prefixes[$prefix]) ? $prefixes[$prefix] : __('Unbekannt', 'rrze-faudir');
                }
                $personal_title = "";
                $first_name= "";
                $nobility_title= "";
                $last_name ="";
                $title_suffix ="";
                if (in_array('personalTitle', $show_fields) && !in_array('personalTitle', $hide_fields)) {
                    $personal_title = (isset($person['personalTitle']) && !empty($person['personalTitle']) ? esc_html($person['personalTitle']) : '');
                }
                if (in_array('firstName', $show_fields) && !in_array('firstName', $hide_fields)) {
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
                $fullName = trim(($longVersion ? $longVersion : ($personal_title ? $personal_title : $personal_title_cpt)) . ' ' 
                . ($first_name ? $first_name : $first_name_cpt) . ' ' 
                .($nobility_title ? $nobility_title : $nobility_title_cpt)  . ' ' 
                . ($last_name ? $last_name : $last_name_cpt) . ' ' 
                . ($title_suffix ? $title_suffix : $title_suffix_cpt));
                ?>

                <!-- We need to add condition for url when we add CPT -->
                <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                    <?php if (!empty($url)) : ?>
                        <a href="<?php echo esc_url($url); ?>" itemprop="url">
                            <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                        </a>
                    <?php else : ?>
                        <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                    <?php endif; ?>
                </section>

                <?php
                // Initialize output strings for email and phone
                $email_output = '';
                $phone_output = '';
                        
               
                       // Check if email should be shown and output only if an email is available
                       if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                        // Get the email from $person array or fallback to custom post type
                        $email = (isset($person['email']) && !empty($person['email'])) 
                            ? esc_html($person['email']) 
                            : esc_html($email_output_cpt); // Custom post type email

                        // Only display the email if it's not empty
                        if (!empty($email)) {
                            echo '<p>' . __('Email:', 'rrze-faudir') . ' <span itemprop="email">' . $email . '</span></p>';
                        }
                    }
                    // Check if phone should be shown and include N/A if not available
                    if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                        // Get the email from $person array or fallback to custom post type
                        $phone = (isset($person['telephone']) && !empty($person['telephone']) 
                        ? esc_html($person['telephone']) 
                        : esc_html($phone_output_cpt));
                        // Only display the email if it's not empty
                        if (!empty($phone)) {
                            echo '<p>' . __('Phone:', 'rrze-faudir') . ' <span itemprop="phone">' . $phone . '</span></p>';
                        }
                    }
            
                ?>

                <?php
                $displayedOrganizations = []; // To track displayed organizations
                ?>

                <?php if (!empty($person['contacts'])) : ?>
                    <ul>
                        <?php foreach ($person['contacts'] as $contact) : ?>
                            <?php
                            // Check if the organization has already been displayed
                            if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) {
                                $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : $organization_name_cpt;
                            }
                            if ($organizationName && !in_array($organizationName, $displayedOrganizations)) :
                                // Add the organization to the displayed list
                                $displayedOrganizations[] = $organizationName;
                            ?>
                                <li itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                                    <strong><?php echo __('Organization:', 'rrze-faudir');?></strong> 
                                    <span itemprop="name"><?php echo esc_html($organizationName); ?></span><br />
                                    
                                    <?php 
                                    if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) {
                                    $function = isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : $function_label_cpt; ?>
                                        <!-- Show functions associated with this organization -->
                                        <strong><?php echo __('Functions:', 'rrze-faudir');?></strong>

                                    <?php  } ?>
                                    <ul>
                                        <?php foreach ($person['contacts'] as $sameOrgContact) : ?>
                                            <?php if (isset($sameOrgContact['organization']['name']) && $sameOrgContact['organization']['name'] === $organizationName) : ?>
                                                <li itemprop="jobTitle">
                                                    <?php echo esc_html($sameOrgContact['functionLabel']['en']); ?>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    <?php else : ?>
    <div><?php echo __('Es konnte kein Kontakteintrag gefunden werden.', 'rrze-faudir') ?> </div>
    <?php endif; ?>
</ul>
