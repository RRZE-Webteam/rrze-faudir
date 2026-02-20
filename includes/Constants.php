<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

/**
 * Class Constants
 * Zentrale Sammlung aller konstanten Werte des Plugins.
 */
final class Constants {

    /**
     * Default API Base URL
     */
    public const API_BASE_URL                       = 'https://api.fau.de/pub/v1/opendir/';
    /**
     * Standard-Limit für API-Requests
     */
    public const DEFAULT_LIMIT                      = 100;
    
    /**
     * Cache-Laufzeiten (in Minuten) je Endpoint
     */
    public const TRANSIENT_TIMES = [
        'persons'                                   => 120,
        'contacts'                                  => 120,
        'organizations'                             => 240,
        'default'                                   => 150,
    ];    
    /**
     * Prefix für Transient-Caches der API-Daten
     */
    public const TRANSIENT_PREFIX_BASE              = 'faudir_';
    public const TRANSIENT_PREFIX_API               = self::TRANSIENT_PREFIX_BASE . 'api_';
    public const TRANSIENT_PREFIX_SHORTCODE         = self::TRANSIENT_PREFIX_BASE . 'shortcode_';
    public const TRANSIENT_JITTER_MINUTES           = 5;

    /*
     * Constants für Cron-Scheduler und Daten-Prüfung
     */
    
    public const CRON_HOOK_PERSON_AVAILABILITY      = 'rrze-faudir_check_person_availability';
    public const CRON_HOOK_PERSON_AVAILABILITY_OLD  = 'check_person_availability';
    public const CRON_INTERVAL                      = 'hourly';
    public const PERSON_STATUS_ON_MISSING           = 'private';
    public const PERSON_AVAILABILITY_MAX_FAILURES   = 3;
    public const META_LAST_SUCCESS_AT               = '_faudir_api_last_success_at';
    public const META_LAST_FAILURE_AT               = '_faudir_api_last_failure_at';
    public const META_FAILURE_COUNT                 = '_faudir_api_failure_count';
    public const TRANSIENT_AVAILABILITY_RUNNING     = 'rrze_faudir_check_person_availability_running';
        // Note: Wir nutzen hier nicht die TRANSIENT_PREFIX_BASE, da wir diese Transients nicht 
        // durch den User ausversehen löschen wollen, während der Cron läuft
    public const TRANSIENT_AVAILABILITY_TTL         = 1200;

    public const OPTION_DASHBOARD_PRIVATE_ALERTS    = 'rrze_faudir_private_alerts';
    public const DASHBOARD_WIDGET_ID                = 'rrze_faudir_private_alerts_widget';
    public const DASHBOARD_DISMISS_ACTION           = 'rrze_faudir_dismiss_private_alerts';
    public const NONCE_DASHBOARD_DISMISS            = 'rrze_faudir_private_alerts_dismiss';
}