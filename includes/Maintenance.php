<?php
/**
 * Maintanance 
 * 
 * Created on : 14.03.2025, 10:30:46
 */
namespace RRZE\FAUdir;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use RRZE\FAUdir\API;
/*
use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Config;
use RRZE\FAUdir\Debug;
*/


class Maintenance {
    protected static $config;
    
    public function __construct(Config $configdata) {
        self::$config = $configdata;
    }
    
    public function register_hooks() {

        //Aktivierungshook
        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'migrate_person_data_on_activation']);
        
        //  Admin Notices
        add_action('admin_notices', [$this, 'rrze_faudir_display_import_notice'], 15);
    }


    public function migrate_person_data_on_activation() {
        register_custom_taxonomy();

        $contact_posts = get_posts([
            'post_type' => 'person',
            'posts_per_page' => -1,
        ]);

        // Initialize counters and reasons array
        $imported_count = 0;
        $not_imported_count = 0;
        $not_imported_reasons = [];

        if (!empty($contact_posts)) {
            foreach ($contact_posts as $post) {

                // Get Univis ID from old post
                $univisid = get_post_meta($post->ID, 'fau_person_univis_id', true);

                // Check if a custom_person with this UnivIS ID and identifier already exists
                $existing_person = get_posts([
                    'post_type' => 'custom_person',
                    'meta_query' => [
                        [
                            'key' => 'fau_person_faudir_synced',
                            'value' => $univisid,
                            'compare' => '=',
                        ],
                    ],
                    'posts_per_page' => 1,
                ]);

                if ($univisid && !$existing_person) {

                    // Make Univis api call using Univis ID
                    $url = 'http://univis.uni-erlangen.de/prg?search=persons&id=' . $univisid . '&show=json';
                    $response = wp_remote_get($url);
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if ($data === null) {
                         $not_imported_count++;
                         $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir'). ': <em>'. $post->post_title.'</em>, <code>' . $univisid .'</code> '. __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir'). '.';

                    } else {

                       // Extract person data
                       $person = $data['Person'][0];

                       // Get identifier
                       $identifier = $person['idm_id'] ?? null;
                       $email = $person['location'][0]['email'] ?? null;
                       $givenName = $person['firstname'] ?? null;
                       $familyName = $person['lastname'] ?? null;

                       // Determine search parameters
                       $queryParts = [];
                       $personId = null;

                       $api = new API(self::$config);

                       if (!empty($identifier)) {
                           $queryParts[] = 'uid=' . $identifier;
                       } else if (!empty($email)) {
                           // search for contacts with the email
                           
                           $response = $api->getContacts(1, 0, ['lq' => 'workplaces.mails=' . $email]);                           
                           if (empty($response['data'])) {
                               $queryParts[] = 'email=' . $email;
                           } else {
                               // get the person id from the contact's person object
                               $personId = $response['data'][0]['person']['identifier'];
                               $queryParts[] = 'identifier=' . $personId;
                           }
                       } else if (!empty($givenName) || !empty($familyName)) {
                           $queryParts[] = 'givenName=' . $givenName;
                           $queryParts[] = 'familyName=' . $familyName;
                       }


                       $params = [
                           'lq' => implode('&', $queryParts)
                       ];

                       $response = $api->getPersons(1, 0, $params); 

                       if (is_array($response) && isset($response['data'])) {
                           $person = $response['data'][0] ?? null; // there should only be one match

                           if ($person) {
                               // Get data from old post
                               $thumbnail_id = get_post_thumbnail_id($post->ID);
                               $short_description = get_post_meta($post->ID, 'fau_person_description', true);
                               $description = !empty($short_description) ? $short_description : get_post_meta($post->ID, 'fau_person_small_description', true);

                               // Get all contacts for the person
                               $contacts = array();
                               foreach ($person['contacts'] as $contact) {
                                   // Get the identifier
                                   $contactIdentifier = $contact['identifier'];
                                   $organizationIdentifier = $contact['organization']['identifier'];

                                   $contacts[] = array(
                                       'organization' => sanitize_text_field($contact['organization']['name'] ?? ''),
                                       'socials' => fetch_and_format_socials($contactIdentifier),
                                       'workplace' => fetch_and_format_workplaces($contactIdentifier),
                                       'address' => fetch_and_format_address($organizationIdentifier),
                                       'function_en' => $contact['functionLabel']['en'] ?? '',
                                       'function_de' => $contact['functionLabel']['de'] ?? '',
                                   );
                               }

                               // Create a new 'custom_person' post
                               $new_post_id = wp_insert_post([
                                   'post_type' => 'custom_person',
                                   'post_title' => $post->post_title,
                                   'post_content' => $post->post_content,
                                   'post_status' => 'publish',
                                   'meta_input' => [
                                       '_thumbnail_id' => $thumbnail_id ?: '',
                                       '_teasertext_en' => sanitize_text_field($description),
                                       'person_id' => sanitize_text_field($person['identifier']),
                                       'person_name' => sanitize_text_field($person['givenName'] . ' ' . $person['familyName']),
                                       'person_email' => sanitize_email($person['email'] ?? ''),
                                       'person_telephone' => sanitize_text_field($person['telephone'] ?? ''),
                                       'person_given_name' => sanitize_text_field($person['givenName'] ?? ''),
                                       'person_familyName' => sanitize_text_field($person['familyName'] ?? ''),
                                       'person_honorificPrefix' => sanitize_text_field($person['personalTitle'] ?? ''),
                                       'person_honorificSuffix' => sanitize_text_field($person['personalTitleSuffix'] ?? ''),
                                       'person_titleOfNobility' => sanitize_text_field($person['titleOfNobility'] ?? ''),
                                       'person_contacts' => $contacts,
                                       'fau_person_faudir_synced' => $univisid,
                                       'old_person_post_id' => $post->ID
                                   ]
                               ]);

                               // the old post was of post type 'person' and had categories named 'persons_category'
                               // the new post is of post type 'custom_person' and has a category named 'custom_taxonomy'
                               // if the old post had categories, we need to add them to the new post type 'custom_person'

                               // first get the categories from the old post
                               $old_categories = wp_get_post_terms($post->ID, 'persons_category', array("fields" => "all"));

                               if (!empty($old_categories) && !is_wp_error($old_categories)) {
                                   foreach ($old_categories as $old_category) {
                                       // Check if a term with the same slug exists in the new taxonomy
                                       $existing_term = term_exists($old_category->name, 'custom_taxonomy');

                                       // log the existing term, which can be null, the term id, an array or 0                                
                                       // error_log('[RRZE-FAUDIR] Existing term: ' . print_r($existing_term, true));
                                       if (!$existing_term) {
                                           // Create new term in custom_taxonomy
                                           $new_term = wp_insert_term(
                                               $old_category->name,    // the term name
                                               'custom_taxonomy',      // the taxonomy
                                               array(
                                                   'description' => $old_category->description,
                                                   'slug' => $old_category->slug
                                               )
                                           );

                                           if (!is_wp_error($new_term)) {
                                               $term = get_term($new_term['term_id'], 'custom_taxonomy');
                                           }
                                       } else {
                                           $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
                                           $term = get_term($term_id, 'custom_taxonomy');
                                       }

                                       // If we have a valid term, set it for the new post
                                       if ($term && !is_wp_error($term)) {
                                           wp_set_object_terms(
                                               $new_post_id,           // post ID
                                               $term->name,            // use the term name instead of ID
                                               'custom_taxonomy',      // taxonomy
                                               true                    // append
                                           );
                                       }
                                   }
                               }

                               // Increment counter after successful import
                               if ($new_post_id && !is_wp_error($new_post_id)) {
                                   $imported_count++;
                               }
                           } else {
                               // Separate messages for each case
                               if (empty($univisid)) {
                                   $not_imported_count++;
                                   $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir').': <em>'. $post->post_title.'</em>';
                               } else {
                                   $not_imported_count++;
                                   $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir').':  <em>'. $post->post_title.'</em>, <code>' . $univisid .'</code> '. __('already exists', 'rrze-faudir'). '.';
                               }
                           }
                       } else {
                           // Separate messages for each case
                           if (empty($univisid)) {
                               $not_imported_count++;
                               $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir').': <em>' . $post->post_title.'</em>';
                           } else {
                               $not_imported_count++;
                               $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir').':  <em>'. $post->post_title.'</em>, <code>' . $univisid .'</code> '. __('already exists or cannot be found on FAUdir', 'rrze-faudir'). '.';
                           }
                       }
                    }   
                } else {
                    // Separate messages for each case
                    if (empty($univisid)) {
                        $not_imported_count++;
                        $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir').': <em>'. $post->post_title.'</em>';
                    } else {
                        $not_imported_count++;
                        $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir'). ': <em>'. $post->post_title.'</em>, <code>' . $univisid .'</code> '. __('already exists', 'rrze-faudir'). '.';
                    }
                }
            }
        }

        // Store the counts and reasons in transients to display them later
        set_transient('rrze_faudir_imported_count', $imported_count, 60);
        set_transient('rrze_faudir_not_imported_count', $not_imported_count, 60);
        set_transient('rrze_faudir_not_imported_reasons', $not_imported_reasons, 60);

    }



    // Add this function to display the notice
    public function rrze_faudir_display_import_notice() {
        // Only show on the plugins page
        $screen = get_current_screen();
        if ($screen->id !== 'plugins') {
            return;
        }

        $imported_count = get_transient('rrze_faudir_imported_count');
        $not_imported_count = get_transient('rrze_faudir_not_imported_count');
        $not_imported_reasons = get_transient('rrze_faudir_not_imported_reasons');
        if ($imported_count !== false || $not_imported_count !== false) {
            // Import success message
            $import_message = sprintf(
                /* translators: %d: number of imported persons */
                _n(
                    '%d person was successfully imported from the old plugin.',
                    '%d persons were successfully imported from the old plugin.',
                    $imported_count,
                    'rrze-faudir'
                ),
                $imported_count
            );

            // Not imported message
            $not_imported_message = sprintf(
                /* translators: %d: number of not imported persons */
                _n(
                    '%d person was not able to be imported from the old plugin.',
                    '%d persons were not able to be imported from the old plugin.',
                    $not_imported_count,
                    'rrze-faudir'
                ),
                $not_imported_count
            );

            // Display all messages
            if ($imported_count > 0) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html($import_message)
                );
            }

            if ($not_imported_count > 0) {
                $errorout = '<div class="notice notice-error is-dismissible">';
                $errorout .= '<p>'.esc_html($not_imported_message).':</p>';
               
                // Display not imported reasons
                if (!empty($not_imported_reasons)) {
                    $errorout .= '<ul>';
                    foreach ($not_imported_reasons as $reason) {
                        $errorout .= '<li>'.$reason.'</li>';
                    }
                    $errorout .= '</ul>';
                  
                }
                $errorout .= '</div>';
                echo $errorout;
            }
            delete_transient('rrze_faudir_imported_count');
            delete_transient('rrze_faudir_not_imported_count');
            delete_transient('rrze_faudir_not_imported_reasons');
        }
    }


}