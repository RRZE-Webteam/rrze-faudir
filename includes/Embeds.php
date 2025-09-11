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

        // Erlaubt: http/https, optional www, optional trailing slash & Query-String
        $pattern = '~^https://faudir\.fau\.de/public/person/([^/?#]+)(?:/)?(?:\?.*)?(?:#.*)?$~i';
        // Name des Handlers: 'rrze_faudir_person'
        wp_embed_register_handler('rrze_faudir_person', $pattern,[__CLASS__, 'handle_person_url'], 999);
    }

    /**
     * Callback des Embed-Handlers.
     * @param array  $matches  Regex-Matches (Index 1 = Identifier)
     * @param array  $attr     Embed-Attribute
     * @param string $url      Gefundene URL
     * @param array  $rawattr  Ungefilterte Attribute
     * @return string          HTML-Ausgabe
     */
    public static function handle_person_url($matches, $attr, $url, $rawattr): string {
        $identifier = isset($matches[1]) ? sanitize_text_field($matches[1]) : '';
        
        
          do_action( 'rrze.log.info', "FAUdir\Embeds (handle_person_url): Identifier:  {$identifier}.");
          
        if ($identifier === '') {
            // Fallback: Original-URL anzeigen, falls Regex fehlging
            return esc_url($url);
        }

        // Ausgabe entspricht deinem Shortcode [faudir identifier="..."]
        $shortcode = sprintf('[faudir identifier="%s"]', esc_attr($identifier));
        return do_shortcode($shortcode);
    }
    
     /**
     * Gutenberg-Preview: oEmbed-Proxy abkürzen und direkt HTML liefern.
     * Gibt ein HTML-String zurück, wenn URL passt – sonst $result unverändert.
     */
    public static function oembed_proxy($result, string $url, $args) {
        // Nur HTTPS + exakt die Domain/Pfade zulassen
        
        
        
        if (!preg_match('~^https://faudir\.fau\.de/public/person/([^/?#]+)~i', $url, $m)) {
            return $result; // nicht unsere URL
        }    
        
        
        do_action( 'rrze.log.info', "FAUdir\Embeds (oembed_proxy): URL:  {$url}.");

        $identifier = sanitize_text_field($m[1]);
        if ($identifier === '') {
            return $result;
        }
        do_action( 'rrze.log.info', "FAUdir\Embeds (oembed_proxy): Identifier:  {$identifier}.");
        // Erzeuge das HTML wie beim Shortcode
        $html = do_shortcode(sprintf('[faudir identifier="%s"]', esc_attr($identifier)));

        // Wenn leer, nicht überschreiben (Editor würde sonst "Fehlgeschlagen" zeigen)
        if (!is_string($html) || trim($html) === '') {
            return $result;
        }

        return $html;
    }
}
