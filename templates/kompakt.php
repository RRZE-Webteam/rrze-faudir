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
                 $options = get_option('rrze_faudir_options');
                 $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                 $longVersion = "";
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
                    $personal_title = (isset($person['personalTitle']) && !empty($person['personalTitle']) ? esc_html($person['personalTitle']) : 'N/A');
                }
                if (in_array('firstName', $show_fields) && !in_array('firstName', $hide_fields)) {
                    $first_name = (isset($person['givenName']) && !empty($person['givenName']) ? esc_html($person['givenName']) : 'N/A');
                }
                if (in_array('titleOfNobility', $show_fields) && !in_array('titleOfNobility', $hide_fields)) {
                    $nobility_title = (isset($person['titleOfNobility']) && !empty($person['titleOfNobility']) ? esc_html($person['titleOfNobility']) : 'N/A');
                }
                if (in_array('familyName', $show_fields) && !in_array('familyName', $hide_fields)) {
                    $last_name = (isset($person['familyName']) && !empty($person['familyName']) ? esc_html($person['familyName']) : 'N/A');
                }
                if (in_array('personalTitleSuffix', $show_fields) && !in_array('personalTitleSuffix', $hide_fields)) {
                    $title_suffix = (isset($person['personalTitleSuffix']) && !empty($person['personalTitleSuffix']) ? esc_html($person['personalTitleSuffix']) : 'N/A');
                }
                $fullName = trim(($longVersion ? $longVersion : $personal_title ). ' ' . $first_name. ' '. $nobility_title . ' ' . $last_name . ' ' . $title_suffix);
                ?>
                <!-- We need to add condition for url when we add CPT -->
                <section class="kompakt-section-title" aria-label="<?php echo esc_html($fullName); ?>"><a href="<?php echo esc_html($url); ?>"><?php echo esc_html($fullName); ?></a></section>


                <?php
                // Initialize output strings for email and phone
                $email_output = '';
                $phone_output = '';

                // Check if email should be shown and include N/A if it is not available
                if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                    echo $email_output = '<p>' . __('Email:', 'rrze-faudir') . (isset($person['email']) && !empty($person['email']) ? esc_html($person['email']) : 'N/A');
                }

                // Check if phone should be shown and include N/A if it is not available
                if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                    echo $phone_output = '<p>' . __('Phone:', 'rrze-faudir') . (isset($person['telephone']) && !empty($person['telephone']) ? esc_html($person['telephone']) : 'N/A');
                }
                ?>
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
                        <!-- Organization name -->
                        <strong><p><?php echo __('Organization:', 'rrze-faudir');?></strong> <?php echo esc_html($organizationName); ?><p>
                        
                        <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) {
                            $function = isset($contact['functionLabel']['en']) ? $contact['functionLabel']['en'] : null ?>
                            <!-- Show functions associated with this organization -->
                        <strong><?php echo __('Functions:', 'rrze-faudir');?></strong>

                        <?php  } ?>
                         
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
            <!-- We need to add condition for url when we add CPT -->
                 <?php
                $business_card_title = rrze_faudir_get_business_card_title();

                echo '<a href="' .esc_html($url) . '" class="business-card-link"><button>' . esc_html($business_card_title) . '</button></a>';
                ?>
                <!-- <a href="?id=<?php //echo esc_attr($person['id']); ?>"><button>More</button></a> -->
                        </div>
            </div>
        <?php endforeach; ?>
<?php else : ?>
    <div><?php echo __('Es konnte kein Kontakteintrag gefunden werden.', 'rrze-faudir') ?> </div>
<?php endif; ?>
