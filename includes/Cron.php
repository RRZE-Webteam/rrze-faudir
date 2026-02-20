<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\API;

final class Cron {
    protected Config $config;

    public function __construct(Config $config) {
        $config->insertOptions();
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
        $timestamp = wp_next_scheduled(Constants::CRON_HOOK_PERSON_AVAILABILITY);
        if ($timestamp) {
            wp_unschedule_event($timestamp, Constants::CRON_HOOK_PERSON_AVAILABILITY);
        }
    }

    public function migrate_scheduler_hook(): void {
        $timestamp = wp_next_scheduled(Constants::CRON_HOOK_PERSON_AVAILABILITY_OLD);
        if ($timestamp) {
            wp_unschedule_event($timestamp, Constants::CRON_HOOK_PERSON_AVAILABILITY_OLD);
        }

        if (!wp_next_scheduled(Constants::CRON_HOOK_PERSON_AVAILABILITY)) {
            wp_schedule_event(time(), Constants::CRON_INTERVAL, Constants::CRON_HOOK_PERSON_AVAILABILITY);
        }
    }

    public function check_api_person_availability(): void {
        if (get_transient(Constants::TRANSIENT_AVAILABILITY_RUNNING)) {
            return;
        }

        set_transient(Constants::TRANSIENT_AVAILABILITY_RUNNING, true, (int) Constants::TRANSIENT_AVAILABILITY_TTL);

        $post_type = (new Config())->get('person_post_type');
        $api = new API($this->config);

        $posts = get_posts([
            'post_type'      => $post_type,
            'post_status'    => ['publish', Constants::PERSON_STATUS_ON_MISSING],
            'posts_per_page' => 1000,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ]);

        foreach ($posts as $post_id) {
            $this->check_single_post((int) $post_id, $api);
        }

        delete_transient(Constants::TRANSIENT_AVAILABILITY_RUNNING);
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

        update_post_meta($post_id, Constants::META_LAST_FAILURE_AT, time());
        update_post_meta($post_id, Constants::META_FAILURE_COUNT, $count);

        if ($count >= $max) {
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
        if ($current === Constants::PERSON_STATUS_ON_MISSING) {
            return;
        }

        update_post_meta($post_id, Constants::META_PREV_STATUS, $current);

        wp_update_post([
            'ID'          => $post_id,
            'post_status' => Constants::PERSON_STATUS_ON_MISSING,
        ]);
        $this->add_private_alert((int) $post->ID, (string) $current);
        
        do_action( 'rrze.log.warn',"FAUdir\Cron (set_post_private): Person post set to private {$post_id}, {$context}");
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
        do_action( 'rrze.log.info',"FAUdir\Cron (maybe_restore_from_private) Person post recovered: {$post_id}, {$context}");
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
}
  