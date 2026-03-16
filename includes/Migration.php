<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\API;

class Migration {
    private Config $config;
    private CPT $cpt;
    
    public function __construct(Config $config, CPT $cpt) {
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
        $taxonomy = (string) $this->config->get('person_taxonomy');
        $fau_person_post_type = (string) $this->config->get('fau-person_post_type');

        $fau_person_posts = get_posts([
            'post_type' => $fau_person_post_type,
            'posts_per_page' => -1,
        ]);

        $imported_count = 0;
        $imported_list = [];
        $not_imported_count = 0;
        $not_imported_reasons = [];

        if (FaudirUtils::getKey() === '') {
            $not_imported_count = is_array($fau_person_posts) ? count($fau_person_posts) : 0;

            if ($not_imported_count > 0) {
                $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir') . ' ' . __('After this you can restart importing old contact entries from there.', 'rrze-faudir');
            } else {
                $not_imported_reasons[] = __('API Key missing. Please enter an API key in settings.', 'rrze-faudir');
            }

            return [
                'imported_count' => $imported_count,
                'imported_list' => $imported_list,
                'not_imported_count' => $not_imported_count,
                'not_imported_reasons' => $not_imported_reasons,
            ];
        }

        if (empty($fau_person_posts)) {
            return [
                'imported_count' => 0,
                'imported_list' => [],
                'not_imported_count' => 0,
                'not_imported_reasons' => [],
            ];
        }

        foreach ($fau_person_posts as $post) {
            $uid = '';
            $email = '';
            $given = '';
            $family = '';

            $univisid = (string) get_post_meta($post->ID, 'fau_person_univis_id', true);
            $univisid = FaudirUtils::sanitizeUnivISId($univisid);
            $hasValidUnivisId = FaudirUtils::isValidUnivISId($univisid);

            if ($hasValidUnivisId) {
                $existing_person = get_posts([
                    'post_type' => $post_type,
                    'meta_query' => [
                        [
                            'key' => 'fau_person_faudir_synced',
                            'value' => $univisid,
                            'compare' => '=',
                        ],
                    ],
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ]);

                if (!empty($existing_person)) {
                    $not_imported_count++;
                    $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('already exists', 'rrze-faudir') . '.';
                    continue;
                }

                $univisPerson = $this->fetchUnivisPersonData($univisid);

                if ($univisPerson === null) {
                    $not_imported_count++;
                    $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('cannot be found on UnivIS. Account either removed or UnivIS Id wrong', 'rrze-faudir') . '.';
                    continue;
                }

                if ($univisPerson === false) {
                    $not_imported_count++;
                    $not_imported_reasons[] = '<em>' . $post->post_title . '</em> (UnivIS Id <code>' . $univisid . '</code>) ' . __('could not be imported because UnivIS is currently unavailable or returned an invalid response.', 'rrze-faudir') . '.';
                    continue;
                }

                $uid = isset($univisPerson['idm_id']) ? (string) $univisPerson['idm_id'] : '';
                $email = isset($univisPerson['location'][0]['email']) ? (string) $univisPerson['location'][0]['email'] : '';
                $given = isset($univisPerson['firstname']) ? (string) $univisPerson['firstname'] : '';
                $family = isset($univisPerson['lastname']) ? (string) $univisPerson['lastname'] : '';
            } else {
                $email = (string) get_post_meta($post->ID, 'fau_person_email', true);
                $given = (string) get_post_meta($post->ID, 'fau_person_givenName', true);
                $family = (string) get_post_meta($post->ID, 'fau_person_familyName', true);
            }

            if (!FaudirUtils::isValidEmailAddress($email) || empty($given) || empty($family)) {
                $not_imported_count++;
                $not_imported_reasons[] = '<em>' . $post->post_title . '</em> ' . __('could not be imported, due to missing search data (no valid UnivIS entry or no valid comibation of email, givenname and familyname)', 'rrze-faudir') . '.';
                continue;
            }

            $params = [
                'email' => $email,
                'givenName' => $given,
                'familyName' => $family,
            ];

            if ($uid !== '') {
                $params['identifier'] = $uid;
            }

            $p = $this->findUniqueFaudirPerson($params);
            if ($p === null) {
                $not_imported_count++;
                $not_imported_reasons[] = '<em>' . $given . ' ' . $family . ' (' . $email . ')</em> ' . __('could not be imported because no unique public FAUdir entry was found.', 'rrze-faudir');
                continue;
            }

            $personId = (string) $p['identifier'];

            $existingByPersonId = $this->cpt->findPostIdByPersonId($personId);
            if ($existingByPersonId) {
                $not_imported_count++;
                $not_imported_reasons[] = '<em>' . $given . ' ' . $family . ' (' . $email . ')</em>, FAUdir Id <code>' . $personId . '</code> ' . __('already exists', 'rrze-faudir') . '.';
                continue;
            }

            $thumbnail_id = get_post_thumbnail_id($post->ID);

            $description = (string) get_post_meta($post->ID, 'fau_person_description', true);
            if ($description === '') {
                $description = (string) get_post_meta($post->ID, 'fau_person_small_description', true);
            }

            $excerpt = '';
            if ($description !== '') {
                $excerpt = sanitize_text_field($description);
            }

            $meta_input = [
                'person_id' => sanitize_text_field($personId),
                'old_person_post_id' => (int) $post->ID,
            ];

            if ($hasValidUnivisId) {
                $meta_input['fau_person_faudir_synced'] = $univisid;
            }

            $new_post_id = wp_insert_post([
                'post_type' => $post_type,
                'post_title' => $post->post_title,
                'post_content' => $post->post_content,
                'post_excerpt' => $excerpt,
                'post_status' => 'publish',
                'meta_input' => $meta_input,
            ], true);

            if (is_wp_error($new_post_id)) {
                do_action('rrze.log.error', 'FAUdir\\Migration (importFromFauPerson): wp_insert_post failed for person_id ' . $personId . ': ' . $new_post_id->get_error_message());

                $not_imported_count++;
                $not_imported_reasons[] = '<em>' . $given . ' ' . $family . ' (' . $email . ')</em> ' . __('could not be imported due to an internal error.', 'rrze-faudir');
                continue;
            }

            update_post_meta($new_post_id, Constants::META_LAST_SUCCESS_AT, time());
            update_post_meta($new_post_id, Constants::META_LAST_FAILURE_AT, 0);
            update_post_meta($new_post_id, Constants::META_FAILURE_COUNT, 0);

            $this->migrate_categories((int) $post->ID, (int) $new_post_id, $taxonomy);

            if ($thumbnail_id > 0) {
                set_post_thumbnail((int) $new_post_id, (int) $thumbnail_id);
            }

            $imported_count++;
            $imported_list[] = '<em>' . $given . ' ' . $family . ' (' . $email . ')</em> ' . __('was successfully imported.', 'rrze-faudir');
        }

        return [
            'imported_count' => $imported_count,
            'imported_list' => $imported_list,
            'not_imported_count' => $not_imported_count,
            'not_imported_reasons' => $not_imported_reasons,
        ];
    }

