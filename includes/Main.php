<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\Shortcode;


/**
 * Hauptklasse
 */
class Main {
   
     /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;
    
    public function __construct($pluginFile)  {
        $this->pluginFile = $pluginFile;
    }

    public function onLoaded() {
        $shortcode = new Shortcode();
        
        $enqueues = new EnqueueScripts($this->pluginFile);
       // Register and enqueue scripts
        $enqueues->register();
    
    
    }



}
