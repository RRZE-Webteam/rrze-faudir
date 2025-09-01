<?php

namespace RRZE\FAUdir;
defined ('ABSPATH') || exit;

class REST {
    public function __construct() {
        $this->register_routes();
    }

    public function register_routes(): void{
        add_action('rest_api_init', [$this, 'settings_route']);
        
         // AJAX-Hooks (eingeloggt & nopriv)
        
      // auskommentiert, da use nicht klar.  
    //    add_action('wp_ajax_rrze_faudir_search_contacts', [$this, 'search_contacts']);
    //    add_action('wp_ajax_nopriv_rrze_faudir_search_contacts', [$this, 'search_contacts']);

        
    }

    /** 
    * Registriert die Settings-Route /wp/v2/settings/rrze_faudir_options; nimmt nichts an; liefert nichts. 
     */
    public function settings_route(): void {
        register_rest_route('wp/v2/settings', 'rrze_faudir_options', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_settings_payload'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    /**
     * Callback für die Settings-Route: gibt Einstellungsdaten zurück.
     * Eingabe: keine (Request wird nicht benötigt).
     * Rückgabe: array mit Optionen/Feldern/Rollen.
     */
    public function get_settings_payload(): array {
        $config  = new Config();
        $options = $config->getOptions();

        return [
            'default_output_fields'       => get_option('rrze_faudir_options')['default_output_fields'] ?? [],
            'available_fields'            => $options['avaible_fields'] ?? [],
            'avaible_fields_byformat'     => $options['avaible_fields_byformat'] ?? [],
            'default_organization'        => $options['default_organization'] ?? null,
            'available_formats_by_display'=> $options['avaible_formats_by_display'] ?? [],
            'format_names'                => $options['formatnames'] ?? [],
        ];
    }
    
     /**
     * AJAX-Handler: Sucht Kontakte per Identifier (LIKE) in {$wpdb->prefix}contacts.
     * Optionaler Input (POST): 'identifier' (string), 'security' (Nonce rrze_faudir_api_nonce).
     * Rückgabe: JSON success (Array von Treffern) oder JSON error (Fehlermeldung).
     */
    public function search_contacts(): void {
        check_ajax_referer('rrze_faudir_api_nonce', 'security');

        $identifier = sanitize_text_field(wp_unslash($_POST['identifier'] ?? ''));
        do_action( 'rrze.log.notice', 'FAUdir\REST (search_contacts): Doing search with identifier: '. $identifier);           

        global $wpdb;
        $table = $wpdb->prefix . 'contacts';
        $like  = '%' . $wpdb->esc_like($identifier) . '%';

        $contacts = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE identifier LIKE %s", $like)
        );

        if (!empty($contacts)) {        
          //  do_action( 'rrze.log.notice', 'FAUdir\REST (search_contacts): Found contacts');         
            $formatted = array_map(function ($contact) {
                return [
                    'name'            => $contact->name,
                    'identifier'      => $contact->identifier,
                    'additional_info' => $contact->additional_info,
                ];
            }, $contacts);

            wp_send_json_success($formatted);
        } else {
            do_action( 'rrze.log.notice', 'FAUdir\REST (search_contacts): Nothingg found');         
            wp_send_json_error(__('No contacts found with the provided identifier.', 'rrze-faudir'));
        }
    }
    
}