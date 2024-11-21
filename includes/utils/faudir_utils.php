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
