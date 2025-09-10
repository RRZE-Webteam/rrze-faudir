<?php
namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Filters {
    /**
     * Registriert alle öffentlichen Filter.
     */
    public static function register(): void {
        // Ziel-URL zu einer Person
        add_filter('rrze_faudir_get_target_url', [__CLASS__, 'filter_get_target_url'], 10, 2);

        // Personen-Daten als Array
        add_filter('rrze_faudir_get_person_array', [__CLASS__, 'filter_get_person_array'], 10, 2);
    }

   

    /**
     * Filter: Liefert die Ziel-URL zu einer Person per Identifier.
     * 1) Lokaler CPT (ggf. Canonical-URL) → 2) Person::getTargetURL() → 3) ''.
     */
    public static function filter_get_target_url(string $url, string $identifier): string {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return '';
        }

        try {
            $config    = new Config();
            $opts      = $config->getOptions();
            $post_type = (string) $config->get('person_post_type');

            // 1) Lokaler CPT
            $posts = get_posts([
                'post_type'      => $post_type,
                'meta_key'       => 'person_id',
                'meta_value'     => $identifier,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'numberposts'    => 1,
                'no_found_rows'  => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            ]);

            if (!empty($posts)) {
                $post_id = (int) $posts[0];

                // Option: Canonical statt Permalink?
                $useCanonical = !empty($opts['redirect_to_canonicals']);
                if ($useCanonical) {
                    $canon = (string) get_post_meta($post_id, '_rrze_faudir_canonical_url', true);
                    if ($canon && filter_var($canon, FILTER_VALIDATE_URL)) {
                        return $canon;
                    }
                }

                $permalink = get_permalink($post_id);
                if (is_string($permalink) && $permalink !== '') {
                    return $permalink;
                }
            }

            // 2) Person via API holen und Person::getTargetURL() nutzen
            if (class_exists('\RRZE\FAUdir\Person')) {
                $api = new API($config);
                $res = $api->getPersons(1, 0, ['identifier' => $identifier]);

                if (is_array($res) && !empty($res['data'][0])) {
                    $personArr = $res['data'][0];
                    $person    = new \RRZE\FAUdir\Person($personArr);
                    if (method_exists($person, 'setConfig')) {
                        $person->setConfig($config);
                    }
                    if (method_exists($person, 'getTargetURL')) {
                        $computed = (string) $person->getTargetURL();
                        if ($computed !== '') {
                            return $computed;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            do_action('rrze.log.error', 'FAUdir Filters (filter_get_target_url) error: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Filter: Gibt Personendaten als Array zurück.
     */
    public static function filter_get_person_array(array $data, string $identifier): array {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        try {
            $config = new Config();

            // Person via API holen
            $api = new API($config);
            $res = $api->getPersons(1, 0, ['identifier' => $identifier]);

            if (!is_array($res) || empty($res['data'][0]) || !class_exists('\RRZE\FAUdir\Person')) {
                return $data;
            }

            $personArr = $res['data'][0];
            $person    = new \RRZE\FAUdir\Person($personArr);
            $person->setConfig($config);
            

            // Basisdaten
            $out = method_exists($person, 'toArray') ? (array) $person->toArray() : (array) $personArr;

            // Zusätzliche Werte
            $out['display_name'] = method_exists($person, 'getDisplayName')
                ? (string) $person->getDisplayName(true, false)
                : '';

            $out['target_url'] = method_exists($person, 'getTargetURL')
                ? (string) $person->getTargetURL()
                : '';
            
            $out['image_html'] = method_exists($person, 'getImage')
                ? (string) $person->getImage()
                : '';
            
            $image_id = 0;
            if (method_exists($person, 'getPostId')) {
                $post_id = (int) $person->getPostId();
                if ($post_id > 0) {
                    $thumb_id = get_post_thumbnail_id($post_id);
                    if (!empty($thumb_id)) {
                        $image_id = (int) $thumb_id;
                    }
                }
            }
            if ($image_id > 0) {
                $out['image_id'] = $image_id;
            }

            return $out;
            
        } catch (\Throwable $e) {
            do_action('rrze.log.error', 'FAUdir Filters (filter_get_person_array) error: ' . $e->getMessage());
        }

        return [];
    }

    
    
     /**
     * Öffentliche Helper-API für Entwickler: Ziel-URL abrufen.
     */
    public static function get_target_url(string $identifier): string {
        return (string) apply_filters('rrze_faudir_get_target_url', '', $identifier);
    }
}

/*
 * Öffentliche Convenience-Funktionen  
 */
if (!function_exists('faudir_get_target_url')) {
    function faudir_get_target_url(string $identifier): string {
        return \RRZE\FAUdir\Filters::get_target_url($identifier);
    }
}
if (!function_exists('faudir_get_person_array')) {
    function faudir_get_person_array(string $identifier): array {
        return (array) apply_filters('rrze_faudir_get_person_array', [], $identifier);
    }
}
