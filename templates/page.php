<?php if (!empty($persons)) : ?>
    <div itemscope itemtype="https://schema.org/ProfilePage">
        <?php foreach ($persons as $person) : ?>
            <div class="contact-page">
                <div class="contact-page-img-container">
                    <div style="flex-grow: 1; max-width:70%" itemscope itemtype="https://schema.org/Person">
                        
                        <!-- Full name with title -->
                        <?php
                        $options = get_option('rrze_faudir_options');
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                        $longVersion = "";
                        if ($hard_sanitize) {
                            $prefix = $person['personalTitle'];
                            $prefixes = array(
                                '' => __('Keine Angabe', 'fau-person'),
                                'Dr.' => __('Doktor', 'fau-person'),
                                'Prof.' => __('Professor', 'fau-person'),
                                'Prof. Dr.' => __('Professor Doktor', 'fau-person'),
                                'Prof. em.' => __('Professor (Emeritus)', 'fau-person'),
                                'Prof. Dr. em.' => __('Professor Doktor (Emeritus)', 'fau-person'),
                                'PD' => __('Privatdozent', 'fau-person'),
                                'PD Dr.' => __('Privatdozent Doktor', 'fau-person')
                            );
                            // Check if the prefix exists in the array and display the long version
                            $longVersion = isset($prefixes[$prefix]) ? $prefixes[$prefix] : __('Unbekannt', 'fau-person');
                        }

                        // Define fields for structured data
                        $personal_title = "";
                        $first_name = "";
                        $nobility_title = "";
                        $last_name = "";
                        $title_suffix = "";
                        
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
                        
                        $fullName = trim(($longVersion ? $longVersion : $personal_title) . ' ' . $first_name . ' ' . $nobility_title . ' ' . $last_name . ' ' . $title_suffix);
                        ?>
                        
                        <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                            <a href="<?php echo esc_html($url); ?>" itemprop="url"><?php echo esc_html($fullName); ?></a>
                        </section>

                        <?php
                        // Initialize output strings for email and phone
                        $email_output = '';
                        $phone_output = '';

                        // Check if email should be shown and include N/A if not available
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            echo $email_output = '<p>' . __('Email:', 'rrze-faudir') . ' <span itemprop="email">' . (isset($person['email']) && !empty($person['email']) ? esc_html($person['email']) : 'N/A') . '</span></p>';
                        }

                        // Check if phone should be shown and include N/A if not available
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            echo $phone_output = '<p>' . __('Phone:', 'rrze-faudir') . ' <span itemprop="telephone">' . (isset($person['telephone']) && !empty($person['telephone']) ? esc_html($person['telephone']) : 'N/A') . '</span></p>';
                        }
                        ?>

                        <!-- Array to track displayed organizations -->
                        <?php
                        $displayedOrganizations = []; // To track displayed organizations
                        ?>

                        <?php if (!empty($person['contacts'])) : ?>
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
                                <strong><p><?php echo __('Organization:', 'rrze-faudir');?></strong> 
                                    <span itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                                        <span itemprop="name"><?php echo esc_html($organizationName); ?></span>
                                    </span>
                                <p><br />

                                <?php 
                                if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) {
                                    $function = isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : null; ?>
                                    <!-- Show functions associated with this organization -->
                                    <strong><?php echo __('Functions:', 'rrze-faudir');?></strong>
                                    <ul>
                                        <?php foreach ($person['contacts'] as $sameOrgContact) : ?>
                                            <?php if (isset($sameOrgContact['organization']['name']) && $sameOrgContact['organization']['name'] === $organizationName) : ?>
                                                <li itemprop="jobTitle"><?php echo esc_html($sameOrgContact['functionLabel']['en']); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php } ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                     
                        <h3>Meine Sprechzeiten</h3>
                        <p>Überall dieselbe alte Leier. Das Layout ist fertig, der Text lässt auf sich warten. Damit das Layout nun nicht nackt im Raume steht und sich klein und leer vorkommt, springe ich ein: der Blindtext. Täglich Mo, 08:00 - 10:00, Raum 00.456, Bitte vorher anmelden!</p>
                    </div>

                    <?php if (!empty($image_url)) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" itemprop="image" />
                    <?php endif; ?>
                </div>

                <h3>Mustertext Biographie:</h3>
                <p>Überall dieselbe alte Leier. Das Layout ist fertig, der Text lässt auf sich warten... <strong><em>Webstandards nämlich.</em></strong></p>
            </div> <!-- End of shortcode-contact-card -->
        <?php endforeach; ?>
    </div> <!-- End of shortcode-contacts-wrapper -->
<?php else : ?>
    <div><?php echo __('Es konnte kein Kontakteintrag gefunden werden.', 'rrze-faudir'); ?> </div>
<?php endif; ?>