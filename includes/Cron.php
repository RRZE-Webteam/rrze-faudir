<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\API;

final class Cron {
    protected Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function register_hooks(): void {
        add_action(Constants::CRON_HOOK_PERSON_AVAILABILITY, [$this, 'check_api_person_availability']);
        $this->migrate_scheduler_hook();
    }

    public function on_plugin_activation(): void {
        if (!wp_next_scheduled(Constants::CRON_HOOK_PERSON_AVAILABILITY)) {
            wp_schedule_event(time(), Constants::CRON_INTERVAL, Constants::CRON_HOOK_PERSON_AVAILABILITY);
        }
    }

    public function on_plugin_deactivation(): void {
        wp_clear_scheduled_hook(Constants::CRON_HOOK_PERSON_AVAILABILITY);
    }

    public function migrate_scheduler_hook(): void {
        wp_clear_scheduled_hook(Constants::CRON_HOOK_PERSON_AVAILABILITY_OLD);

        if (!wp_next_scheduled(Constants::CRON_HOOK_PERSON_AVAILABILITY)) {
            wp_schedule_event(time(), Constants::CRON_INTERVAL, Constants::CRON_HOOK_PERSON_AVAILABILITY);
        }
    }

    public function check_api_person_availability(): void {
        if (get_transient(Constants::TRANSIENT_AVAILABILITY_RUNNING)) {
            return;
        }

        set_transient(
            Constants::TRANSIENT_AVAILABILITY_RUNNING,
            true,
            (int) Constants::TRANSIENT_AVAILABILITY_TTL
        );

        try {
            $post_type = (string) $this->config->get('person_post_type');
            $api = new API($this->config);

            $posts = get_posts([
                'post_type'              => $post_type,
                'post_status'            => ['publish'],
                'posts_per_page'         => -1,
                'no_found_rows'          => true,
                'fields'                 => 'ids',
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'rrze_multilang_suppress_locale_query' => true,
                
            ]);

            foreach ($posts as $post_id) {
                $this->check_single_post((int) $post_id, $api);
            }
        } finally {
            delete_transient(Constants::TRANSIENT_AVAILABILITY_RUNNING);
        }
    }

    private function check_single_post(int $post_id, API $api): void {
        $status = (string) get_post_status($post_id);

        $person_id = (string) get_post_meta($post_id, 'person_id', true);
        $person_id = trim($person_id);

        if ($person_id === '') {
            $this->handle_result($post_id, $status, false, [
                'reason' => 'missing_person_id',
            ]);
            return;
        }

        $person_data = $api->getPerson($person_id);
        $ok = !($person_data === false || empty($person_data));

        $this->handle_result($post_id, $status, $ok, [
            'person_id' => $person_id,
        ]);
    }

    private function handle_result(int $post_id, string $status, bool $ok, array $context = []): void {
        if ($ok) {
            $this->mark_success($post_id);

            if ($status === Constants::PERSON_STATUS_ON_MISSING) {
                $this->maybe_restore_from_private($post_id, $context);
            }

            return;
        }

        if ($status === Constants::PERSON_STATUS_ON_MISSING) {
            $this->mark_failure_private($post_id);
            return;
        }

        $this->mark_failure_published($post_id, $context);
    }

    private function mark_success(int $post_id): void {
        update_post_meta($post_id, Constants::META_LAST_SUCCESS_AT, time());
        update_post_meta($post_id, Constants::META_LAST_FAILURE_AT, 0);
        update_post_meta($post_id, Constants::META_FAILURE_COUNT, 0);
    }

    private function mark_failure_published(int $post_id, array $context = []): void {
        $max = (int) Constants::PERSON_AVAILABILITY_MAX_FAILURES;


        $count = (int) get_post_meta($post_id, Constants::META_FAILURE_COUNT, true);
        $count = max(0, $count);
        $count++;

        $ts = time();

        update_post_meta($post_id, Constants::META_LAST_FAILURE_AT, $ts);
        update_post_meta($post_id, Constants::META_FAILURE_COUNT, $count);
    
        do_action( 'rrze.log.warning',"FAUdir\Cron (mark_failure_published): Post {$post_id} will be marked with failure",
        [
            'last_failure_key' => Constants::META_LAST_FAILURE_AT,
            'last_failure_value' => $ts,
            'failure_count_key' => Constants::META_FAILURE_COUNT,
            'failure_count_value' => $count,
            'context' => $context,
        ]);
 
        if ($count >= $max) {
            do_action( 'rrze.log.warning',"FAUdir\Cron (mark_failure_published): Post {$post_id} reached max failure count. Will be set to privat",
            [
                'last_failure_key' => Constants::META_LAST_FAILURE_AT,
                'last_failure_value' => $ts,
                'failure_count_key' => Constants::META_FAILURE_COUNT,
                'failure_count_value' => $count,
                'context' => $context,
            ]);
            $this->set_post_private($post_id, $context);
        }
    }

