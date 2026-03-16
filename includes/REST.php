<?php

namespace RRZE\FAUdir;
defined ('ABSPATH') || exit;

class REST {
    protected Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        $this->register_routes();
    }

    public function register_routes(): void{
        add_action('rest_api_init', [$this, 'settings_route']);
        add_action('rest_api_init', [$this, 'organization_route']);
        
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
     * Registers a route for fetching organization data via the block editor.
     */
    public function organization_route(): void {
        register_rest_route('rrze-faudir/v1', '/organization', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_organization_payload'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
            'args'                => [
                'orgid' => [
                    'description'        => __('FAUdir organization identifier.', 'rrze-faudir'),
                    'type'               => 'string',
                    'required'           => false,
                    'sanitize_callback'  => [Organization::class, 'sanitizeOrgIdentifier'],
                    'validate_callback'  => function ($value) {
                        return empty($value) || Organization::sanitizeOrgIdentifier($value);
                    },
                ],
                'orgnr' => [
                    'description'        => __('Legacy 10 digit organization number.', 'rrze-faudir'),
                    'type'               => 'string',
                    'required'           => false,
                    'sanitize_callback'  => 'sanitize_text_field',
                    'validate_callback'  => function ($value) {
                        $value = preg_replace('/\D/', '', (string) $value);
                        return empty($value) || FaudirUtils::isValidOrgnr($value);
                    },
                ],
            ],
        ]);
    }

    /**
    * Returns the organization payload for the provided identifiers.
    */
   public function get_organization_payload(\WP_REST_Request $request) {
       $orgnr = preg_replace('/\D/', '', (string) ($request->get_param('orgnr') ?? ''));
       $orgid = FaudirUtils::sanitizeOrganizationId((string) ($request->get_param('orgid') ?? '')) ?? '';

       $org = new Organization();
       $org->setConfig($this->config);

       if ($orgnr !== '') {
           if (!FaudirUtils::isValidOrgnr($orgnr)) {
               return new \WP_Error(
                   'rrze_faudir_invalid_orgnr',
                   __('Invalid parameter orgnr. Expecting a 10 digit number.', 'rrze-faudir'),
                   ['status' => 400]
               );
           }

           $resolvedId = $org->getIdentifierbyOrgnr($orgnr);
           $resolvedId = FaudirUtils::sanitizeOrganizationId((string) $resolvedId) ?? '';

           if ($resolvedId === '') {
               return new \WP_Error(
                   'rrze_faudir_orgnr_not_found',
                   __('Could not resolve an orgid from the provided orgnr.', 'rrze-faudir'),
                   ['status' => 404]
               );
           }

           $orgid = $resolvedId;
       }

       if ($orgid === '') {
           return new \WP_Error(
               'rrze_faudir_missing_orgid',
               __('Missing parameter orgid or orgnr.', 'rrze-faudir'),
               ['status' => 400]
           );
       }

       $hasData = $org->getOrgbyAPI($orgid);

       if (!$hasData) {
           return new \WP_Error(
               'rrze_faudir_org_not_found',
               __('No organization data found for the provided identifier.', 'rrze-faudir'),
               ['status' => 404]
           );
       }

       return rest_ensure_response([
           'identifier' => $orgid,
           'data'       => $org->toArray(),
       ]);
   }

    /**
     * Callback für die Settings-Route: gibt Einstellungsdaten zurück.
     * Eingabe: keine (Request wird nicht benötigt).
     * Rückgabe: array mit Optionen/Feldern/Rollen.
     */
    public function get_settings_payload(): array {
        return [
            'show_output_fields_person_default' => $this->config->getDefaultFieldlistByFormat('default', 'person'),
            'show_output_fields_person_page'    => $this->config->getDefaultFieldlistByFormat('page', 'person'),
            'show_output_fields_org_default'    => $this->config->getDefaultFieldlistByFormat('default', 'org'),
            'available_fields'                  => $this->config->get('avaible_fields') ?? [],
            'available_fields_org'              => $this->config->get('avaible_fields_org') ?? [],
            'avaible_fields_byformat'           => $this->config->get('avaible_fields_byformat') ?? [],
            'default_organization'              => $this->config->get('default_organization'),
            'format_names'                      => $this->config->get('formatnames') ?? [],
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
