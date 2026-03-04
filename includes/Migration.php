<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\API;

class Migration {
    private Config $config;
    private CPT $cpt;
    
    public function __construct(Config $config, CPT $cpt) {
        $config->insertOptions();
        $this->config = $config;
        $this->cpt = $cpt;
    }
    

     /**
     * Migration beim Aktivieren:
     * - importiert Posts aus altem CPT 'person'
     * - legt neuen CPT-Post an
     * - speichert als Meta NUR: person_id (+ interne Helfer-Metas)
     * - setzt post_excerpt direkt aus alter Kurzbeschreibung
     * - KEINE Speicherung von alten person_* Feldern / Kontaktdaten
     */
    public function importFromFauPerson(): array {
        if (!FaudirUtils::isFauPersonActive()) {
            return [
                'imported_count' => 0,
                'imported_list' => [],
                'not_imported_count' => 0,
                'not_imported_reasons' => [__('FAU Person plugin is not active.', 'rrze-faudir')],
            ];
        }
        
        $post_type = (string) $this->config->get('person_post_type');
        $taxonomy  = (string) $this->config->get('person_taxonomy');

        
        $fau_person_post_type = (string) $this->config->get('fau-person_post_type');
        $fau_person_posts = get_posts([
            'post_type'      => $fau_person_post_type,
            'posts_per_page' => -1,
        ]);

        $imported_count = 0;
        $imported_list = [];
        $not_imported_count = 0;
        $not_imported_reasons = [];

        if (empty($this->config->get('api_key')) && !API::isUsingNetworkKey()) {
            $not_imported_count = is_array($fau_person_posts) ? count($fau_person_posts) : 0;
            if ($not_imported_count > 0) {
                $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir') . ' ' . __('After this you can restart importing old contact entries from there.', 'rrze-faudir');
            } else {
                $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir');
            }
        } else {
            if (!empty($fau_person_posts)) {
                foreach ($fau_person_posts as $post) {
                    
                    $uid = $email = $given = $family = '';
                    
                    $univisid = (string) get_post_meta($post->ID, 'fau_person_univis_id', true);
                    $univisid = FaudirUtils::sanitizeUnivISId($univisid);

                    if (FaudirUtils::isValidUnivISId($univisid)) {
                        // hole Vorname,, Nachname und EMail aus UnivIS                      
                       
                        $existing_person = get_posts([
                            'post_type'      => $post_type,
                            'meta_query'     => [
                                [
                                    'key'     => 'fau_person_faudir_synced',
                                    'value'   => $univisid,
                                    'compare' => '=',
                                ],
                            ],
                            'posts_per_page' => 1,
                        ]);
                       if ($existing_person) {
                            // do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson):  Person already exists.");

                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('already exists', 'rrze-faudir') . '.';
                            continue;
                       }
                                              
                        $url = sprintf(Constants::UNIVIS_PERSON_JSON_URL, rawurlencode($univisid));
                        $response = wp_remote_get($url, [
                            'timeout' => 10,
                        ]);
                        $body = wp_remote_retrieve_body($response);
                        $data = json_decode($body, true);

                        if (($data === null) ||  !isset($data['Person'])) {
                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir') . '.';
                            continue;
                        }
                        if (count($data['Person']) > 1) {
                            // Treffer nicht eindeutig!
                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('got no unique result. Please add a correct UnivIS Id or leave empty.', 'rrze-faudir') . '.';
                            continue;
                        }
                        $person = $data['Person'][0];
                        if (!$person) {
                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir') . '.';
                            continue;
                        }


                        $uid = $person['idm_id'] ?? null;
                        $email = $person['location'][0]['email'] ?? null;
                        $given = $person['firstname'] ?? null;
                        $family = $person['lastname'] ?? null;
                        // do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson): Got data from UnivIS. Email: {$email}");

                    } else {
                        $email = get_post_meta($post->ID, 'fau_person_email', true);
                        $given = get_post_meta($post->ID, 'fau_person_givenName', true);
                        $family = get_post_meta($post->ID, 'fau_person_familyName', true);
                    }
                    

                    if (FaudirUtils::isValidEmailAddress($email) && (!empty($given)) && (!empty($family))) {
                //        do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson): Looking for FAU Person entry with: email: {$email}, firstname: {$given}, lastname: {$family}");
                    } else {
                //        do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson): Either no email, name, familyname to look for. email: {$email}, firstname: {$given}, lastname: {$family}");
                        $not_imported_count++;
                        $not_imported_reasons[] =  '<em>' . $post->post_title . '</em> ' . __('could not be imported, due to missing search data (no valid UnivIS entry or no valid comibation of email, givenname and familyname)', 'rrze-faudir') . '.';
                        continue;
                    }
                    

                        
                        $api = new API($this->config);
                        $params = [
                            'email'      => $email,
                            'givenName'  => $given,
                            'familyName' => $family,
                        ];
                        if (!empty($uid)) {
                            $params['identifier'] = $uid;
                        }
                        $resp = $api->getPersons(1, 0, $params);

                        if (!is_array($resp) || empty($resp['data']) || !is_array($resp['data'])) {
                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $given. ' '. $family. '('.$email.')</em> ' . __('cannot be found on FAUdir. Maybe not public?', 'rrze-faudir');
                            continue;
                        }

                        $count = count($resp['data']);
                        if ($count !== 1) {
                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $given. ' '. $family. '('.$email.')</em> ' . __('could not be imported because the result is not unique.', 'rrze-faudir');
                            continue;
                        }
                        $p = $resp['data'][0];
                        if (empty($p['identifier'])) {
                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $given. ' '. $family. '('.$email.')</em> ' . __('cannot be found on FAUdir. Maybe not public?', 'rrze-faudir');
                            continue;
                        }
                        
                        $personId = (string) $p['identifier'];
 
 
                        $existingByPersonId = $this->cpt->findPostIdByPersonId($personId);
                        if ($existingByPersonId) {
                       //      do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson): Already existing in CPT: {$personId}");

                            $not_imported_count++;
                            $not_imported_reasons[] = '<em>' . $given. ' '. $family. '('.$email.')</em> , FAUdir Id <code>' . $personId . '</code> ' . __('already exists', 'rrze-faudir') . '.';
                            continue;
                        }
                        
                        
                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                        $short_desc = get_post_meta($post->ID, 'fau_person_description', true);
                        $description = !empty($short_desc) ? $short_desc : get_post_meta($post->ID, 'fau_person_small_description', true);

                        $excerpt = '';
                        if (!empty($description)) {
                            $excerpt = sanitize_text_field($description);
                        }

                        $meta_input = [
                            'person_id'          => sanitize_text_field((string) $p['identifier']),
                            'old_person_post_id' => (int) $post->ID,
                        ];

                        if ($thumbnail_id > 0) {
                            $meta_input['_thumbnail_id'] = (int) $thumbnail_id;
                        }

                        if (FaudirUtils::isValidUnivISId($univisid)) {
                            $meta_input['fau_person_faudir_synced'] = $univisid;
                        }

                        $new_post_id = wp_insert_post([
                            'post_type'    => $post_type,
                            'post_title'   => $post->post_title,
                            'post_content' => $post->post_content,
                            'post_excerpt' => $excerpt,
                            'post_status'  => 'publish',
                            'meta_input'   => $meta_input,
                        ], true);
                        if (is_wp_error($new_post_id)) {
                              // do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson): Error on wp_insert_post: email: {$email}, firstname: {$given}, lastname: {$family}");
                               $not_imported_count++;
                               $not_imported_reasons[] = '<em>' . $given. ' '. $family. '('.$email.')</em> ' . __('could not be imported due to an internal error.', 'rrze-faudir');
                               continue;
                        }
                         // Zeitstempel setzen 
                        update_post_meta($new_post_id, Constants::META_LAST_SUCCESS_AT, time());
                        update_post_meta($new_post_id, Constants::META_LAST_FAILURE_AT, 0);
                        update_post_meta($new_post_id, Constants::META_FAILURE_COUNT, 0);
                        
                        $this->migrate_categories((int) $post->ID, (int) $new_post_id, $taxonomy);
                        if ($thumbnail_id > 0) {
                            set_post_thumbnail((int) $new_post_id, (int) $thumbnail_id);
                        }

                        
                        $imported_count++;
                        $imported_list[] = '<em>' . $given. ' '. $family. '('.$email.')</em> '. __('was successfully imported.', 'rrze-faudir');
                        // do_action('rrze.log.info', "FAUdir\Migration (importFromFauPerson): Successfully added: email: {$email}, firstname: {$given}, lastname: {$family}");
             
                }
            }
        }

        return [
               'imported_count' => $imported_count,
               'imported_list' => $imported_list,
               'not_imported_count' => $not_imported_count,
               'not_imported_reasons' => $not_imported_reasons,
           ];
    }

    private function migrate_categories(int $old_post_id, int $new_post_id, string $taxonomy): void {
        if ($new_post_id <= 0) {
            return;
        }

        $old_categories = wp_get_post_terms($old_post_id, 'persons_category', ['fields' => 'all']);
        if (empty($old_categories) || is_wp_error($old_categories)) {
            return;
        }

        foreach ($old_categories as $old_category) {
            $existing_term = term_exists($old_category->name, $taxonomy);
            if (!$existing_term) {
                $new_term = wp_insert_term(
                    $old_category->name,
                    $taxonomy,
                    [
                        'description' => $old_category->description,
                        'slug'        => $old_category->slug,
                    ]
                );
                if (!is_wp_error($new_term)) {
                    $term = get_term($new_term['term_id'], $taxonomy);
                } else {
                    $term = null;
                }
            } else {
                $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
                $term = get_term($term_id, $taxonomy);
            }

            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($new_post_id, $term->name, $taxonomy, true);
            }
        }
    }

   
    
}