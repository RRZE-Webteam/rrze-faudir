<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Maintenance;
use RRZE\FAUdir\BlockRegistration;
use RRZE\FAUdir\REST;
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
        new REST();
        $shortcode = new Shortcode($this->config);
        new BlockRegistration();
        
        $enqueues = new EnqueueScripts($this->pluginFile);
        $enqueues->register();
    
        // Rufe Maintenance Hooks auf
        $maintenance = new Maintenance($this->config);
        $maintenance->register_hooks();
        
    
    }



}
