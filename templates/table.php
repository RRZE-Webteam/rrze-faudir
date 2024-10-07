<table class="fau-contacts-table-custom">
    <thead>
        <tr>
            <?php if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) : ?>
                <th>Name</th>
            <?php endif; ?>
            <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) : ?>
                <th>Email</th>
            <?php endif; ?>
            <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) : ?>
                <th>Phone</th>
            <?php endif; ?>
            <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) : ?>
                <th>Organization</th>
            <?php endif; ?>
            <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) : ?>
                <th>Function</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
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
                       
                    }
                }
            endforeach;
        }?>
            <tr itemscope itemtype="https://schema.org/Person">
                <!-- Full Name -->
                <?php if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) : ?>
                    <?php
                        $options = get_option('rrze_faudir_options');
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                        $longVersion = "";
                        if($hard_sanitize){
                           $prefix = $person['personalTitle'];
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
                    <td>
                    <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                    <?php if (!empty($url)) : ?>
                        <a href="<?php echo esc_url($url); ?>" itemprop="url">
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
                                : esc_html($email_output_cpt); // Custom post type email

                            // Only display the email if it's not empty
                            if (!empty($email)) {
                                echo '<td> <span itemprop="email">' . $email . '</span></td>';
                            }
                            else{
                                echo '<td></td>';
                            }
                        }

                // Phone (if available)
                if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $phone = (isset($person['telephone']) && !empty($person['telephone']) 
                            ? esc_html($person['telephone']) 
                            : esc_html($phone_output_cpt));
                            // Only display the email if it's not empty
                            if (!empty($phone)) {
                                echo '<td><span itemprop="phone">' . $phone . '</span></td>';
                            }
                            else{
                                echo '<td></td>';
                            }
                        }?>

                <!-- Organizations and Functions -->
                <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) : ?>
                    <td>
                        <?php
                        $displayedOrganizations = [];
                        foreach ($person['contacts'] as $contact) :
                            if (isset($contact['organization']['name']) && !in_array($contact['organization']['name'], $displayedOrganizations)) :
                                $displayedOrganizations[] = $contact['organization']['name'];
                        ?>
                                <div itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                                    <span itemprop="name"><?php echo esc_html($contact['organization']['name']); ?></span>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        if (empty($displayedOrganizations)) :
                            ?>
                                    <div>
                                        <span><?php echo esc_html($organization_name_cpt) ?></span>
                                    </div>
                            <?php
                                endif;
                            ?>
                    </td>
                <?php endif; ?>

                <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) : ?>
                    <td>
                        <ul>
                            <?php foreach ($person['contacts'] as $contact) : ?>
                                <?php if (isset($contact['functionLabel']['en'])) : ?>
                                    <li itemprop="jobTitle"><?php echo esc_html($contact['functionLabel']['en']); ?></li>
                                <?php else: ?>
                                <li itemprop="jobTitle"><?php echo esc_html($function_label_cpt); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
