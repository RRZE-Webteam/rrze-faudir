<ul class="fau-contacts-list-custom">
    <?php if (!empty($persons)) : ?>
        <?php foreach ($persons as $person) : ?>
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
                $fullName = trim(($longVersion ? $longVersion : $personal_title ). ' ' . $first_name. ' '. $nobility_title . ' ' . $last_name . ' ' . $title_suffix);
                ?>

                <!-- We need to add condition for url when we add CPT -->
                <section class="list-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                    <a href="<?php echo esc_html($url); ?>" itemprop="url">
                        <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                    </a>
                </section>

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

                <?php
                $displayedOrganizations = []; // To track displayed organizations
                ?>

                <?php if (!empty($person['contacts'])) : ?>
                    <ul>
                        <?php foreach ($person['contacts'] as $contact) : ?>
                            <?php
                            // Check if the organization has already been displayed
                            if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) {
                                $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : null;
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
                                        $function = isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : null ?>
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
