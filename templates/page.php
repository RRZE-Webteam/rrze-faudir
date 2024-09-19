<?php if (!empty($persons)) : ?>
    <div>
        <?php foreach ($persons as $person) : ?>
            <div class="contact-page">
                <div class="contact-page-img-container">
                    <div style="flex-grow: 1; max-width:70%">
                
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
                        <!-- We need to add condition for url when we add CPT -->
                        <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>"><a href="<?php echo esc_html($url); ?>"><?php echo esc_html($fullName); ?></a></section>
                
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
        
                            <!-- Array to track displayed organizations -->
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
                                <strong><p><?php echo __('Organization:', 'rrze-faudir');?></strong> <?php echo esc_html($organizationName); ?><p><br />
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
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                     
                        <h3>Meine Sprechzeiten </h3>
                        <p>Überall dieselbe alte Leier. Das Layout ist fertig, der Text lässt auf sich warten. Damit das Layout nun nicht nackt im Raume steht und sich klein und leer vorkommt, springe ich ein: der Blindtext.
                        Täglich Mo, 08:00 - 10:00, Raum 00.456, Bitte vorher anmelden!
                    </div>
                    <?php if (!empty($image_url)) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="Person Image" />
                    <?php endif; ?>

                </div>
                <h3>Mustertext Biographie:</h3>
                <p>Überall dieselbe alte Leier. Das Layout ist fertig, der Text lässt auf sich warten. Damit das Layout nun nicht nackt im Raume steht und sich klein und leer vorkommt, springe ich ein: der Blindtext. Genau zu diesem Zwecke erschaffen, immer im Schatten meines großen Bruders »Lorem Ipsum«, freue ich mich jedes Mal, wenn Sie ein paar Zeilen lesen. Denn esse est percipi – Sein ist wahrgenommen werden. Und weil Sie nun schon die Güte haben, mich ein paar weitere Sätze lang zu begleiten, möchte ich diese Gelegenheit nutzen, Ihnen nicht nur als Lückenfüller zu dienen, sondern auf etwas hinzuweisen, das es ebenso verdient wahrgenommen zu werden: Webstandards nämlich. Sehen Sie, Webstandards sind das Regelwerk, auf dem Webseiten aufbauen. So gibt es Regeln für HTML, CSS, JavaScript oder auch XML; Worte, die Sie vielleicht schon einmal von Ihrem Entwickler gehört haben. Diese Standards sorgen dafür, dass alle Beteiligten aus einer Webseite den größten Nutzen ziehen. Im Gegensatz zu früheren Webseiten müssen wir zum Beispiel nicht mehr zwei verschiedene Webseiten für den Internet Explorer und einen anderen Browser programmieren. Es reicht eine Seite, die – richtig angelegt – sowohl auf verschiedenen Browsern im Netz funktioniert, aber ebenso gut für den Ausdruck oder die Darstellung auf einem Handy geeignet ist. Wohlgemerkt: Eine Seite für alle Formate. Was für eine Erleichterung. Standards sparen Zeit bei den Entwicklungskosten und sorgen dafür, dass sich Webseiten später leichter pflegen lassen. Natürlich nur dann, wenn sich alle an diese Standards halten. Das gilt für Browser wie Firefox, Opera:</p>               
            </div> <!-- End of shortcode-contact-card -->
        <?php endforeach; ?>
    </div> <!-- End of shortcode-contacts-wrapper -->
<?php else : ?>
    <p>No data available.</p>
<?php endif; ?>
