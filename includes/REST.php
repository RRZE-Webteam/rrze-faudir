<?php

namespace RRZE\FAUdir;
defined ('ABSPATH') || exit;
class REST
{
    public function __construct()
    {
        $this->register_routes();
    }

    public function register_routes(): void{
        add_action('rest_api_init', [$this, 'settings_route']);
    }

    public static function settings_route(): void{
        register_rest_route('wp/v2/settings', 'rrze_faudir_options', array(
            'methods' => 'GET',
            'callback' => function () {
                $config = new Config;
                $options = $config->getOptions();
                $roles = $config->get('person_roles');
                return [
                    'default_output_fields' => get_option('rrze_faudir_options')['default_output_fields'] ?? [],
                    'available_fields' => $options['avaible_fields'] ?? [],
                    'avaible_fields_byformat' => $options['avaible_fields_byformat'] ?? [],
                    'person_roles' => $roles,
                    'default_organization' => $options['default_organization'] ?? null,
                    'available_formats_by_display' => $options['avaible_formats_by_display'] ?? [],
                    'format_names' => $options['formatnames'] ?? [],
                ];
            },
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));
    }
}