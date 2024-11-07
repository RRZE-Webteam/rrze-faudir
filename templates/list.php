<ul class="fau-contacts-list-custom">
    <?php if (!empty($persons)) : ?>
        <?php foreach ($persons as $person) : ?>
            <?php if (isset($person['error'])): ?>
            <div class="faudir-error">
                <?php echo esc_html($person['message']); ?>
            </div>
            <?php else: ?>
            <?php if (!empty($person)) : ?>
                <?php 
                 $contact_posts = get_posts([
                    'post_type' => 'custom_person',
                    'meta_key' => 'person_id',
                    'meta_value' => $person['identifier'],
                    'posts_per_page' => 1, // Only fetch one post matching the person ID
                ]);
                        $cpt_url = !empty($contact_posts) ? get_permalink($contact_posts[0]->ID) : '';
                    
                        // Use custom post type URL if multiple persons or no direct URL
                        $final_url = (count($persons) > 1 || empty($url)) ? $cpt_url : $url;?>
            
                <li itemscope itemtype="https://schema.org/Person">
                    <!-- Full name with title -->
                    <?php
                    $options = get_option('rrze_faudir_options');
                    $hard_sanitize = isset($options['hard_sanitize']) && $options['hard_sanitize'];

                    $longVersion = '';
                    if($hard_sanitize){
                        $prefix = $person['personalTitle'] ?? '';
                        $prefixes = array(
                            '' => __('Not specified', 'rrze-faudir'),
                            'Dr.' => __('Doktor', 'rrze-faudir'),
                            'Prof.' => __('Professor', 'rrze-faudir'),
                            'Prof. Dr.' => __('Professor Doktor', 'rrze-faudir'),
                            'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
                            'Prof. Dr. em.' => __('Professor Doktor (Emeritus)', 'rrze-faudir'),
                            'PD' => __('Privatdozent', 'rrze-faudir'),
                            'PD Dr.' => __('Privatdozent Doktor', 'rrze-faudir')
                        );
                        // Check if the prefix exists in the array and display the long version
                        $longVersion = isset($prefixes[$prefix]) ? $prefixes[$prefix] : __('Unknown', 'rrze-faudir');
                    }
                    $personal_title = "";
                    $first_name= "";
                    $nobility_title= "";
                    $last_name ="";
                    $title_suffix ="";
                    if (in_array('personalTitle', $show_fields) && !in_array('personalTitle', $hide_fields)) {
                        $personal_title = (isset($person['personalTitle']) && !empty($person['personalTitle']) ? esc_html($person['personalTitle']) : '');
                    }
                    if (in_array('givenName', $show_fields) && !in_array('givenName', $hide_fields)) {
                        $first_name = (isset($person['givenName']) && !empty($person['givenName']) ? esc_html($person['givenName']) : '');
                    }
                    if (in_array('titleOfNobility', $show_fields) && !in_array('titleOfNobility', $hide_fields)) {
                        $nobility_title = (isset($person['titleOfNobility']) && !empty($person['titleOfNobility']) ? esc_html($person['titleOfNobility']) : '');
                    }
                    if (in_array('familyName', $show_fields) && !in_array('familyName', $hide_fields)) {
                        $last_name = (isset($person['familyName']) && !empty($person['familyName']) ? esc_html($person['familyName']) : '');
                    }
                    if (in_array('personalTitleSuffix', $show_fields) && !in_array('personalTitleSuffix', $hide_fields)) {
                    $title_suffix = (isset($person['personalTitleSuffix']) && !empty($person['personalTitleSuffix']) ? ' (' . esc_html($person['personalTitleSuffix']) . ')' : '');
                    }
                     // Construct the full name
            $fullName = trim(
                ($longVersion ? $longVersion : $personal_title) . ' ' .
                ($first_name) . ' ' .
                ($nobility_title) . ' ' .
                ($last_name) . ' ' .
                ($title_suffix)
            );
                    ?>
                    <?php if (in_array('displayName', $show_fields) && !in_array('displayName', $hide_fields)) : ?>
                    <section class="card-section-title" aria-label="<?php echo esc_html($fullName); ?>">
                        <?php if (!empty($final_url)) : ?>
                            <a href="<?php echo esc_url($final_url); ?>" itemprop="url">
                                <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                            </a>
                        <?php else : ?>
                            <span itemprop="name"><?php echo esc_html($fullName); ?></span>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>
                    <?php
                    // Initialize output strings for email and phone
                    $email_output = '';
                    $phone_output = '';
                            
                
                        // Check if email should be shown and output only if an email is available
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $email = (isset($person['email']) && !empty($person['email'])) 
                                ? esc_html($person['email']) 
                                : ''; // Custom post type email

                            // Only display the email if it's not empty
                            if (!empty($email)) {
                                echo '<p>' . esc_html__('Email:', 'rrze-faudir') . ' <span itemprop="email">' . esc_html($email) . '</span></p>';
                            }
                        }
                        // Check if phone should be shown and include N/A if not available
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            // Get the email from $person array or fallback to custom post type
                            $phone = (isset($person['telephone']) && !empty($person['telephone']) 
                            ? esc_html($person['telephone']) 
                            : '');
                            // Only display the email if it's not empty
                            if (!empty($phone)) {
                                echo '<p>' . esc_html__('Phone:', 'rrze-faudir') . ' <span itemprop="phone">' . esc_html($phone) . '</span></p>';
                            }
                        }
                
                    ?>

                    <?php
                    $displayedOrganizations = []; // To track displayed organizations
                    ?>

<?php if (!empty($person['contacts'])) : ?>

<?php
// Array to track displayed organizations and their associated functions
$organizationFunctions = [];

foreach ($person['contacts'] as $contact) {
    $organizationName = '';

    // Check if the organization is allowed to be shown
        $organizationName = $contact['organization']['name'] ?? '';

    if ($organizationName) {
        $locale = get_locale();
        $isGerman = strpos($locale, 'de_DE') !== false || strpos($locale, 'de_DE_formal') !== false;

        // Determine the appropriate function label based on locale
        $function = '';
        if (!empty($contact['functionLabel'])) {
            $function = $isGerman ? 
                ($contact['functionLabel']['de'] ?? '') : 
                ($contact['functionLabel']['en'] ?? '');
        }

        // Group functions by organization
        if (!empty($function)) {
            $organizationFunctions[$organizationName][] = $function;
        }
    }
}

// Display each organization and its functions
foreach ($organizationFunctions as $orgName => $functions) {
    if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) {
    ?>
    <ul>
        <li itemprop="affiliation" itemscope itemtype="https://schema.org/Organization">
            <strong><?php echo esc_html__('Organization:', 'rrze-faudir'); ?></strong>
            <span itemprop="name"><?php echo esc_html($orgName); ?></span><br />
        </li>
    </ul>
        <?php } foreach ($functions as $function) : 
    if (in_array('function', $show_fields) && !in_array('function', $hide_fields)) {
        ?>
            <ul>
            <li itemprop="jobTitle">
                <?php echo esc_html($function); ?>
            </li>
            </ul>
        <?php } endforeach; ?>
    
    <?php
}
?>

<?php endif; ?>


                </li>
            <?php else : ?>
                <li itemprop><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </li>
            <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else : ?>
    <div><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
    <?php endif; ?>
</ul>
