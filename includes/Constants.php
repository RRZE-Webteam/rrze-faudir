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
    public const API_BASE_URL = 'https://api.fau.de/pub/v1/opendir/';

    /**
     * Prefix für Transient-Caches der API-Daten
     */
    
     
    public const TRANSIENT_PREFIX_BASE = 'faudir_';
    // Gemeinsamer Transient-Base-Prefix


    public const TRANSIENT_PREFIX_API =  self::TRANSIENT_PREFIX_BASE . 'api_';
    public const TRANSIENT_PREFIX_SHORTCODE = self::TRANSIENT_PREFIX_BASE . 'shortcode_';



    /**
     * Jitter in Minuten zur Vermeidung von Cache-Stürmen
     */
    public const TRANSIENT_JITTER_MINUTES = 5;

    /**
     * Cache-Laufzeiten (in Minuten) je Endpoint
     */
    public const TRANSIENT_TIMES = [
        'persons'       => 120,
        'contacts'      => 120,
        'organizations' => 240,
        'default'       => 150,
    ];

    /**
     * Standard-Limit für API-Requests
     */
    public const DEFAULT_LIMIT = 100;

}