<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Maintenance;
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


    public function __construct(string $pluginFile)  {
        $this->pluginFile = $pluginFile;
        $this->config = new Config();
        $this->config->set('pluginfile', $pluginFile);
    }

    public function onLoaded() {
           
        $shortcode = new Shortcode($this->config);
        
        $enqueues = new EnqueueScripts($this->pluginFile);
        $enqueues->register();
    
        // Rufe Maintenance Hooks auf
        $maintenance = new Maintenance($this->config);
        $maintenance->register_hooks();
        
    
    }



}
