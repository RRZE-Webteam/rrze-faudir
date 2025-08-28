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

    
    
    public static function getLang() {
        $locale = get_locale();          
        $lang = substr($locale, 0, 2);      
        //   return $lang;
        // currently we only support english and german
        // otherwise we have to update the additional content fields
        return ($lang === 'de') ? 'de' : 'en'; 
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

    
}