    private function mark_failure_private(int $post_id): void {
        $count = (int) get_post_meta($post_id, Constants::META_FAILURE_COUNT, true);
        $count = max(0, $count);
        $count++;

        update_post_meta($post_id, Constants::META_LAST_FAILURE_AT, time());
        update_post_meta($post_id, Constants::META_FAILURE_COUNT, $count);
    }

    private function set_post_private(int $post_id, array $context = []): void {
        $current = (string) get_post_status($post_id);

        do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): entered", [
            'post_id' => $post_id,
            'current_status' => $current,
            'target_status' => Constants::PERSON_STATUS_ON_MISSING,
            'context' => $context,
        ]);

        if ($current === Constants::PERSON_STATUS_ON_MISSING) {
            do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): already private", [
                'post_id' => $post_id,
                'current_status' => $current,
            ]);
            return;
        }
do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): before update META_PREV_STATUS", [
        'post_id' => $post_id,
        'current_status' => $current,
    ]);

        update_post_meta($post_id, Constants::META_PREV_STATUS, $current);
    do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): after update META_PREV_STATUS", [
        'post_id' => $post_id,
        'stored_prev_status' => get_post_meta($post_id, Constants::META_PREV_STATUS, true),
    ]);
    
    
        $result = wp_update_post([
            'ID'          => $post_id,
            'post_status' => Constants::PERSON_STATUS_ON_MISSING,
        ], true);
        
 do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): after wp_update_post", [
        'post_id' => $post_id,
        'result' => is_wp_error($result) ? $result->get_error_message() : $result,
        'new_status' => get_post_status($post_id),
    ]);

        if (is_wp_error($result)) {
            do_action('rrze.log.error', "FAUdir\\Cron (set_post_private): wp_update_post failed", [
                'post_id' => $post_id,
                'error' => $result->get_error_message(),
                'target_status' => Constants::PERSON_STATUS_ON_MISSING,
                'context' => $context,
            ]);
            return;
        }

        $new_status = (string) get_post_status($post_id);

        do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): wp_update_post finished", [
            'post_id' => $post_id,
            'result' => $result,
            'new_status' => $new_status,
            'target_status' => Constants::PERSON_STATUS_ON_MISSING,
            'context' => $context,
        ]);

        $this->add_private_alert($post_id, $current);

        do_action('rrze.log.warning', "FAUdir\\Cron (set_post_private): alert added", [
            'post_id' => $post_id,
            'old_status' => $current,
        ]);
    }

    private function maybe_restore_from_private(int $post_id, array $context = []): void {
        $prev = (string) get_post_meta($post_id, Constants::META_PREV_STATUS, true);
        $prev = trim($prev);

        /**
         * Schutz: Wenn META_PREV_STATUS fehlt, war das "private" vermutlich manuell gesetzt.
         * Dann NICHT automatisch publishen.
         */
        if ($prev === '') {
            return;
        }

        if (!get_post_status_object($prev)) {
            $prev = 'publish';
        }

        wp_update_post([
            'ID'          => $post_id,
            'post_status' => $prev,
        ]);

        delete_post_meta($post_id, Constants::META_PREV_STATUS);
        do_action( 'rrze.log.info',"FAUdir\Cron (maybe_restore_from_private) Person post recovered: {$post_id}", $context);
    }
    
    private function add_private_alert(int $post_id, string $old_status): void {
        $opt = get_option(Constants::OPTION_DASHBOARD_PRIVATE_ALERTS, []);
        if (!is_array($opt)) {
            $opt = [];
        }

        $items = isset($opt['items']) && is_array($opt['items']) ? $opt['items'] : [];

        array_unshift($items, [
            'post_id'     => $post_id,
            'old_status'  => $old_status,
            'at'          => time(),
        ]);

        $items = array_slice($items, 0, 20);

        $opt['items']   = $items;
        $opt['count']   = isset($opt['count']) ? ((int) $opt['count'] + 1) : 1;
        $opt['last_at'] = time();

        update_option(Constants::OPTION_DASHBOARD_PRIVATE_ALERTS, $opt, false);
    }
 
    /*
     * Damit ich den Status auch von der Ajax-Aktion aus CPT setzen kann brauche wir noch eine
     * public funktion:
     */
    public function apply_availability_result(int $post_id, bool $ok, array $context = []): void {
        $status = (string) get_post_status($post_id);
        if ($status === '') {
            return;
        }

        $this->handle_result($post_id, $status, $ok, $context);
    }
}
  