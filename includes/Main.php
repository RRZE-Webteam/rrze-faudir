<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Settings;
use RRZE\FAUdir\Maintenance;
use RRZE\FAUdir\BlockRegistration;
use RRZE\FAUdir\REST;
use RRZE\FAUdir\Config;
use RRZE\FAUdir\Filters;
use RRZE\FAUdir\Embeds;

/**
 * Hauptklasse
 */
class Main {
   
     /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected string $pluginFile;
    public Config $config;
    public CPT $cpt;

    public function __construct(string $pluginFile)  {
        $this->pluginFile = $pluginFile;
        $this->config = new Config();   
        $this->config->insertOptions();

        $this->config->set('pluginfile', $pluginFile);
    }

    public function onLoaded() {
        
        // CPT laden
        $this->cpt = new CPT($this->config);
        

        // Einstellungen laden
        $settings = new Settings($this->config, $this->cpt);
        $settings->register_hooks();
        
        // Register REST API
        new REST($this->config);
        
        // Enqueue Scripts
        $enqueues = new EnqueueScripts();
        $enqueues->register();
        
        // Register Shortcodes Actions
        new Shortcode($this->config);
        
        // Block Registration
        new BlockRegistration();
    
        // Rufe Maintenance Hooks auf
        $maintenance = new Maintenance($this->config, $this->cpt);
        $maintenance->register_hooks();
        
        $dashboard = new Dashboard();
        $dashboard->register_hooks();
        

        // Aktiviere die Filter für externe Plugins
        Filters::register();
     
        // Embed Funktionalität aktivieren
        Embeds::register();
        
    }



}
