<?php if (!empty($persons)) : ?>
    <div class="shortcode-contacts-wrapper"> <!-- Flex container for the cards -->
        <?php foreach ($persons as $person) : ?>
            <div class="shortcode-contact-card">
                <img src="/wp-content/uploads/2024/09/V20210305LJ-0043-cropped-e1725968539245.webp" alt="Profile Image">
                
                <!-- Full name with title -->
                <?php
                $fullName = trim($person['personalTitle'] . ' ' . $person['givenName'] . ' ' . $person['familyName']);
                ?>
                <h2><?php echo esc_html($fullName); ?></h2>

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
