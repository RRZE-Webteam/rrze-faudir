<ul class="fau-contacts-list-custom">
    <?php if (!empty($persons)) : ?>
        <?php foreach ($persons as $person) : ?>
            <li>
                <!-- Full name with title -->
                <?php
                 $options = get_option('rrze_faudir_options');
                 $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
             
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
                $fullName = trim(($longVersion ? $longVersion : $person['personalTitle'] ). ' ' . $person['givenName'] . ' ' . $person['familyName']);
                ?>
                <!-- We need to add condition for url when we add CPT -->
                <section class="list-section-title" aria-label="<?php echo esc_html($fullName); ?>"><a href="<?php echo esc_html($url); ?>"><?php echo esc_html($fullName); ?></a></section>

                <?php
                // Initialize output strings for email and phone
                $email_output = '';
                $phone_output = '';
                        
                // Check if email should be shown and include N/A if it is not available
                if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                    $email_output = __('Email:', 'rrze-faudir') . (isset($person['email']) && !empty($person['email']) ? esc_html($person['email']) : 'N/A');
                }
                
                // Check if phone should be shown and include N/A if it is not available
                if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                    $phone_output =  __('Phone:', 'rrze-faudir') . (isset($person['telephone']) && !empty($person['telephone']) ? esc_html($person['telephone']) : 'N/A');
                }
                
                // Build the final output based on what's available
                if (!empty($email_output) && !empty($phone_output)) {
                    // If both email and phone should be shown
                    echo '(' . $email_output . ', ' . $phone_output . ')';
                } elseif (!empty($email_output)) {
                    // If only email should be shown
                    echo '(' . $email_output . ')';
                } elseif (!empty($phone_output)) {
                    // If only phone should be shown
                    echo '(' . $phone_output . ')';
                }
                ?>

            
                <!-- Array to track displayed organizations -->
                <?php
                $displayedOrganizations = []; // To track displayed organizations
                ?>

                <?php if (!empty($person['contacts'])) : ?>
                    <ul>
                        <?php foreach ($person['contacts'] as $contact) : ?>
                            <?php
                            // Check if the organization has already been displayed
                            $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : null;

                            if ($organizationName && !in_array($organizationName, $displayedOrganizations)) :
                                // Add the organization to the displayed list
                                $displayedOrganizations[] = $organizationName;
                            ?>
                                <li>
                                    <!-- Organization name -->
                                    <strong><?php echo __('Organization:', 'rrze-faudir');?></strong> <?php echo esc_html($organizationName); ?><br />
                                    
                                    <!-- Show functions associated with this organization -->
                                    <strong><?php echo __('Functions:', 'rrze-faudir');?></strong> 
                                    <ul>
                                        <?php foreach ($person['contacts'] as $sameOrgContact) : ?>
                                            <?php if (isset($sameOrgContact['organization']['name']) && $sameOrgContact['organization']['name'] === $organizationName) : ?>
                                                <li>
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
