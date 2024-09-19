<?php if (!empty($persons)) : ?>
    <div class="shortcode-contacts-wrapper"> <!-- Flex container for the cards -->
        <?php foreach ($persons as $person) : ?>
            <div class="shortcode-contact-card">
                <?php  if (count($persons) === 1 && !empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" />
                <?php else: ?>
                <!--To be implemented after CPT-->
                <img src="/wp-content/uploads/2024/09/V20210305LJ-0043-cropped-e1725968539245.webp" alt="Profile Image">
                <?php endif; ?>
                
                <!-- Full name with title -->
                <?php
                        $options = get_option('rrze_faudir_options');
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
                        $fullName = trim(($longVersion ? $longVersion : $person['personalTitle'] ). ' ' . $person['givenName'] . ' ' . $person['familyName']);
                        ?>
                <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>"><?php echo esc_html($fullName); ?></section>

                <?php
                // Initialize output strings for email and phone
                $email_output = '';
                $phone_output = '';

                // Check if email should be shown and include N/A if it is not available
                if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                    $email_output = 'Email: ' . (isset($person['email']) && !empty($person['email']) ? esc_html($person['email']) : 'N/A');
                }

                // Check if phone should be shown and include N/A if it is not available
                if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                    $phone_output = 'Phone: ' . (isset($person['telephone']) && !empty($person['telephone']) ? esc_html($person['telephone']) : 'N/A');
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
                        $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : null;
                        // Check if the organization has already been displayed
                        if ($organizationName && !in_array($organizationName, $displayedOrganizations)) :
                            // Add the organization to the displayed list to prevent duplicates
                            $displayedOrganizations[] = $organizationName;
                        ?>
                            <p><strong>Organization:</strong> <?php echo esc_html($organizationName); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div> <!-- End of shortcode-contact-card -->
        <?php endforeach; ?>
    </div> <!-- End of shortcode-contacts-wrapper -->
<?php else : ?>
    <p>No data available.</p>
<?php endif; ?>
