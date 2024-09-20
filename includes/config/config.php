<?php
// config/config.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return [
    'api_key' => '',
    'no_cache_logged_in' => false,
    'cache_timeout' => 15, // Minimum 15 minutes
    'transient_time_for_org_id' => 1, // Minimum 1 day
    'show_error_message' => false,
    'business_card_title' => __('Call up business card', 'rrze-faudir'),
    'hard_sanitize' => false,
    'default_output_fields' => ['academic_title', 'first_name', 'last_name', 'email'], // Default fields
];
