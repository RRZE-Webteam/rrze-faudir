<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class FaudirUtils {
    const API_BASE_URL = 'https://api.fau.de/pub/v1/opendir/';

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

    public static function getApiBaseUrl() {
        return self::API_BASE_URL;
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
    

    // Sanitize and format telephone number
    public static function format_phone_number(string $phone): string {
        // Entferne alle Zeichen außer Zahlen, "+", "(", ")", "-" und Leerzeichen
        $phone = preg_replace('/[^\d\+\(\) ]+/', '', $phone);
        $phone = preg_replace('/\s+/', ' ', trim($phone));

        // Falls die Nummer mit "+49(0)" beginnt → zu "+49" umwandeln
        $phone = preg_replace('/^\+49\s*\(0\)/', '+49', $phone);
        $phone = preg_replace('/^0049/', '+49', $phone);

        // Falls die Nummer mit "0" beginnt (deutsche Nummer ohne Ländercode)
        if (preg_match('/^0[1-9]/', $phone)) {
            $phone = preg_replace('/^0/', '+49 ', $phone);
        }

        // Standardisiere das Format mit Leerzeichen zwischen Gruppen
        $phone = preg_replace('/(\+?\d{1,3})\s*(\d{3,4})\s*(\d{2})\s*(\d{0,5})/', '$1 $2 $3 - $4', $phone);

        return trim($phone); // Entfernt überflüssige Leerzeichen am Ende
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

   

}
