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
            <tr itemscope itemtype="https://schema.org/Person">
                <!-- Full Name -->
                <?php if (in_array('name', $show_fields) && !in_array('name', $hide_fields)) : ?>
                    <?php
                        $options = get_option('rrze_faudir_options');
                        $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];
                        $longVersion = "";
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
                        $fullName = trim(($longVersion ? $longVersion : $personal_title ). ' ' . $first_name. ' '. $nobility_title . ' ' . $last_name);
                        $fullNameWithSuffix = $fullName . ($title_suffix !== '' ? ' <span class="title-suffix">' . $title_suffix . '</span>' : '');
                        ?>
                    <!-- We need to add condition for url when we add CPT -->
                    <td>
                        <section class="table-section-title" aria-label="<?php echo esc_attr($fullName . ' ' . $title_suffix); ?>">
                            <a href="<?php echo esc_url($url); ?>" itemprop="url">
                                <span itemprop="name"><?php echo wp_kses_post($fullNameWithSuffix); ?></span>
                            </a>
                        </section>
                    </td>
                <?php endif; ?>

                <!-- Email (if available) -->
                <?php if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) : ?>
                    <td>
                        <?php if (isset($person['email']) && !empty($person['email'])) : ?>
                            <span itemprop="email"><?php echo esc_html($person['email']); ?></span>
                        <?php else : ?>
                            N/A
                        <?php endif; ?>
                    </td>
                <?php endif; ?>

                <!-- Phone (if available) -->
                <?php if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) : ?>
                    <td>
                        <?php if (isset($person['telephone']) && !empty($person['telephone'])) : ?>
                            <span itemprop="telephone"><?php echo esc_html($person['telephone']); ?></span>
                        <?php else : ?>
                            N/A
                        <?php endif; ?>
                    </td>
                <?php endif; ?>

                <!-- Organizations and Functions -->
                <?php if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) : ?>
                    <td>
                        <?php
                        $displayedOrganizations = [];
                        foreach ($person['contacts'] as $contact) :
                            if (isset($contact['organization']['name']) && !in_array($contact['organization']['name'], $displayedOrganizations)) :
                                $displayedOrganizations[] = $contact['organization']['name'];
                        ?>
                                <div itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
                                    <span itemprop="name"><?php echo esc_html($contact['organization']['name']); ?></span>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </td>
                <?php endif; ?>

                <?php if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) : ?>
                    <td>
                        <ul>
                            <?php foreach ($person['contacts'] as $contact) : ?>
                                <?php if (isset($contact['functionLabel']['en'])) : ?>
                                    <li itemprop="jobTitle"><?php echo esc_html($contact['functionLabel']['en']); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