    private function migrate_categories(int $old_post_id, int $new_post_id, string $taxonomy): void {
        if ($old_post_id <= 0 || $new_post_id <= 0 || $taxonomy === '') {
            return;
        }

        $old_categories = wp_get_post_terms($old_post_id, 'persons_category', ['fields' => 'all']);
        if (empty($old_categories) || is_wp_error($old_categories)) {
            return;
        }

        foreach ($old_categories as $old_category) {
            $existing_term = term_exists($old_category->slug, $taxonomy);

            if (!$existing_term) {
                $new_term = wp_insert_term(
                    $old_category->name,
                    $taxonomy,
                    [
                        'description' => $old_category->description,
                        'slug' => $old_category->slug,
                    ]
                );

                if (is_wp_error($new_term)) {
                    do_action('rrze.log.error', 'FAUdir\\Migration (migrate_categories): Could not create term "' . $old_category->name . '" in taxonomy "' . $taxonomy . '": ' . $new_term->get_error_message());
                    continue;
                }

                $term_id = (int) $new_term['term_id'];
            } else {
                $term_id = is_array($existing_term) ? (int) $existing_term['term_id'] : (int) $existing_term;
            }

            if ($term_id > 0) {
                wp_set_object_terms($new_post_id, [$term_id], $taxonomy, true);
            }
        }
    }

    /*
     * Helper Funktion um die Daten von UnivIS zu holen
     */
    private function fetchUnivisPersonData(string $univisid): array|bool|null {
        if (!FaudirUtils::isValidUnivISId($univisid)) {
            return null;
        }

        $url = sprintf(Constants::UNIVIS_PERSON_JSON_URL, rawurlencode($univisid));
        $response = wp_remote_get($url, [
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            do_action('rrze.log.error', 'FAUdir\\Migration (fetchUnivisPersonData): UnivIS request failed for ' . $univisid . ': ' . $response->get_error_message());
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            do_action('rrze.log.error', 'FAUdir\\Migration (fetchUnivisPersonData): UnivIS HTTP ' . $code . ' for ' . $univisid . '.');
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        if ($body === '') {
            do_action('rrze.log.error', 'FAUdir\\Migration (fetchUnivisPersonData): Empty response body for ' . $univisid . '.');
            return false;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['Person']) || !is_array($data['Person'])) {
            do_action('rrze.log.error', 'FAUdir\\Migration (fetchUnivisPersonData): Invalid JSON structure for ' . $univisid . '.');
            return false;
        }

        if (count($data['Person']) === 0) {
            return null;
        }

        if (count($data['Person']) > 1) {
            do_action('rrze.log.warning', 'FAUdir\\Migration (fetchUnivisPersonData): UnivIS result not unique for ' . $univisid . '.');
            return null;
        }

        return is_array($data['Person'][0]) ? $data['Person'][0] : false;
    }
   
    /*
     * Helper um zu prüfen um die gesuchte Person eindeutig in FAUdir findbar ist
     */
    private function findUniqueFaudirPerson(array $params): ?array {
        $api = new API($this->config);
        $resp = $api->getPersons(2, 0, $params);

        if (!is_array($resp) || empty($resp['data']) || !is_array($resp['data'])) {
            return null;
        }

        if (count($resp['data']) !== 1) {
            return null;
        }

        $person = $resp['data'][0] ?? [];
        if (!is_array($person) || empty($person['identifier'])) {
            return null;
        }

        return $person;
    }
}