<?php

class FaudirUtils
{
    const API_BASE_URL = 'https://api.fau.de/pub/v1/opendir/';

    public static function isUsingNetworkKey()
    {
        if (is_multisite()) {
            $settingsOptions = get_site_option('rrze_settings');
            if (!empty($settingsOptions['plugins']['faudir_public_apiKey'])) {
                return true;
            }
        }
        return false;
    }

    public static function getKey()
    {
        if (self::isUsingNetworkKey()) {
            $settingsOptions = get_site_option('rrze_settings');
            return $settingsOptions['plugins']['faudir_public_apiKey'];
        } else {
            $options = get_option('rrze_faudir_options');
            return isset($options['api_key']) ? $options['api_key'] : '';
        }
    }

    public static function getApiBaseUrl()
    {
        return self::API_BASE_URL;
    }
}
