<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;



class Template {
    protected $template_dir;

    public function __construct($template_dir) {
        $this->template_dir = $template_dir;
    }

    public function render($template_name, $data = []) {
        $template_path = $this->template_dir . $template_name . '.php';
        
       
        if (!file_exists($template_path)) {
            return ''; // Return an empty string if the template doesn't exist            
        }

        ob_start();
        extract($data);  // Make the $data array available as variables in the template
        include $template_path;
        return ob_get_clean();
    }
}
