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
}

// Neue lokale Version:
function load_fontawesome_svg() {
    function get_fa_icon($icon_name) {
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

    function get_fa_icon_url($icon_name) {
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

function get_social_icon_data($platform) {
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
        'icon_url' => RRZE_PLUGIN_URL . 'assets/fontawesome/svgs/' . $icon_name . '.svg'
    ];
}
