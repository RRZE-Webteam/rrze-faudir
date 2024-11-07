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
                $personal_title_cpt = '';
                $first_name_cpt = '';
                $nobility_title_cpt = '';
                $last_name_cpt = '';
                $title_suffix_cpt = '';
                $email_output_cpt = '';
                $phone_output_cpt = '';
                $function_label_cpt = '';
                $organization_name_cpt = '';
                
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
                        
                            
                            // New Code to Add: Handling multiple languages (de_DE and en)
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
                        $title_suffix = (isset($person['personalTitleSuffix']) && !empty($person['personalTitleSuffix']) ? esc_html($person['personalTitleSuffix']) : '');
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
                                echo '<p>' . esc_html__('Email:', 'rrze-faudir') . ' <span itemprop="email">' . esc_html($email) . '</span></p>';
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
                                echo '<p>' . esc_html__('Phone:', 'rrze-faudir') . ' <span itemprop="phone">' . esc_html($phone) . '</span></p>';
                            }
                        }
                
                    ?>

                    <?php
                    $displayedOrganizations = []; // To track displayed organizations
                    ?>

    <?php if (!empty($person['contacts'])) : ?>
        
            <?php foreach ($person['contacts'] as $contact) : ?>
                
                <?php
                $organizationName = ''; // Default to an empty string if not found
                
                // Check if the organization is allowed to be shown
                if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) {
                    $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : $organization_name_cpt;
                }

                // Only display the organization if it is allowed
                if ($organizationName && !in_array($organizationName, $displayedOrganizations)) :
                    // Add the organization to the displayed list
                    $displayedOrganizations[] = $organizationName;
                ?>
                <ul>
                    <li itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                        <strong><?php echo esc_html__('Organization:', 'rrze-faudir'); ?></strong> 
                        <span itemprop="name"><?php echo esc_html($organizationName); ?></span><br />
                    </li>
                    </ul>
                <?php endif; // End organization check ?>

                <?php
                // Check if the function field should be shown, independent of the organization
                if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) :
                    $function = isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : $function_label_cpt;
                ?>
                <ul>
                    <li itemprop="jobTitle">
                        <strong><?php echo esc_html__('Function:', 'rrze-faudir'); ?></strong>
                        <?php echo esc_html($function); ?>
                    </li>
                    </ul>
                <?php endif; // End function check ?>
            <?php endforeach; ?>
        
    <?php endif; ?>

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
