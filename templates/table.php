<table class="fau-contacts-table-custom">
    <thead>
        <tr>
            <?php if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) : ?>
                <th>Name</th>
            <?php endif; ?>
            <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) : ?>
                <th>Email</th>
            <?php endif; ?>
            <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) : ?>
                <th>Phone</th>
            <?php endif; ?>
            <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) : ?>
                <th>Organization</th>
            <?php endif; ?>
            <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) : ?>
                <th>Function</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($persons as $person) : ?>
            <tr>
                <!-- Full Name -->
                <?php if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) : ?>
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
                    <td><section class="table-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                    <a href="<?php echo esc_html($url); ?>"><?php echo esc_html($fullName); ?></a></section></td>
                <?php endif; ?>

                <!-- Email (if available) -->
                <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) : ?>
                    <td><?php echo isset($person['email']) ? esc_html($person['email']) : 'N/A'; ?></td>
                <?php endif; ?>

                <!-- Phone (if available) -->
                <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) : ?>
                    <td><?php echo isset($person['telephone']) ? esc_html($person['telephone']) : 'N/A'; ?></td>
                <?php endif; ?>

                <!-- Organizations and Functions -->
                <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) : ?>
                    <?php
                    $displayedOrganizations = []; // Track displayed organizations
                    $organizationCell = '';
                    $functionCell = '';

                    foreach ($person['contacts'] as $contact) {
                        $organizationName = isset($contact['organization']['name']) ? $contact['organization']['name'] : 'N/A';

                        // Only show each organization once
                        if (!in_array($organizationName, $displayedOrganizations)) {
                            $displayedOrganizations[] = $organizationName;

                            // Add organization and its functions to the cells
                            $organizationCell .= esc_html($organizationName) . '<br>';
                            $functionCell .= '<ul>';

                            // Loop again to list all functions for this organization
                            foreach ($person['contacts'] as $sameOrgContact) {
                                if (isset($sameOrgContact['organization']['name']) && $sameOrgContact['organization']['name'] === $organizationName) {
                                    $functionCell .= '<li>' . esc_html($sameOrgContact['functionLabel']['en']) . '</li>';
                                }
                            }

                            $functionCell .= '</ul>';
                        }
                    }
                    ?>
                    <!-- Display organizations -->
                    <td><?php echo $organizationCell; ?></td>

                    <!-- Display functions -->
                    <td><?php echo $functionCell; ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
