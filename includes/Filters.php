<?php
 /**
 * Registriert alle öffentlichen Filter.
 */

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;
    
class Filters {

    /** Gesammelte Copyright-Einträge (pro Request). */
    private static array $copyrightEntries = [];
    /** Cache für den gewählten Filter-Tag. */
    private static ?string $copyrightFilterTag = null;

    
    public static function register(): void {
        // Ziel-URL zu einer Person
        add_filter('rrze_faudir_get_target_url', [__CLASS__, 'filter_get_target_url'], 10, 2);

        // Personen-Daten als Array
        add_filter('rrze_faudir_get_person_array', [__CLASS__, 'filter_get_person_array'], 10, 2);
        
        
        $tag = self::getCopyrightFilterTag();
        if ($tag) {
            // Theme ruft z.B. apply_filters('fau_copyright_info', [], $args) auf
            add_filter($tag, [__CLASS__, 'collectCopyrightInfo'], 10, 2);
        }
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
    
    /**
     * Von überall im Plugin aufrufen, um einen Eintrag hinzuzufügen.
     * @param string $text      Copyright-Text (wird getrimmt/gestrippt)
     * @param int    $image_id  Attachment-ID (0, wenn unbekannt)
     */
    public static function pushCopyright(string $text, int $image_id = 0): void {
        $text = trim(wp_strip_all_tags($text));
        if ($text === '') {
            return;
        }
        self::$copyrightEntries[] = [
            'text'     => $text,
            'image_id' => max(0, (int) $image_id),
        ];
    }

    /**
     * Callback für den Theme-Filter:
     * Merged unsere gesammelten Einträge in die vom Theme übergebenen $entries.
     *
     * @param mixed $entries Array (oder gemischt), das vom Theme übergeben wird
     * @param mixed $args    optionale Zusatzargumente
     * @return array         Normalisiertes Array von ['text' => string, 'image_id' => int]
     */
    public static function collectCopyrightInfo($entries, $args = []): array {
        $entriesArr = is_array($entries) ? $entries : [$entries];

        // Unsere Einträge anhängen
        if (!empty(self::$copyrightEntries)) {
            $entriesArr = array_merge($entriesArr, self::$copyrightEntries);
        }

        // Normalisieren: alles auf ['text' => string, 'image_id' => int] bringen
        $normalized = [];
        foreach ($entriesArr as $e) {
            if (is_array($e)) {
                $text   = isset($e['text']) ? trim((string) $e['text']) : '';
                $imgId  = isset($e['image_id']) ? (int) $e['image_id'] : 0;
                if ($text !== '') {
                    $normalized[] = ['text' => $text, 'image_id' => max(0, $imgId)];
                }
            } elseif (is_scalar($e)) {
                $text = trim((string) $e);
                if ($text !== '') {
                    $normalized[] = ['text' => $text, 'image_id' => 0];
                }
            }
        }

        // Deduplizieren nach text+image_id (case-insensitive für Text)
        $seen = [];
        $out  = [];
        foreach ($normalized as $item) {
            $key = strtolower($item['text']) . '|' . (string) $item['image_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Ermittelt den passenden Filter-Tag anhand des aktiven Themes.
     * - FAU-Elemental → 'fau_elemental_copyright_info'
     * - andere FAU-* Themes → 'fau_copyright_info'
     * - sonst → null (wir registrieren dann keinen Filter)
     */
    private static function getCopyrightFilterTag(): ?string  {
        if (self::$copyrightFilterTag !== null) {
            return self::$copyrightFilterTag;
        }

        $theme      = function_exists('wp_get_theme') ? wp_get_theme() : null;
        $name       = $theme ? (string) $theme->get('Name') : '';
        $stylesheet = (string) get_option('stylesheet');
        $template   = (string) get_option('template');

        // Elemental prüfen
        $isElemental =
            stripos($name, 'FAU-Elemental') !== false
            || $stylesheet === 'fau-elemental'
            || $template === 'fau-elemental';

        if ($isElemental) {
            self::$copyrightFilterTag = 'fau_elemental_copyright_info';
            return self::$copyrightFilterTag;
        }

        // Andere FAU-Themes (Name oder Stylesheet/Template beginnt mit 'fau'/'FAU')
        $isOtherFAU =
            preg_match('/^fau[\s-]?/i', $stylesheet)
            || preg_match('/^fau[\s-]?/i', $template)
            || preg_match('/^fau/i', $name);

        if ($isOtherFAU) {
            self::$copyrightFilterTag = 'fau_copyright_info';
            return self::$copyrightFilterTag;
        }

        // Kein FAU-Theme → keinen Filter registrieren
        self::$copyrightFilterTag = null;
        return null;
    }

    /**
     * Optional: für Debug/Tests – gibt den aktuell aktiven Copyright-Filter-Tag zurück.
     */
    public static function getActiveCopyrightFilterTag(): ?string {
        return self::getCopyrightFilterTag();
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
