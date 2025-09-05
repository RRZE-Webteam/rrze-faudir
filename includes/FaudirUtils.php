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
        $phone = preg_replace('/[^\d\+\-\(\) ]/', '', $phone);
        $phone = preg_replace('/\s+/', ' ', trim($phone));

        // Falls die Nummer mit "+49(0)" beginnt → zu "+49" umwandeln
        $phone = preg_replace('/^\+49\s*\(0\)/', '+49', $phone);
        $phone = preg_replace('/^0049/', '+49', $phone);

        // Falls die Nummer mit "0" beginnt (deutsche Nummer ohne Ländercode)
        if (preg_match('/^0[1-9]/', $phone)) {
            $phone = preg_replace('/^0/', '+49 ', $phone);
        }

        // Standardisiere das Format mit Leerzeichen zwischen Gruppen
        $phone = preg_replace('/(\+?\d{1,3})\s*(\d{3,4})\s*(\d{2,4})\s*(\d{0,5})/', '$1 $2 $3 $4', $phone);

        return trim($phone); // Entfernt überflüssige Leerzeichen am Ende
    }

    

    /**
     * Normalisiert einen akademischen Titel aus honorificPrefix.
     * - Entfernt "Noise"-Tokens (inkl. Fällen wie "Dr.-univ.")
     * - Erfasst APL ("apl.") → wird vorne angezeigt (nicht sortierrelevant)
     * - Erfasst "habil" und "h.c." → werden hinter dem Titel angezeigt (nicht sortierrelevant)
     * - Erfasst Disziplin-Abkürzungen nach "Dr." (z. B. "med.", "phil.", "nat.") → nur Anzeige, nicht Sortierung
     * - Ermittelt Mapping-Key, Label und Sortorder aus Config::getAcademicPrefixes()
     * Rückgabe:
     *   [
     *     'key'                      => string,
     *     'label'                    => string,
     *     'sortorder'                => int,
     *     'visible_title'            => string, // inkl. APL/habil/h.c. und Disziplinen
     *     'visible_title_no_discipline' => string, // wie oben, aber ohne Disziplinen
     *   ]
     */
    public static function normalizeAcademicTitle(?string $honorificPrefix): array {
        $result = [
            'key'                          => '',
            'label'                        => '',
            'sortorder'                    => PHP_INT_MAX,
            'visible_title'                => '',
            'visible_title_no_discipline'  => '',
        ];

        $raw = trim((string) $honorificPrefix);
        if ($raw === '') {
            return $result;
        }

        // Config-Daten holen
        $cfg          = new Config();
        $prefixMap    = $cfg->getAcademicPrefixes();      // ['Prof. Dr.' => ['label'=>..., 'sortorder'=>..., 'aliases'=>['prof dr', ...]], ...]
        $ignoreTokens = $cfg->getAcademicIgnoreTokens();   // z. B. ['univ','univ.']

        // Aliases → Key-Lookup bauen (alles kleingeschrieben, ohne überflüssige Leerzeichen)
        $aliasToKey = [];
        foreach ($prefixMap as $key => $meta) {
            if (!empty($meta['aliases']) && is_array($meta['aliases'])) {
                foreach ($meta['aliases'] as $alias) {
                    $aliasNorm = trim(strtolower(preg_replace('/\s+/', ' ', $alias)));
                    $aliasToKey[$aliasNorm] = $key;
                }
            }
            // den Key selbst auch als Alias zulassen
            $selfKeyNorm = trim(strtolower(preg_replace('/\s+/', ' ', preg_replace('/\./', '', $key))));
            $aliasToKey[$selfKeyNorm] = $key;
        }

        // Flags für Zusatzkennzeichen
        $hasApl   = (bool) preg_match('/\bapl\.?\b/i', $raw);
        $hasHabil = (bool) preg_match('/(?:^|[\s\-])habil\.?(?=$|[\s\-])/i', $raw);
        $hasHC    = (bool) preg_match('/(?:^|[\s\-])h\.?c\.?(?=$|[\s\-])/i', $raw);

        // Disziplinen nach "Dr." einsammeln (z. B. "med.", "phil.", "nat.", ggf. mehrere)
        // Behalte sie für die Anzeige, entferne sie für die Normalisierung.
        $disciplines = [];
        if (preg_match_all('/Dr\.?\s*(?:-)?\s*((?:[a-z]{2,}\.\s*)+)/i', $raw, $discMatches)) {
            foreach ($discMatches[1] as $grp) {
                preg_match_all('/[a-z]{2,}\./i', $grp, $tok);
                foreach ($tok[0] as $abbr) {
                    $disciplines[] = trim($abbr);
                }
            }
        }

        // Arbeitsstring erstellen: entferne apl/habil/h.c. und Disziplinen (nur für Matching)
        $work = $raw;
        $work = preg_replace('/\bapl\.?\b/i', ' ', $work);
        $work = preg_replace('/(?:^|[\s\-])habil\.?(?=$|[\s\-])/i', ' ', $work);
        $work = preg_replace('/(?:^|[\s\-])h\.?c\.?(?=$|[\s\-])/i', ' ', $work);
        // Dr. + Disziplinen → zu reinem "Dr."
        $work = preg_replace('/Dr\.?\s*(?:-)?\s*(?:[a-z]{2,}\.\s*)+/i', 'Dr.', $work);

        // IGNORE-Tokens entfernen:
        //   1) Stand-alone:  (^|space|-) token (. optional) ($|space|-)
        //   2) Spezieller Fall "Dr.-token" / "Prof.-token" → auf "Dr." / "Prof." zurückführen
        if (!empty($ignoreTokens)) {
            foreach ($ignoreTokens as $tok) {
                $tokCore  = rtrim((string) $tok, '.');
                if ($tokCore === '') { continue; }
                $tokRegex = preg_quote($tokCore, '/');

                // Speziell gebundene Form: Dr.-univ / Prof.-univ.
                $work = preg_replace('/\b(Dr\.?|Prof\.?)\s*-\s*' . $tokRegex . '\.?/i', '$1', $work);

                // Allgemeine Formen mit Leer- oder Bindestrichgrenzen
                $work = preg_replace('/(?:^|[\s\-])' . $tokRegex . '\.?(?=$|[\s\-])/i', ' ', $work);
            }
        }

        // Mehrfachräume trimmen
        $work = trim(preg_replace('/\s+/', ' ', $work));

        // Vorkommen zählen (für mult.-Erkennung)
        $profCount = preg_match_all('/\bProf\.?\b/i', $work);
        $drCount   = preg_match_all('/\bDr\.?\b/i', $work);
        $pdCount   = preg_match_all('/\bPD\b/i', $work);

        // Emeritus?
        $hasEm = (bool) preg_match('/\bem\.?\b/i', $raw);

        // Normalisierte Alias-Zeichenfolge bilden (für Lookup im Alias-Map)
        // Reihenfolge: PD vor Dr vor Prof, plus Em, plus Mult
        $aliasBits = [];
        if ($pdCount > 0) { $aliasBits[] = 'pd'; }
        if ($drCount > 0) { $aliasBits[] = 'dr'; }
        if ($profCount > 0) { $aliasBits[] = 'prof'; }
        if ($hasEm && $profCount > 0) { $aliasBits[] = 'em'; }

        // Mult.-Kennzeichen (wenn mehrfach)
        $hasMult = ($profCount > 1) || ($drCount > 1);
        if ($hasMult) {
            $aliasBits[] = 'mult';
        }

        $aliasNorm = trim(strtolower(implode(' ', $aliasBits)));
        // Auch Variante ohne "mult" probieren (falls nicht in Config gepflegt)
        $aliasNormNoMult = trim(strtolower(implode(' ', array_filter($aliasBits, static fn($b) => $b !== 'mult'))));

        // Versuche 1: exakter Alias
        $key = $aliasToKey[$aliasNorm] ?? '';
        // Versuche 2: ohne mult
        if ($key === '' && $aliasNormNoMult !== '') {
            $key = $aliasToKey[$aliasNormNoMult] ?? '';
        }
        // Versuche 3: Fallback-Kaskade
        if ($key === '') {
            $fallbacks = [];
            // sortierte Fallbacks: prof dr em, prof dr, prof em, prof, pd dr, dr, pd
            if ($profCount > 0 && $drCount > 0 && $hasEm) $fallbacks[] = 'prof dr em';
            if ($profCount > 0 && $drCount > 0)          $fallbacks[] = 'prof dr';
            if ($profCount > 0 && $hasEm)                $fallbacks[] = 'prof em';
            if ($profCount > 0)                          $fallbacks[] = 'prof';
            if ($pdCount > 0 && $drCount > 0)            $fallbacks[] = 'pd dr';
            if ($drCount > 0)                            $fallbacks[] = 'dr';
            if ($pdCount > 0)                            $fallbacks[] = 'pd';

            foreach ($fallbacks as $fb) {
                $k = $aliasToKey[$fb] ?? '';
                if ($k !== '') { $key = $k; break; }
            }
        }

        // Wenn trotzdem kein Key erkannt wurde → fertig ohne Titel
        if ($key === '' || !isset($prefixMap[$key])) {
            // Sichtbare Titel trotzdem aus Rohdaten (bereinigt) zusammensetzen:
            $visibleBase = trim($work);
            // APL vorn
            $prefixFront = $hasApl ? 'apl. ' : '';
            // hinten: habil/h.c. + Disziplinen
            $suffixTail = [];
            if ($hasHabil) $suffixTail[] = 'habil.';
            if ($hasHC)    $suffixTail[] = 'h.c.';
            $visibleNoDisc = trim($prefixFront . $visibleBase . (empty($suffixTail) ? '' : ' ' . implode(' ', $suffixTail)));
            $visibleFull   = $visibleNoDisc;
            if (!empty($disciplines)) {
                $visibleFull .= ' ' . implode(' ', $disciplines);
            }
            $result['visible_title']               = $visibleFull;
            $result['visible_title_no_discipline'] = $visibleNoDisc;
            return $result;
        }

        // Label & Sortorder aus Config
        $label     = (string) ($prefixMap[$key]['label'] ?? $key);
        $sortorder = (int)    ($prefixMap[$key]['sortorder'] ?? PHP_INT_MAX);

        // Sichtbare Titel zusammenbauen
        $visibleBase = $key; // wir verwenden den "kanonischen" Key für die Anzeige-Basis
        $prefixFront = $hasApl ? 'apl. ' : '';

        $suffixTail = [];
        if ($hasHabil) $suffixTail[] = 'habil.';
        if ($hasHC)    $suffixTail[] = 'h.c.';

        $visibleNoDisc = trim($prefixFront . $visibleBase . (empty($suffixTail) ? '' : ' ' . implode(' ', $suffixTail)));
        $visibleFull   = $visibleNoDisc;
        if (!empty($disciplines)) {
            $visibleFull .= ' ' . implode(' ', $disciplines);
        }

        $result['key']                          = $key;
        $result['label']                        = $label;
        $result['sortorder']                    = $sortorder;
        $result['visible_title']                = $visibleFull;
        $result['visible_title_no_discipline']  = $visibleNoDisc;

        return $result;
    }
    
}
