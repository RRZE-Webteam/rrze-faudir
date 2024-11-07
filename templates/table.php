<table class="fau-contacts-table-custom">
    <thead>
        <tr>
            <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
                <th><?php  echo esc_html__('Name', 'rrze-faudir') ?></th>
            <?php endif; ?>
            <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) : ?>
                <th><?php  echo esc_html__('Email', 'rrze-faudir') ?></th>
            <?php endif; ?>
            <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) : ?>
                <th><?php  echo esc_html__('Phone', 'rrze-faudir') ?></th>
            <?php endif; ?>
            <?php if ((in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) || 
                          (in_array('function', $show_fields) && !in_array('function', $hide_fields))) : ?>
                    <th><?php echo esc_html__('Organization / Function', 'rrze-faudir') ?></th>
            <?php endif; ?>
            <?php if (in_array('url', $show_fields) && !in_array('url', $hide_fields)): ?>
                    <th><?php  echo esc_html__('Url', 'rrze-faudir') ?></th>
            <?php endif; ?>
        </tr>
    </thead>
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
                                    echo '<td> <span itemprop="email">' . esc_html($email) . '</span></td>';
                                }
                                else{
                                    echo '<td> N/A </td>';
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
                                    echo '<td><span itemprop="phone">' . esc_html($phone) . '</span></td>';
                                }
                                else{
                                    echo '<td> N/A </td>';
                                }
                            }?>

                <!-- Organization / Function (Grouped and Displayed) -->
                <?php if ((in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) || 
                        (in_array('function', $show_fields) && !in_array('function', $hide_fields))) : ?>
                    <td>
                        <?php
                        // Group functions by organization
                        $organizationFunctions = [];

                        // Group functions by organization name
                        foreach ($person['contacts'] as $contact) {
                            $organizationName = $contact['organization']['name'] ?? null;
                            if ($organizationName) {
                                $locale = get_locale();
                                $isGerman = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_DE_formal') !== false;
                            
                                // Determine function label
                                $functionLabel = '';
                                if (!empty($contact['functionLabel'])) {
                                    $functionLabel = $isGerman ? 
                                        ($contact['functionLabel']['de'] ?? '') : 
                                        ($contact['functionLabel']['en'] ?? '');
                                }
                            
                                // Add function label to the organization group
                                if (!empty($functionLabel)) {
                                    $organizationFunctions[$organizationName][] = $functionLabel;
                                }
                            }
                        }
                    
                        // Display each organization and its functions if found
                        if (!empty($organizationFunctions)) {
                            foreach ($organizationFunctions as $orgName => $functions) {
                            if(in_array('organization', $show_fields) && !in_array('organization', $hide_fields)){?>
                                <div itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                                    <strong itemprop="name"><?php echo esc_html($orgName); ?></strong>
                                </div>
                                <?php } 
                            if(in_array('function', $show_fields) && !in_array('function', $hide_fields)){ ?>
                                <ul>
                                    <?php foreach ($functions as $function) : ?>
                                        <li itemprop="jobTitle"><?php echo esc_html($function); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php } ?>

                                <?php
                            }
                        }
                        ?>
                    </td>
                <?php endif; ?>
            <?php if (in_array('url', $show_fields) && !in_array('url', $hide_fields)): ?>
                <td>
                    
                <?php  foreach ($person['contacts'] as $contact) {
                foreach ($contact['workplaces'] as $workplace) : ?>
                        <p>
                            <?php if (!empty($workplace['url'])) : ?>
                                <strong><?php echo esc_html__('Street:', 'rrze-faudir'); ?></strong>
                                <?php echo esc_html($workplace['url']); ?><br>
                                <?php else: 
                                
                                    echo ' N/A'; ?>
                                
                            <?php endif; ?>
                            
                        </p>
                    <?php endforeach;}  ?>
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
