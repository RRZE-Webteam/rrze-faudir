<?php if (!empty($persons)) : ?>
    <div class="shortcode-contacts-wrapper"> <!-- Flex container for the cards -->
        <?php foreach ($persons as $person) : ?>
            <div class="shortcode-contact-card" itemscope itemtype="https://schema.org/Person">
                <?php  if (count($persons) === 1 && !empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" itemprop="image" />
                <?php else: ?>
                <!--To be implemented after CPT-->
                <img src="/wp-content/uploads/2024/09/V20210305LJ-0043-cropped-e1725968539245.webp" alt="Profile Image" itemprop="image">
                <?php endif; ?>
                
                <!-- Full name with title -->
                <?php
                $options = get_option('rrze_faudir_options');
                $longVersion = "";
                $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                if($hard_sanitize){
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

                // Check if email should be shown and include N/A if it is not available
                if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                    $email_output = __('Email:', 'rrze-faudir') . (isset($person['email']) && !empty($person['email']) ? esc_html($person['email']) : 'N/A');
                }

                // Check if phone should be shown and include N/A if it is not available
                if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                    $phone_output = __('Phone:', 'rrze-faudir') . (isset($person['telephone']) && !empty($person['telephone']) ? esc_html($person['telephone']) : 'N/A');
                }

                // Build the final output based on what's available
                if (!empty($email_output) && !empty($phone_output)) {
                    // If both email and phone should be shown
                    echo '<p>(' . $email_output . ', ' . $phone_output . ')</p>';
                } elseif (!empty($email_output)) {
                    // If only email should be shown
                    echo '<p>(' . $email_output . ')</p>';
                } elseif (!empty($phone_output)) {
                    // If only phone should be shown
                    echo '<p>(' . $phone_output . ')</p>';
                }
                ?>

                <!-- Organizations and functions -->
                <?php if (!empty($person['contacts'])) : ?>
                    <?php
                    $displayedOrganizations = []; // To track displayed organizations
                    ?>
                    <?php foreach ($person['contacts'] as $contact) : ?>
                        <?php
                        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) {
                            $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : null;
                        }
                        // Check if the organization has already been displayed
                        if ($organizationName && !in_array($organizationName, $displayedOrganizations)) :
                            // Add the organization to the displayed list to prevent duplicates
                            $displayedOrganizations[] = $organizationName;
                        ?>
                            <p><strong><?php echo __('Organization:', 'rrze-faudir');?></strong> <?php echo esc_html($organizationName); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div> <!-- End of shortcode-contact-card -->
        <?php endforeach; ?>
    </div> <!-- End of shortcode-contacts-wrapper -->
<?php else : ?>
    <div><?php echo __('Es konnte kein Kontakteintrag gefunden werden.', 'rrze-faudir') ?> </div>
<?php endif; ?>
