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

        return ($lang === 'de') ? 'de' : 'en'; 
    }

    
    
    public static function getDefaultOutputFields() {
        $options = get_option('rrze_faudir_options');
        $default_show_fields = isset($options['default_output_fields']) ? $options['default_output_fields'] : [];
/*
        $field_mapping = [
            'displayname'       => 'displayName',
            'honorificPrefix'    => 'personalTitle',
            'givenName'        => 'givenName',
            'nobility_title'    => 'titleOfNobility',
            'familyName'         => 'familyName',
            'honorificSuffix'   => 'personalTitleSuffix',
            'organization'      => 'organization',
            'jobTitle'          => 'jobTitle',
            'url'               => 'url'
        ];

        // Map fields from options to internal field names
        $default_show_fields = array_map(function ($field) use ($field_mapping) {
            return isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
        }, $default_output_fields);
        */
        return array_unique($default_show_fields);
    }

    private static function getAcademicTitleLongVersion(string $prefix): string  {
        $prefixes = array(
            '' => __('Not specified', 'rrze-faudir'),
            'Dr.' => __('Doctor', 'rrze-faudir'),
            'Prof.' => __('Professor', 'rrze-faudir'),
            'Prof. Dr.' => __('Professor Doctor', 'rrze-faudir'),
            'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
            'Prof. Dr. em.' => __('Professor Doctor (Emeritus)', 'rrze-faudir'),
            'PD' => __('Private lecturer', 'rrze-faudir'),
            'PD Dr.' => __('Private lecturer Doctor', 'rrze-faudir')
        );

        return isset($prefixes[$prefix]) ? $prefixes[$prefix] : '';
    }

    public static function getPersonNameHtml($person_data)  {
        $hard_sanitize = $person_data['hard_sanitize'] ?? false;
        $personal_title = $person_data['honorificPrefix'] ?? '';
        $givenName = $person_data['givenName'] ?? '';
        $nobility_title = $person_data['titleOfNobility'] ?? '';
        $familyName = $person_data['familyName'] ?? '';
        $title_suffix = $person_data['honorificSuffix'] ?? '';
        $identifier = $person_data['identifier'] ?? '';

        // if all name parts are empty, return an empty string
        if (empty($personal_title) && empty($givenName) && empty($nobility_title) && empty($familyName) && empty($title_suffix)) {
            return '';
        }

        $nameHtml = '';
        $nameHtml .= '<span id="name-' . esc_attr($identifier) . '" itemprop="name">';
        if (!empty($personal_title)) {
            if ($hard_sanitize) {
                $long_version = self::getAcademicTitleLongVersion($personal_title);
                if (!empty($long_version)) {
                    $nameHtml .= '<abbr title="' . esc_attr($long_version) . '" itemprop="honorificPrefix">' . esc_html($personal_title) . '</abbr> ';
                }
            } else {
                $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($personal_title) . '</span> ';
            }
        }
        if (!empty($givenName)) {
            $nameHtml .= '<span itemprop="givenName">' . esc_html($givenName) . '</span> ';
        }
        if (!empty($nobility_title)) {
            $nameHtml .= '<span>' . esc_html($nobility_title) . '</span> ';
        }
        if (!empty($familyName)) {
            $nameHtml .= '<span itemprop="familyName">' . esc_html($familyName) . '</span> ';
        }
        if (!empty($title_suffix)) {
            $nameHtml .= '(<span itemprop="honorificSuffix">' . esc_html($title_suffix) . '</span>)';
        }
        $nameHtml .= '</span>';
        return $nameHtml;
    }

    public static function getWeekday($weekday)  {
        $weekdayMap = [
            0 => __('Sunday','rrze-faudir'),
            1 => __('Monday','rrze-faudir'),
            2 => __('Tuesday','rrze-faudir'),
            3 => __('Wednesday','rrze-faudir'),
            4 => __('Thursday','rrze-faudir'),
            5 => __('Friday','rrze-faudir'),
            6 => __('Saturday','rrze-faudir'),
        ];
        return $weekdayMap[$weekday] ?? __('Unknown','rrze-faudir');
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
        $phone = preg_replace('/(\+?\d{1,3})\s*(\d{3,4})\s*(\d{3,4})\s*(\d{0,4})/', '$1 $2 $3 $4', $phone);

        return trim($phone); // Entfernt überflüssige Leerzeichen am Ende
    }

    
}
