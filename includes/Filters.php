<?php
namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Filters {
    /**
     * Registriert alle öffentlichen Filter.
     */
    public static function register(): void {
        // Ziel-URL zu einer Person (Identifier rein, URL raus)
        add_filter('rrze_faudir_get_target_url', [__CLASS__, 'filter_get_target_url'], 10, 2);

        // Personen-Daten als Array (Identifier rein, Array raus)
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
     * Bevorzugt Person-Klasse (getPersonbyAPI + toArray),
     * ergänzt um 'display_name' und 'target_url'.
     */
    public static function filter_get_person_array(array $data, string $identifier): array {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        try {
            $config = new Config();

            // Instanz-Variante
            if (class_exists('\RRZE\FAUdir\Person')) {
                $person = new \RRZE\FAUdir\Person([]);
                if (method_exists($person, 'setConfig')) {
                    $person->setConfig($config);
                }
                if (method_exists($person, 'getPersonbyAPI')) {
                    $ok = $person->getPersonbyAPI($identifier);
                    if ($ok !== false && method_exists($person, 'toArray')) {
                        $arr = (array) $person->toArray();
                        return self::enrich_person_array($arr, $person, $config, $identifier);
                    }
                }

                // Statische Variante als Fallback
                if (is_callable(['\RRZE\FAUdir\Person', 'getPersonbyAPI'])) {
                    $p = \RRZE\FAUdir\Person::getPersonbyAPI($identifier);
                    if (is_object($p) && method_exists($p, 'toArray')) {
                        $arr = (array) $p->toArray();
                        return self::enrich_person_array($arr, $p, $config, $identifier);
                    }
                    if (is_array($p)) {
                        return self::enrich_person_array($p, null, $config, $identifier);
                    }
                }
            }

            // Reine API-Daten
            $api = new API($config);
            $res = $api->getPersons(1, 0, ['identifier' => $identifier]);
            if (is_array($res) && !empty($res['data'][0]) && is_array($res['data'][0])) {
                return self::enrich_person_array($res['data'][0], null, $config, $identifier);
            }
        } catch (\Throwable $e) {
            do_action('rrze.log.error', 'FAUdir Filters (filter_get_person_array) error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Ergänzt das Personen-Array um display_name und target_url.
     */
    private static function enrich_person_array(array $arr, $personOrNull, Config $config, string $identifier): array {
        // display_name
        if (is_object($personOrNull) && method_exists($personOrNull, 'getDisplayName')) {
            $arr['display_name'] = (string) $personOrNull->getDisplayName(true, false);
        } else {
            $prefix = isset($arr['honorificPrefix']) ? trim((string) $arr['honorificPrefix']) : '';
            $gn     = isset($arr['givenName']) ? trim((string) $arr['givenName']) : '';
            $fn     = isset($arr['familyName']) ? trim((string) $arr['familyName']) : '';
            $arr['display_name'] = trim(($prefix ? $prefix . ' ' : '') . $gn . ' ' . $fn);
        }

        // target_url
        if (is_object($personOrNull) && method_exists($personOrNull, 'getTargetURL')) {
            $arr['target_url'] = (string) $personOrNull->getTargetURL();
        } else {
            $arr['target_url'] = self::get_target_url($identifier);
        }

        return $arr;
    }
    
     /**
     * Öffentliche Helper-API für Entwickler: Ziel-URL abrufen.
     * apply_filters-Kürzel, falls jemand lieber Funktion als Filter nutzt.
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
