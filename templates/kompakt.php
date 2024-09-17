<?php if (!empty($persons)) : ?>
        <?php foreach ($persons as $person) : ?>
            <div  class="shortcode-contact-kompakt">
                <?php  if (count($persons) === 1 && !empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" />
                <?php else: ?>
                <!--To be implemented after CPT-->
                <img src="/wp-content/uploads/2024/09/V20210305LJ-0043-cropped-e1725968539245.webp" alt="Profile Image">
                <?php endif; ?><div style="flex-grow: 1;">
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
                    echo $email_output = '<p>Email: ' . (isset($person['email']) && !empty($person['email']) ? esc_html($person['email']) : 'N/A');
                }

                // Check if phone should be shown and include N/A if it is not available
                if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                    echo $phone_output = '<p>Phone: ' . (isset($person['telephone']) && !empty($person['telephone']) ? esc_html($person['telephone']) : 'N/A');
                }
                ?>
  <?php
                $displayedOrganizations = []; // To track displayed organizations
                ?>

                <?php if (!empty($person['contacts'])) : ?>
                    <?php foreach ($person['contacts'] as $contact) : ?>
                        <?php
                        // Check if the organization has already been displayed
                        $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : null;
                        if ($organizationName && !in_array($organizationName, $displayedOrganizations)) :
                            // Add the organization to the displayed list
                            $displayedOrganizations[] = $organizationName;
                        ?>
                        <!-- Organization name -->
                        <strong><p>Organization:</strong> <?php echo esc_html($organizationName); ?><p>
                        
                        <!-- Show functions associated with this organization -->
                        <strong>Functions:</strong> 
                        <?php foreach ($person['contacts'] as $sameOrgContact) : ?>
                            <?php if (isset($sameOrgContact['organization']['name']) && $sameOrgContact['organization']['name'] === $organizationName) : ?>
                                <p>
                                    <?php echo esc_html($sameOrgContact['functionLabel']['en']); ?>
                            </p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <!-- to be implemented after CPT -->
                <a href="?id=' . $person_id . '"><button>More</button></a>
                        </div>
            </div>
        <?php endforeach; ?>
<?php else : ?>
    <p>No data available.</p>
<?php endif; ?>
