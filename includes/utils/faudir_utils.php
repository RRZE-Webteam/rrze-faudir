<?php

class FaudirUtils
{
    const API_BASE_URL = 'https://api.fau.de/pub/v1/opendir/';

    public static function isUsingNetworkKey(): bool
    {
        if (is_multisite()) {
            $settingsOptions = get_site_option('rrze_settings');
            if (!empty($settingsOptions->plugins->faudir_public_apiKey)) {
                return true;
            }
        }
        return false;
    }

    public static function getKey()
    {
        if (self::isUsingNetworkKey()) {
            $settingsOptions = get_site_option('rrze_settings');
            return $settingsOptions->plugins->faudir_public_apiKey;
        } else {
            $options = get_option('rrze_faudir_options');
            return isset($options['api_key']) ? $options['api_key'] : '';
        }
    }

    public static function getApiBaseUrl()
    {
        return self::API_BASE_URL;
    }

    public static function getDefaultOutputFields()
    {
        $options = get_option('rrze_faudir_options');
        $default_output_fields = isset($options['default_output_fields']) ? $options['default_output_fields'] : [];

        $field_mapping = [
            'display_name' => 'displayName',
            'academic_title' => 'personalTitle',
            'first_name' => 'givenName',
            'last_name' => 'familyName',
            'academic_suffix' => 'personalTitleSuffix',
            'organization' => 'organization',
            'function' => 'function',
            'url' => 'url'
        ];

        // Map fields from options to internal field names
        $default_show_fields = array_map(function ($field) use ($field_mapping) {
            return isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
        }, $default_output_fields);

        return array_unique($default_show_fields);
    }

    private static function getAcademicTitleLongVersion(string $prefix): string
    {
        $prefixes = array(
            '' => __('Not specified', 'rrze-faudir'),
            'Dr.' => __('Doktor', 'rrze-faudir'),
            'Prof.' => __('Professor', 'rrze-faudir'),
            'Prof. Dr.' => __('Professor Doktor', 'rrze-faudir'),
            'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
            'Prof. Dr. em.' => __('Professor Doktor (Emeritus)', 'rrze-faudir'),
            'PD' => __('Privatdozent', 'rrze-faudir'),
            'PD Dr.' => __('Privatdozent Doktor', 'rrze-faudir')
        );

        return isset($prefixes[$prefix]) ? $prefixes[$prefix] : '';
    }

    public static function getPersonNameHtml($person_data)
    {
        $hard_sanitize = $person_data['hard_sanitize'] ?? false;
        $personal_title = $person_data['personal_title'] ?? '';
        $first_name = $person_data['first_name'] ?? '';
        $nobility_title = $person_data['nobility_title'] ?? '';
        $last_name = $person_data['last_name'] ?? '';
        $title_suffix = $person_data['title_suffix'] ?? '';
        $identifier = $person_data['identifier'] ?? '';

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
        if (!empty($first_name)) {
            $nameHtml .= '<span itemprop="givenName">' . esc_html($first_name) . '</span> ';
        }
        if (!empty($nobility_title)) {
            $nameHtml .= '<span>' . esc_html($nobility_title) . '</span> ';
        }
        if (!empty($last_name)) {
            $nameHtml .= '<span itemprop="familyName">' . esc_html($last_name) . '</span> ';
        }
        if (!empty($title_suffix)) {
            $nameHtml .= '(<span itemprop="honorificSuffix">' . esc_html($title_suffix) . '</span>)';
        }
        $nameHtml .= '</span>';
        return $nameHtml;
    }
    public static function getWeekday($weekday)
    {
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

    public static function filterContactsByCriteria($contacts, $includeDefaultOrg, $defaultOrgIdentifier, $email)
    {
        foreach ($contacts as $contactKey => $contact) {
            $shouldRemove = false;

            // Check organization if includeDefaultOrg is true
            if ($includeDefaultOrg && $contact['organization']['identifier'] !== $defaultOrgIdentifier) {
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
}

// Neue lokale Version:
function load_fontawesome_svg()
{
    function get_fa_icon($icon_name)
    {
        $icon_path = plugin_dir_path(RRZE_PLUGIN_FILE) . 'assets/fontawesome/svgs/';
        $icon_file = '';

        // Check in different FA directories
        $directories = ['solid', 'regular', 'brands'];
        foreach ($directories as $dir) {
            if (file_exists($icon_path . $dir . '/' . $icon_name . '.svg')) {
                $icon_file = file_get_contents($icon_path . $dir . '/' . $icon_name . '.svg');
                break;
            }
        }

        return $icon_file ?: '<i class="fas fa-' . esc_attr($icon_name) . '"></i>';
    }

    function get_fa_icon_url($icon_name)
    {
        $icon_path = plugin_dir_path(RRZE_PLUGIN_FILE) . 'assets/fontawesome/svgs/';
        $icon_url = plugin_dir_url(RRZE_PLUGIN_FILE) . 'assets/fontawesome/svgs/';

        // Check in different FA directories
        $directories = ['solid', 'regular', 'brands'];
        foreach ($directories as $dir) {
            if (file_exists($icon_path . $dir . '/' . $icon_name . '.svg')) {
                return $icon_url . $dir . '/' . $icon_name . '.svg';
            }
        }
        return '';
    }
}
add_action('init', 'load_fontawesome_svg');

function get_social_icon_data($platform)
{
    if (!defined('RRZE_PLUGIN_FILE')) {
        return [
            'name' => $platform,
            'css_class' => 'social-icon social-icon-' . $platform,
            'icon_url' => '' // Fallback empty URL if constant not defined
        ];
    }

    $iconMap = require RRZE_PLUGIN_PATH . 'includes/config/icons.php';
    $platform = strtolower($platform);
    $icon_name = isset($iconMap[$platform]) ? $iconMap[$platform] : 'link';

    return [
        'name' => $platform,
        'css_class' => 'social-icon social-icon-' . $platform,
        'icon_url' => RRZE_PLUGIN_URL . 'assets/fontawesome/svgs/brands/' . $icon_name . '.svg',
        'icon_address' => RRZE_PLUGIN_URL . 'assets/fontawesome/svgs/solid/' . $icon_name . '.svg'
    ];
}
