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
    protected ?Config $config = null;
            
    public function __construct(Config $configdata) {
        $configdata->insertOptions();
        $this->config = $configdata;
        
    }
    
    public function register_hooks() {

        // Aktivierungshook
        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'migrate_person_data_on_activation']);
        
        //  Admin Notices
        add_action('admin_notices', [$this, 'rrze_faudir_display_import_notice'], 15);
        
        // Slug-Änderung überwachen
        add_action('update_option_rrze_faudir_options',  [$this, 'rrze_faudir_flush_rewrite_on_slug_change'], 10, 3);
        
        // Rewrite-Regeln speichern
        add_action('admin_init', [$this, 'rrze_faudir_save_permalink_settings']);
        
        // CRON/Scheduler functions
        register_activation_hook(RRZE_PLUGIN_FILE, [$this, 'on_plugin_activation']);
        register_deactivation_hook(RRZE_PLUGIN_FILE, [$this, 'on_plugin_deactivation']);
        add_action('rrze-faudir_check_person_availability', [$this, 'check_api_person_availability']);

        // Check for old scheduler Namens
        $this->migrate_scheduler_hook();
        
        // Definiere Templates
        add_action('template_redirect', [$this, 'maybe_disable_canonical_redirect'], 1);
        add_filter('template_include', [$this, 'load_custom_person_template'], 99);
        add_action('template_redirect', [$this, 'custom_cpt_404_message']);
    }


    public function migrate_person_data_on_activation() {
        register_custom_taxonomy();

        $contact_posts = get_posts([
            'post_type' => 'person',
            'posts_per_page' => -1,
        ]);

        // Initialize counters and reasons array
        $imported_count = 0;
        $imported_list = [];
        $not_imported_count = 0;
        $not_imported_reasons = [];
        
        if (empty($this->config->get('api_key'))) {
            // No API Key, there notice this and then stop here.
           $not_imported_count = count($contact_posts);
           if ($not_imported_count > 0) {
               $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir'). ' '.__('After this you can restart importing old contact entries from there.', 'rrze-faudir');
           } else {
               $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir');
           }
        } else {        
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

                           $api = new API($this->config);

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
                                   $lastorgid = '';

                                   $org = new Organization();
                                   $org->setConfig($this->config);

                                   foreach ($person['contacts'] as $contact) {
                                       // Get the identifier
                                       $contactIdentifier = $contact['identifier'];
                                       $organizationIdentifier = $contact['organization']['identifier'];

                                       $cont = new Contact($contact);
                                       $cont->setConfig($this->config);
                                       $cont->getContactbyAPI($contact['identifier']);

                                       if ((empty($lastorgid)) || ($lastorgid !== $organizationIdentifier)) {
                                            $success = $org->getOrgbyAPI($organizationIdentifier);
                                            if ($success) {
                                                $lastorgid = $organizationIdentifier;
                                            }
                                       }



                                       $contacts[] = array(
                                           'organization' => sanitize_text_field($contact['organization']['name'] ?? ''),
                                           'socials' => $cont->getSocialString(),
                                           'workplace' => $cont->getWorkplacesString(),
                                           'address' => $org->getAdressString(),
                                           'function_en' => $cont->functionLabel['en'] ?? '',
                                           'function_de' => $cont->functionLabel['de'] ?? '',
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
                                       $imported_list[] = __('Person successfully importet', 'rrze-faudir').': <em>'. $post->post_title.'</em>';
                                   }
                               } else {
                                   // Separate messages for each case
                                   if (empty($univisid)) {
                                       $not_imported_count++;
                                       $not_imported_reasons[] = __('Missing UnivIS ID for person', 'rrze-faudir').': <em>'. $post->post_title.'</em>';
                                   } else {
                                       $not_imported_count++;
                                       $not_imported_reasons[] = __('Person with UnivIS ID', 'rrze-faudir').':  <em>'. $post->post_title.'</em>, <code>' . $univisid .'</code> '. __('could not be importet. Possible Reasons: Either E-Mail-Adress from UnivIS wasnt found in public FAUdir entry or person name is not unique in FAUdir', 'rrze-faudir'). '.';
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
        }
        // Store the counts and reasons in transients to display them later
        set_transient('rrze_faudir_imported_count', $imported_count, 60);
        set_transient('rrze_faudir_imported_list', $imported_list, 60);
        set_transient('rrze_faudir_not_imported_count', $not_imported_count, 60);
        set_transient('rrze_faudir_not_imported_reasons', $not_imported_reasons, 60);

    }



    // Add this function to display the notice
    public function rrze_faudir_display_import_notice($echothis = true, $onpluginpage = true) {
        if (!isset($onpluginpage) || ($onpluginpage)) {
            // Only show on the plugins page
            $screen = get_current_screen();
            if ($screen->id !== 'plugins') {
                return;
            }
        }
        $res = '';
        
        $imported_count = get_transient('rrze_faudir_imported_count');
        $imported_list = get_transient('rrze_faudir_imported_list');      
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
                $success = '<div class="notice notice-success is-dismissible">';
                $success .= '<p>'.esc_html($import_message).'</p>';
                
                 // Display imported 
                if (!empty($imported_list)) {
                    $success .= '<ul>';
                    foreach ($imported_list as $reason) {
                        $success .= '<li>'.$reason.'</li>';
                    }
                    $success .= '</ul>';
                  
                }
                $success .= '</div>';

                $res .= $success;
                
            }

            if ($not_imported_count > 0) {
                $errorout = '<div class="notice notice-error is-dismissible">';
                $errorout .= '<p>'.esc_html($not_imported_message).'</p>';
               
                // Display not imported reasons
                if (!empty($not_imported_reasons)) {
                    $errorout .= '<ul>';
                    foreach ($not_imported_reasons as $reason) {
                        $errorout .= '<li>'.$reason.'</li>';
                    }
                    $errorout .= '</ul>';
                  
                }
                $errorout .= '</div>';
                $res .=  $errorout;
            }
            delete_transient('rrze_faudir_imported_count');
            delete_transient('rrze_faudir_imported_list');
            delete_transient('rrze_faudir_not_imported_count');
            delete_transient('rrze_faudir_not_imported_reasons');
        }
                
        if (!isset($onpluginpage) || ($onpluginpage)) {        
            echo $res;
        }
        return $res;
    }


    public function rrze_faudir_flush_rewrite_on_slug_change($old_value, $value, $option) {
        if (  ($option === 'rrze_faudir_options') 
                 && (isset($old_value['person_slug'])) 
                 && (isset($value['person_slug'])) 
                 && ($old_value['person_slug'] !== $value['person_slug'])) {
            flush_rewrite_rules(); // Flush rewrite rules if the slug changes
        }
    }

    public function rrze_faudir_save_permalink_settings(): void  {
        // Simulate visiting the Permalinks page to refresh rewrite rules
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    
    // CRON Scheduler: activate on plugin activating
    public function on_plugin_activation(): void {
        if (!wp_next_scheduled('rrze-faudir_check_person_availability')) {
            wp_schedule_event(time(), 'hourly', 'rrze-faudir_check_person_availability');
        }
    }
    
    
    // CRON Scheduler: deactivate on plugin deactivation
    public function on_plugin_deactivation(): void {
        $timestamp = wp_next_scheduled('rrze-faudir_check_person_availability');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rrze-faudir_check_person_availability');
        }
    }
    
    /*
     * Migrate Function für alten Scheduler Namen
     */
    public function migrate_scheduler_hook(): void {
        // Prüfen, ob der alte Cron-Job noch geplant ist
        $old_hook = 'check_person_availability';
        $new_hook = 'rrze-faudir_check_person_availability';

        $timestamp = wp_next_scheduled($old_hook);

        if ($timestamp) {
            // Entferne den alten Cron-Job
            wp_unschedule_event($timestamp, $old_hook);

        }

        // Wenn der neue Hook noch nicht existiert, planen
        if (!wp_next_scheduled($new_hook)) {
            wp_schedule_event(time(), 'hourly', $new_hook);
        }
    }
    
    
    // Main Task for scheduler
    public function check_api_person_availability(): void {
        if (get_transient('check_person_availability_running')) {
            return;
        }
        
        
     //   error_log('RRZE FAUdir\Maintenance::check_api_person_availability: Start Cron job check_person_availability.');
        set_transient('check_person_availability_running', true, 60);
        $api = new API($this->config);
        
        
        $args = [
            'post_type'      => 'custom_person',
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
        ];

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $person_id = get_post_meta($post->ID, 'person_id', true);

            if (empty($person_id)) {
                wp_update_post([
                    'ID'          => $post->ID,
                    'post_status' => 'draft',
                ]);
                continue;
            }

            $person_data = $api->getPerson($person_id);
            if ($person_data === false || empty($person_data)) {
                wp_update_post([
                    'ID'          => $post->ID,
                    'post_status' => 'draft',
                ]);
          //       error_log('RRZE FAUdir\Maintenance::check_api_person_availability: Person with FAUdir Identifier '.  $person_id. ' moved to draft.');
            }
        }

        delete_transient('check_person_availability_running');
     //   error_log('RRZE FAUdir\Maintanace::check_api_person_availability: Cron job check_person_availability completed.');
        
    }

    
    /*
     * Setze Template für Slug fest
     */
    public static function load_custom_person_template($template) {
        if (get_query_var('custom_person') || is_singular('custom_person')) {
            $plugin_template = plugin_dir_path(__DIR__) . '/templates/single-custom_person.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    
    /*
     * Definiere Fehlermeldungsseite bei Aufruf des Slugs mit Fehlern
     */
    public static function custom_cpt_404_message() {
        global $wp_query;
       // Prüfe CPT-Einzelansicht, aber Post nicht gefunden → 404
        if (isset($wp_query->query_vars['post_type']) &&
            $wp_query->query_vars['post_type'] === 'custom_person' &&
            empty($wp_query->post) ) {
            
            self::render_custom_404();
            return;
        }

        // Prüfe ob Archiv-Slug direkt aufgerufen wurde
        $options = get_option('rrze_faudir_options');
        $slug = !empty($options['person_slug']) ? sanitize_title($options['person_slug']) : 'faudir';
        if (self::is_slug_request($slug)) {

            $redirect = trim($options['redirect_archivpage_uri'] ?? '');
            if (!empty($redirect)) {
                
                // Wenn relativer Pfad → mit Home-URL verbinden
                if (str_starts_with($redirect, '/')) {
                    $redirect = home_url($redirect);
                }

                // Validierung und Weiterleitung
                if (filter_var($redirect, FILTER_VALIDATE_URL)) {
                    wp_redirect(esc_url_raw($redirect), 301);
                    exit;
                }
            }

            // Fallback: 404 anzeigen
            self::render_custom_404();

        }
        
    }
    public static function maybe_disable_canonical_redirect(): void {
        $options = get_option('rrze_faudir_options');
        $redirect = trim($options['redirect_archivpage_uri'] ?? '');

        if (empty($redirect)) {
            return;
        }

        $slug = !empty($options['person_slug']) ? sanitize_title($options['person_slug']) : 'faudir';

        if (self::is_slug_request($slug)) {
            // Nur in diesem Fall canonical-redirect unterbinden
            remove_filter('template_redirect', 'redirect_canonical');
        }
    }

    private static function render_custom_404(): void {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);

        ob_start();
        add_action('shutdown', function () {
            $content = ob_get_clean();
            $new_hero_content = '<div class="hero-container hero-content">'
                . '<p class="presentationtitle">' . __('No contact entry could be found.', 'rrze-faudir') . '</p>'
                . '</div>';
            $updated_content = preg_replace(
                '/<p class="presentationtitle">.*?<\/p>/s',
                $new_hero_content,
                $content
            );
            echo $updated_content;
        }, 0);

        include get_404_template();
        exit;
    }
    
    private static function is_slug_request(string $slug): bool {
        if (! isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        // Ursprünglich angeforderte URI (relativ zur Domain)
        $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // WordPress-Installationspfad (Multisite-/Unterverzeichnis-Support)
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH) ?? '', '/');

        // Entferne ggf. Sprach-/Blogpräfixe
        if (!empty($home_path) && stripos($request_uri, $home_path) === 0) {
            $request_uri = trim(substr($request_uri, strlen($home_path)), '/');
        }

        // Normalisierung: Slashes, Groß-/Kleinschreibung, /index.php entfernen
        $normalized_uri = strtolower(preg_replace('#/index\.php$#', '', $request_uri));
        $normalized_slug = strtolower(trim($slug, '/'));

        return $normalized_uri === $normalized_slug;

    }
    
}