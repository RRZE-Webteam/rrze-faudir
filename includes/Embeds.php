<?php
namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Embeds {
    public static function register(): void {
        add_action('init', [__CLASS__, 'register_handlers']);
        
        // Für Gutenberg-Preview:
        add_filter('pre_oembed_result', [__CLASS__, 'oembed_proxy'], 10, 3);
    }

    public static function register_handlers(): void {
        if (!function_exists('wp_embed_register_handler')) {
            return;
        }

      // Person-URL: https://faudir.fau.de/public/person/{identifier}[...]
        $pattern_person = '~^https://faudir\.fau\.de/public/person/([^/?#]+)(?:/)?(?:\?.*)?(?:#.*)?$~i';
        wp_embed_register_handler(
            'rrze_faudir_person',
            $pattern_person,
            [__CLASS__, 'handle_person_url'],
            999
        );

        // Org-URL: https://faudir.fau.de/public/org/{identifier}[...]
        $pattern_org = '~^https://faudir\.fau\.de/public/org/([^/?#]+)(?:/)?(?:\?.*)?(?:#.*)?$~i';
        wp_embed_register_handler(
            'rrze_faudir_org',
            $pattern_org,
            [__CLASS__, 'handle_org_url'],
            999
        );
    }

    /**
    * Callback des Embed-Handlers (Person).
    */
    public static function handle_person_url($matches, $attr, $url, $rawattr): string {
        $identifier = isset($matches[1]) ? sanitize_text_field($matches[1]) : '';
        if ($identifier === '') {
            return esc_url($url);
        }
        $shortcode = sprintf('[faudir identifier="%s"]', esc_attr($identifier));
        $html = self::add_styleprefix_on_editor($shortcode);
        
        return do_shortcode($html);
    }

    /**
     * Callback des Embed-Handlers (Org).
     */
    public static function handle_org_url($matches, $attr, $url, $rawattr): string {
        $identifier = isset($matches[1]) ? sanitize_text_field($matches[1]) : '';
        if ($identifier === '') {
            return esc_url($url);
        }
        $shortcode = sprintf('[faudir orgid="%s" display="org"]', esc_attr($identifier));
        $html = self::add_styleprefix_on_editor($shortcode);
        
        return do_shortcode($html);
    }
    
    /**
     * Gutenberg-Preview: oEmbed-Proxy abkürzen und direkt HTML liefern.
     */
    public static function oembed_proxy($result, string $url, $args) {
        //  ORG 
        if (preg_match('~^https://faudir\.fau\.de/public/org/([^/?#]+)~i', $url, $m)) {
            $identifier = sanitize_text_field($m[1]);
            if ($identifier === '') {
                return $result;
            }
            $html = do_shortcode(sprintf('[faudir orgid="%s" display="org"]', esc_attr($identifier)));
            $html = self::add_styleprefix_on_editor($html);
            return (is_string($html) && trim($html) !== '') ? $html : $result;
        }

        // PERSON
        if (preg_match('~^https://faudir\.fau\.de/public/person/([^/?#]+)~i', $url, $m)) {
            $identifier = sanitize_text_field($m[1]);
            if ($identifier === '') {
                return $result;
            }
            $html = do_shortcode(sprintf('[faudir identifier="%s"]', esc_attr($identifier)));
            $html = self::add_styleprefix_on_editor($html);
            
            return (is_string($html) && trim($html) !== '') ? $html : $result;
        }

        return $result;
    }
    
    
    private static function add_styleprefix_on_editor(string $html): string {   
        if (empty($html)) {
            return '';
        }
        $is_block_editor = false;
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) {
                $is_block_editor = true;
            }
        }
        
        $add = '';
        if ($is_block_editor || is_admin() || self::is_block_editor_embed_request()) {
            $css_url = plugins_url('assets/css/rrze-faudir.css', defined('RRZE_PLUGIN_FILE') ? RRZE_PLUGIN_FILE : __FILE__);
            $add = '<style>@import url("' . esc_url_raw($css_url) . '");</style>';
        }
        $result = $add.$html;
        
        return $result;
    }
    
    
    private static function is_block_editor_embed_request(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Gutenberg (Editor-iFrame) ruft den REST-Proxy auf
        if (defined('REST_REQUEST') && REST_REQUEST) {
            if (
                stripos($uri, '/wp-json/oembed/1.0/proxy') !== false
                || (isset($_GET['rest_route']) && stripos((string) $_GET['rest_route'], '/oembed/1.0/proxy') !== false)
            ) {
                return true;
            }
        }
        // Classic-Editor-Preview (TinyMCE): kein Block-Editor
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && $_REQUEST['action'] === 'parse-embed') {
            return false;
        }
        // Frontend oder sonstiges Admin: nicht Block-Editor
        return false;
    }

}
