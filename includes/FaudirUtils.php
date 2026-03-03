<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class FaudirUtils {
    public static function isUsingNetworkKey(): bool  {
        if (is_multisite()) {
            $settingsOptions = get_site_option('rrze_settings');
            if (!empty($settingsOptions->plugins->faudir_public_apiKey)) {
                return true;
            }
        }
        return false;
    }

    public static function getKey()  {
        if (self::isUsingNetworkKey()) {
            $settingsOptions = get_site_option('rrze_settings');
            return $settingsOptions->plugins->faudir_public_apiKey;
        } else {
            $options = get_option('rrze_faudir_options');
            return isset($options['api_key']) ? $options['api_key'] : '';
        }
    }

   
    
    public static function getLang($full = false) {
        $locale = get_locale();          
        if ($full === true) {
            return $locale;
        }
        $lang = substr($locale, 0, 2);      

        return $lang; 
    }

    public static function lower(string $s): string {
        return \function_exists('\mb_strtolower')
            ? \mb_strtolower($s, 'UTF-8')
            : \strtolower($s);
    }
    
    public static function getDefaultOutputFields() {
        $options = get_option('rrze_faudir_options');
        $default_show_fields = isset($options['default_output_fields']) ? $options['default_output_fields'] : [];

        return array_unique($default_show_fields);
    }

    
    public static function filterContactsByCriteria($contacts, $includeDefaultOrg, $defaultOrgIds, $email)  {
        foreach ($contacts as $contactKey => $contact) {
            $shouldRemove = false;

            // Check organization if includeDefaultOrg is true
            if ($includeDefaultOrg && !in_array($contact['organization']['identifier'], $defaultOrgIds)) {
                $shouldRemove = true;
            }

            // Check email only if an email search parameter was provided
            if (!empty($email)) {
                $contactEmails = $contact['mails'] ?? [];
                if (empty($contactEmails) || !in_array($email, $contactEmails)) {
                    $shouldRemove = true;
                }
            }

            if ($shouldRemove) {
                unset($contacts[$contactKey]);
            }
        }

        return $contacts;
    }
    

    /*
     *  Sanitize and format telephone number (DIN 5008-ish: groups with spaces, extension with hyphen)
     */
    public static function format_phone_number(string $phone): string {
            $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        // +49(0) -> +49
        $phone = preg_replace('/^\+49\s*\(0\)\s*/', '+49 ', $phone);
        // 0049 -> +49
        $phone = preg_replace('/^00\s*49\s*/', '+49 ', $phone);

        // erlaubte Zeichen: Ziffern, +, Leerzeichen, Klammern, Slash, Punkt, Bindestrich
        $phone = preg_replace('/[^\d\+\s\(\)\/\.\-]+/u', '', $phone);
        $phone = preg_replace('/\s+/u', ' ', $phone);

        // deutsche Nummer ohne Ländercode: 0... -> +49 ...
        if (preg_match('/^0[1-9]/', $phone)) {
            $phone = preg_replace('/^0+/', '', $phone);
            $phone = '+49 ' . $phone;
        }

        // wenn +49 direkt an Ziffern klebt: +499131... -> +49 9131...
        $phone = preg_replace('/^\+49\s*/', '+49 ', $phone);

        // Trennzeichen normalisieren:
        // - Slash & Punkt werden zu Leerzeichen
        $phone = str_replace(['/', '.'], ' ', $phone);

        // - Klammern raus (DIN 5008: keine "(0)" im internationalen Format)
        $phone = str_replace(['(', ')'], '', $phone);

        // - Bindestrich: keine Spaces drumrum
        $phone = preg_replace('/\s*-\s*/', '-', $phone);

        // doppelte Leerzeichen killen
        $phone = preg_replace('/\s+/u', ' ', trim($phone));

        return $phone;
    }


  /**
    * Normalisiert/klassifiziert einen akademischen Titel-String (honorificPrefix).
    * Optionale Eingaben: keine.
    * Rückgabe: array{
    *   key: string,                // kanonischer Titel-Key aus Config (z. B. 'Prof. Dr.')
    *   label: string,              // langer Label-Text aus Config
    *   sortorder: int,             // Sortierwert aus Config
    *   visible-title: string,      // sichtbare Titel-Variante inkl. 'apl' vorn, Disziplinen, sowie 'habil'/'h.c.' hinten
    *   visible_title_no_discipline: string // wie visible-title, aber OHNE Disziplinen und OHNE 'apl' vorn
    * }
    */
   public static function normalizeAcademicTitle(string $honorificPrefix): array {
       $orig = trim($honorificPrefix);

       // Frühexit – kein Titel
       if ($orig === '') {
           return [
               'key'                       => '',
               'label'                     => '',
               'sortorder'                 => PHP_INT_MAX,
               'visible-title'             => '',
               'visible_title_no_discipline' => '',
           ];
       }
        // Config-Daten holen
        $cfg          = new Config();
        $prefixMap   = $cfg->getAcademicPrefixes();            // ['Prof. Dr.' => ['label'=>..., 'sortorder'=>..., 'aliases'=>[...]], ...]
        $ignore      = $cfg->getAcademicIgnoreTokens();   // ['univ','univ.'] etc.

       $s = ' ' . $orig . ' ';
       // Vereinheitliche Leerzeichen
       $s = preg_replace('/\s+/', ' ', $s);

       // Entferne ignorierte Tokens (auch mit optionalem Bindestrich und optionalem Punkt), z. B. "univ", "univ."
       if (!empty($ignore)) {
           $ignorePattern = implode('|', array_map(
               static fn($t) => preg_quote($t, '/'),
               $ignore
           ));
           // Vorkommen als eigenständiges Wort ODER direkt nach Dr./Prof. mit Bindestrich: "Dr.-univ." etc.
           $s = preg_replace('/(?<=\b|\.|-)\s*(?:' . $ignorePattern . ')\b\.?/iu', '', $s);
           $s = preg_replace('/\s+/', ' ', $s);
       }

       // Flags für apl, habil, h.c.
       $hasApl   = false;  // vorn stehend
       $hasHabil = false;  // hinten
       $hasHC    = false;  // hinten

       // apl (am Wortanfang, optional Punkt), auch vorangestellt mit Leerzeichen/Komma
       if (preg_match('/^\s*apl\.?\s+/iu', $s)) {
           $hasApl = true;
           $s = preg_replace('/^\s*apl\.?\s+/iu', ' ', $s);
       }

       // Disziplinen nach Dr./Titel: Sequenzen wie "med.", "phil.", "nat." (mehrere möglich)
       // Wir sammeln sie, aber verwenden sie nur für visible-title (nicht für *_no_discipline).
       $disciplines = [];
       $s = preg_replace_callback('/\b([a-z]{2,}\.)\b/iu', function($m) use (&$disciplines) {
           $disciplines[] = $m[1];
           return ' ';
       }, $s);
       $s = preg_replace('/\s+/', ' ', $s);

       // „habil“ und „h.c.“ (varianten inkl. Bindestrich nach Dr./Prof.)
       if (preg_match('/(?:^|\s|\.|-)(habil)\b\.?/iu', $s)) {
           $hasHabil = true;
           $s = preg_replace('/(?:^|\s|\.|-)(habil)\b\.?/iu', ' ', $s);
           $s = preg_replace('/\s+/', ' ', $s);
       }
       if (preg_match('/(?:^|\s|\.|-)(h\.c\.)\b/iu', $s)) {
           $hasHC = true;
           $s = preg_replace('/(?:^|\s|\.|-)(h\.c\.)\b/iu', ' ', $s);
           $s = preg_replace('/\s+/', ' ', $s);
       }

       // Mehrfache Dr./Prof. erkennen → „mult.“-Variante
       $drCount   = preg_match_all('/\bdr\.?\b/iu',  $s);
       $profCount = preg_match_all('/\bprof\.?\b/iu', $s);

       // Kerntitel extrahieren (nur Dr/Prof Kombinationen berücksichtigen)
       // Normalisieren: "prof dr", "prof. dr.", "dr.", "prof." → in feste Key-Form bringen
       $core = strtolower(trim($s));

       // Versuche Alias-Mapping aus Config
       $finalKey = '';
       $label    = '';
       $order    = PHP_INT_MAX;

       // 1) Direkter Treffer auf Keys
       foreach ($prefixMap as $key => $meta) {
           if (strcasecmp($core, strtolower($key)) === 0) {
               $finalKey = $key;
               $label    = $meta['label']     ?? '';
               $order    = (int)($meta['sortorder'] ?? PHP_INT_MAX);
               break;
           }
       }

       // 2) Prüfe Aliasse, wenn kein direkter Key
       if ($finalKey === '') {
           foreach ($prefixMap as $key => $meta) {
               $aliases = array_map('strval', (array)($meta['aliases'] ?? []));
               foreach ($aliases as $alias) {
                   if (strcasecmp($core, strtolower($alias)) === 0) {
                       $finalKey = $key;
                       $label    = $meta['label']     ?? '';
                       $order    = (int)($meta['sortorder'] ?? PHP_INT_MAX);
                       break 2;
                   }
               }
           }
       }

       // 3) Heuristik falls immer noch nichts gefunden (z. B. freie Reihenfolge)
       if ($finalKey === '') {
           // Dr+Prof
           if ($profCount > 0 && $drCount > 0) {
               $finalKey = ($profCount > 1 || $drCount > 1) ? 'Prof. Dr. mult.' : 'Prof. Dr.';
           } elseif ($profCount > 0) {
               $finalKey = ($profCount > 1) ? 'Prof. mult.' : 'Prof.';
           } elseif ($drCount > 0) {
               $finalKey = ($drCount > 1) ? 'Dr. mult.' : 'Dr.';
           } else {
               $finalKey = ''; // kein bekannter Titel
           }

           if ($finalKey !== '' && isset($prefixMap[$finalKey])) {
               $label = $prefixMap[$finalKey]['label'] ?? '';
               $order = (int)($prefixMap[$finalKey]['sortorder'] ?? PHP_INT_MAX);
           }
       }

       // --- Sichtbare Varianten bauen ---
       // mit Disziplinen & ggf. apl vorn
       $visible = trim($finalKey);
       if ($hasHabil) { $visible .= ($visible ? ' ' : '') . 'habil'; }
       if ($hasHC)    { $visible .= ($visible ? ' ' : '') . 'h.c.';  }
       if (!empty($disciplines)) {
           $visible .= ($visible ? ' ' : '') . implode(' ', $disciplines);
       }
       if ($hasApl) {
           $visible = 'apl ' . $visible;
       }

       // ohne Disziplinen und EXPLIZIT OHNE apl vorn (nur Titel + evtl. „habil“/„h.c.“)
       $visibleNoDisc = trim($finalKey);
       if ($hasHabil) { $visibleNoDisc .= ($visibleNoDisc ? ' ' : '') . 'habil'; }
       if ($hasHC)    { $visibleNoDisc .= ($visibleNoDisc ? ' ' : '') . 'h.c.';  }
       // KEIN apl-Präfix hier!

       return [
           'key'                         => $finalKey,
           'label'                       => $label,
           'sortorder'                   => $order,
           'visible-title'               => trim($visible),
           'visible_title_no_discipline' => trim($visibleNoDisc),
       ];
   }
   /**
    * Sanitizer für das Shortcode-Attribut "order".
    * - Eingabe: "asc", "desc" oder kommasepariert (z.B. "asc, desc, ASC")
    * - Filtert ungültige Tokens, normalisiert auf Kleinbuchstaben
    * - Rückgabe: kommaseparierter String (mind. "asc")
    * Optionale Eingaben: $orderRaw (string|array|null)
    * Rückgabe: string
    */
   public static function sanitizeOrderString(mixed $orderRaw): string {
       // In String umwandeln (Array → kommasepariert)
       $raw = is_array($orderRaw) ? implode(',', array_map('strval', $orderRaw)) : (string) $orderRaw;

       // Tokens herausziehen
       $parts = preg_split('/\s*,\s*/', sanitize_text_field($raw), -1, PREG_SPLIT_NO_EMPTY);

       // Nur erlaubte Werte behalten
       $allowed = ['asc' => true, 'desc' => true];
       $clean   = [];

       foreach ($parts ?: [] as $p) {
           $p = strtolower(trim($p));
           if (isset($allowed[$p])) {
               $clean[] = $p;
           }
       }

       // Fallback
       if (empty($clean)) {
           $clean[] = 'asc';
       }

       // Als String zurückgeben (mit Komma+Leerzeichen für Lesbarkeit)
       return implode(', ', $clean);
   }

   /**
    * Streckt einen (bereits sanierten) Order-String auf die Länge der Sort-Keys.
    * Beispiel:
    *   sort="title, familyName", order="asc"       → "asc, asc"
    *   sort="title, familyName, email", "asc,desc" → "asc, desc, desc"
    * Optionale Eingaben: keine (außer Parametern)
    * Rückgabe: string (kommasepariert)
    */
   public static function expandOrderStringForSort(string $orderStr, string $sortStr): string {
       // Sort-Keys zerlegen
       $sortKeys = preg_split('/\s*,\s*/', (string) $sortStr, -1, PREG_SPLIT_NO_EMPTY) ?: [];

       // Order-String sanitizen und zerlegen
       $sanitizedOrder = self::sanitizeOrderString($orderStr);
       $orders = preg_split('/\s*,\s*/', $sanitizedOrder, -1, PREG_SPLIT_NO_EMPTY);

       if (empty($sortKeys)) {
           // Keine Sort-Keys → gebe den (sanierten) Order-String zurück
           return $sanitizedOrder;
       }

       // Auf Länge der Sort-Keys strecken
       $out   = [];
       $last  = 'asc';
       foreach ($sortKeys as $i => $_) {
           $val = $orders[$i] ?? (end($orders) ?: 'asc');
           $val = ($val === 'desc') ? 'desc' : 'asc';
           $out[] = $val;
           $last  = $val;
       }

       return implode(', ', $out);
   }

   /*
    * Wandelt eine kommaseparierte Zeichenkette in ein bereinigtes Array um.
    *
    * - Trennt an Kommas
    * - Entfernt führende und nachgestellte Leerzeichen
    * - Entfernt leere Einträge
    *
    * @param string $csv Kommaseparierte Liste (z. B. "a, b, c")
    * @return array Bereinigtes Array von Strings
    */
   public static function csvToArray(string $csv): array {
        $csv = trim($csv);
        if ($csv === '') {
            return [];
        }

        $parts = explode(',', $csv);
        $out = [];

        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return $out;
    }
    
    /*
     * Normalisiert ein Array von Strings.
     *
     * - Castet Werte zu String
     * - Entfernt führende und nachgestellte Leerzeichen
     * - Entfernt leere Einträge
     * - Entfernt doppelte Werte (Reihenfolge bleibt erhalten)
     *
     * @param array $values Eingabearray mit beliebigen Werten
     * @return array Bereinigtes Array eindeutiger Strings
     */
    public static function normalizeStringArray(array $values): array {
        $out = [];

        foreach ($values as $v) {
            $v = trim((string) $v);
            if ($v !== '') {
                $out[] = $v;
            }
        }

        return array_values(array_unique($out));
    }

    
    /**
    * Normalisiert ein Feld (string|array|null) zu einem Array von Strings.
    * Entfernt Leerwerte und trimmt Einträge.
    */
   public static function normalizeScalarOrArrayToList($value): array {
       $out = [];

       if (is_string($value)) {
           $value = trim($value);
           if ($value !== '') {
               $out[] = $value;
           }
           return $out;
       }

       if (!is_array($value)) {
           return [];
       }

       foreach ($value as $v) {
           if (!is_string($v)) {
               continue;
           }
           $v = trim($v);
           if ($v === '') {
               continue;
           }
           $out[] = $v;
       }

       return $out;
   }

   /**
    * Prüft grob, ob eine Telefonnummer/Faxnummer valide aussieht.
    */
   public static function isValidPhoneNumber(string $val): bool {
       return (bool) preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $val);
   }
   /**
    * Prüft, ob eine E-Mail-Adresse valide ist.
    */
   public static function isValidEmailAddress(string $val): bool {
       return (bool) filter_var($val, FILTER_VALIDATE_EMAIL);
   }

   /*
    * Prüfe FAUdir personId auf validität: 10 oder 11 Zeichen aus [a-z0-9].
    */
   public static function isValidPersonId(string $input): bool {
        $input = strtolower(trim($input));
        return (bool) preg_match('/^[a-z0-9]{10,11}$/', $input);
    }

    public static function sanitizePersonId(string $input): ?string {
        $clean = strtolower(trim($input));
        $clean = preg_replace('/[^a-z0-9]/', '', $clean);

        if (preg_match('/^[a-z0-9]{10,11}$/', $clean)) {
            return $clean;
        }

        return null;
    }
    
    /*
    * Prüft, ob eine UnivIS ID gültig ist.
    * Erlaubt nur 0-9 und mindestens 4, aber maximal 10 Zeichen.
    */
   public static function isValidUnivISId(string $input): bool {
       $input = trim($input);
       return (bool) preg_match('/^[0-9]{4,10}$/', $input);
   }

   /*
    * Sanitized eine UnivIS-ID.
    * Entfernt alle Nicht-Ziffern und kürzt auf maximal 10 Stellen.
    */
   public static function sanitizeUnivISId(string $input): string {
       $input = trim($input);

       // nur Ziffern erlauben
       $input = preg_replace('/[^0-9]/', '', $input);

       // auf max. 10 Zeichen begrenzen
       $input = substr($input, 0, 10);

       return $input;
   }
    
    /*
    * Prüft, ob eine FAUdir Contact-ID gültig ist.
    * Erlaubt nur a-z0-9 und exakt 10 Zeichen.
    */
   public static function isValidContactId(string $input): bool {
       $input = strtolower(trim($input));
       return (bool) preg_match('/^[a-z0-9]{10}$/', $input);
   }

   /*
    * Sanitized eine Contact-ID.
    * Entfernt ungültige Zeichen und gibt null zurück,
    * wenn das Ergebnis nicht exakt 10 Zeichen a-z0-9 ist.
    */
   public static function sanitizeContactId(string $input): ?string {
       $clean = strtolower(trim($input));
       $clean = preg_replace('/[^a-z0-9]/', '', $clean);

       if (preg_match('/^[a-z0-9]{10}$/', $clean)) {
           return $clean;
       }

       return null;
   }
    
    /*
    * Prüft, ob eine FAUdir Organization-ID gültig ist.
    * Erlaubt nur a-z0-9 und exakt 10 Zeichen.
    */
   public static function isValidOrganizationId(string $input): bool {
       $input = strtolower(trim($input));
       return (bool) preg_match('/^[a-z0-9]{10}$/', $input);
   }

   /*
    * Sanitized eine Organization-ID.
    * Entfernt ungültige Zeichen und gibt null zurück,
    * wenn das Ergebnis nicht exakt 10 Zeichen a-z0-9 ist.
    */
   public static function sanitizeOrganizationId(string $input): ?string {
       $clean = strtolower(trim($input));
       $clean = preg_replace('/[^a-z0-9]/', '', $clean);

       if (preg_match('/^[a-z0-9]{10}$/', $clean)) {
           return $clean;
       }

       return null;
   }

   /*
    * Prüft, ob eine Orgnr (= FAU Kostenstellennummer) gültig ist. 
    * Erlaubt exakt 10 Ziffern (0-9).
    */
   public static function isValidOrgnr(string $input): bool {
       $input = trim($input);
       return (bool) preg_match('/^\d{10}$/', $input);
   }

   /**
    * Sanitized eine Orgnr.
    * Entfernt alle Nicht-Ziffern und gibt null zurück,
    * wenn das Ergebnis nicht exakt 10 Ziffern enthält.
    */
   public static function sanitizeOrgnr(string $input): ?string {
       $clean = preg_replace('/\D/', '', trim($input));

       if (preg_match('/^\d{10}$/', $clean)) {
           return $clean;
       }

       return null;
   }
   
    /*
     * Prüft, ob eine Orgnr-Prefixsuche zulässig ist (6 bis 9 Ziffern).
     */
    public static function isValidOrgnrPrefix(string $input): bool {
        $input = trim($input);
        return (bool) preg_match('/^\d{6,9}$/', $input);
    }

    /*
     * Sanitized eine Orgnr-Prefixsuche (6 bis 9 Ziffern) und gibt null zurück wenn ungültig.
     */
    public static function sanitizeOrgnrPrefix(string $input): ?string {
        $clean = preg_replace('/\D+/', '', trim($input));

        if (preg_match('/^\d{6,9}$/', $clean)) {
            return $clean;
        }

        return null;
    }   
    
    
   /*
    * Sanitizes an HTML wrapper tag name for list/fragment outputs.
    * Allows only a small whitelist to prevent invalid markup and injection.
    */
   public static function sanitizeHtmlSurround(string $tag, string $default = 'div'): string {
       $allowed = ['div', 'span', 'nav', 'p'];

       $tag = strtolower(trim($tag));
       if ($tag === '') {
           return $default;
       }

       return in_array($tag, $allowed, true) ? $tag : $default;
   }
   
   /*
    * Normalize separators for textarea usage.
    * Allows "\n", "\n\n", " ", and returns default otherwise.
    */
   public static function normalizeTextareaSeparator(string $sep, string $default): string {
       $sep = str_replace("\r\n", "\n", $sep);
       if ($sep === "\n" || $sep === "\n\n" || $sep === ' ') {
           return $sep;
       }
       return $default;
   }
   
       /**
     * Normalize socials data into a stable list of items:
     * [
     *   ['platform' => 'X', 'url' => 'https://...'],
     *   ...
     * ]
     * - trims values
     * - drops empty/invalid entries
     * - de-duplicates (platform+url)
     * - stable sort by platform, then url
     */
    public static function normalizeSocialItems(array $socials): array {
        $out = [];
        $seen = [];

        foreach ($socials as $item) {
            if (!is_array($item)) {
                continue;
            }

            $platform = '';
            if (isset($item['platform'])) {
                $platform = trim((string) $item['platform']);
            }

            $url = '';
            if (isset($item['url'])) {
                $url = trim((string) $item['url']);
            }

            if ($platform === '' || $url === '') {
                continue;
            }

            $key = strtolower($platform) . '|' . strtolower($url);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $out[] = [
                'platform' => $platform,
                'url' => $url,
            ];
        }

        usort($out, [self::class, 'compareSocialItems']);

        return $out;
    }

    private static function compareSocialItems(array $a, array $b): int {
        $ap = isset($a['platform']) ? (string) $a['platform'] : '';
        $bp = isset($b['platform']) ? (string) $b['platform'] : '';

        $pc = strcasecmp($ap, $bp);
        if ($pc !== 0) {
            return $pc;
        }

        $au = isset($a['url']) ? (string) $a['url'] : '';
        $bu = isset($b['url']) ? (string) $b['url'] : '';

        return strcasecmp($au, $bu);
    }

    /**
     * Render socials list as semantic HTML (<ul>).
     * Input must be normalized items (see normalizeSocialItems()).
     */
    public static function renderSocialMediaList(array $items, string $htmlsurround = 'div', string $class = 'icon-list icon', string $arialabel = ''): string {
        if (empty($items)) {
            return '';
        }

        $htmlsurround = self::sanitizeHtmlSurround($htmlsurround);

        $out = '<' . $htmlsurround;

        $arialabel = trim($arialabel);
        if ($arialabel !== '') {
            $out .= ' aria-label="' . esc_attr($arialabel) . '"';
        }

        $class = trim($class);
        if ($class !== '') {
            $out .= ' class="' . esc_attr($class) . '"';
        }

        $out .= '>';
        $out .= '<ul class="list-icons">';

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = isset($item['platform']) ? (string) $item['platform'] : '';
            $value = isset($item['url']) ? (string) $item['url'] : '';

            $name = trim($name);
            $value = trim($value);

            if ($name === '' || $value === '') {
                continue;
            }

            $label = esc_html($name);

            if (preg_match('/^https?:\/\//i', $value)) {
                $display = self::prettyUrl($value);
                $formatted = '<a href="' . esc_url($value) . '" itemprop="sameAs">' . esc_html($display) . '</a>';
                $out .= '<li><span class="website title">' . $label . ': </span>' . $formatted . '</li>';
                continue;
            }

            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $formatted = '<a itemprop="email" href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                $out .= '<li><span class="email title">' . $label . ': </span>' . $formatted . '</li>';
                continue;
            }

            $out .= '<li><span class="title">' . $label . ': </span><span class="value">' . esc_html($value) . '</span></li>';
        }

        $out .= '</ul>';
        $out .= '</' . $htmlsurround . '>';

        return $out;
    }

    /**
     * Render socials as plain text lines for textarea/log.
     * "Platform: URL"
     */
    public static function renderSocialMediaText(array $items, string $lineSeparator = "\n"): string {
        if (empty($items)) {
            return '';
        }

        $lines = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $p = isset($item['platform']) ? trim((string) $item['platform']) : '';
            $u = isset($item['url']) ? trim((string) $item['url']) : '';

            if ($p === '' || $u === '') {
                continue;
            }

            $lines[] = $p . ': ' . $u;
        }

        $lines = self::normalizeStringArray($lines);

        return implode($lineSeparator, $lines);
    }

    private static function prettyUrl(string $url): string {
        $p = wp_parse_url($url);
        if (is_array($p) && !empty($p['host'])) {
            $host = (string) $p['host'];
            $path = !empty($p['path']) ? (string) $p['path'] : '';
            return $host . $path;
        }

        return preg_replace('/^https?:\/\//i', '', $url);
    }
   
    
    /*
     * Prüfung ob FAU Person aktiv ist
     */
    public static function isFauPersonActive(): bool {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (is_multisite() && function_exists('is_plugin_active_for_network')) {
            if (is_plugin_active_for_network('fau-person/fau-person.php')) {
                return true;
            }
        }

        return is_plugin_active('fau-person/fau-person.php');
    }
}
