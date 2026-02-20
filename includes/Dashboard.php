<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

final class Dashboard {

    public function register_hooks(): void {
        add_action('wp_dashboard_setup', [$this, 'register_widget']);
        add_action('admin_post_' . Constants::DASHBOARD_DISMISS_ACTION, [$this, 'handle_dismiss']);
    }

    public function register_widget(): void {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $alerts = get_option(Constants::OPTION_DASHBOARD_PRIVATE_ALERTS, []);
        if (!is_array($alerts) || empty($alerts) || empty($alerts['count'])) {
            return;
        }

        wp_add_dashboard_widget(
            Constants::DASHBOARD_WIDGET_ID,
            __('FAUdir: Persons set to private', 'rrze-faudir'),
            [$this, 'render_widget']
        );
    }

    public function render_widget(): void {
        if (!current_user_can('edit_posts')) {
            echo '<p>' . esc_html__('Insufficient permissions.', 'rrze-faudir') . '</p>';
            return;
        }

        $alerts = get_option(Constants::OPTION_DASHBOARD_PRIVATE_ALERTS, []);
        if (!is_array($alerts) || empty($alerts) || empty($alerts['count'])) {
            echo '<p>' . esc_html__('No recent availability issues.', 'rrze-faudir') . '</p>';
            return;
        }

        $count = isset($alerts['count']) ? (int) $alerts['count'] : 0;
        $last  = isset($alerts['last_at']) ? (int) $alerts['last_at'] : 0;
        $items = isset($alerts['items']) && is_array($alerts['items']) ? $alerts['items'] : [];

        echo '<div class="notice notice-warning inline">';
        echo '<p><strong>' . esc_html__('Heads-up:', 'rrze-faudir') . '</strong> ';
        echo esc_html(sprintf(
            /* translators: %d = number of persons */
            _n('%d person entry was set to private due to API availability issues.', '%d person entries were set to private due to API availability issues.', $count, 'rrze-faudir'),
            $count
        ));
        if ($last > 0) {
            echo ' ' . esc_html__('Last change:', 'rrze-faudir') . ' ' . esc_html(wp_date('Y-m-d H:i:s T', $last)) . '.';
        }
        echo '</p>';
        echo '</div>';

        if (!empty($items)) {
            echo '<p><strong>' . esc_html__('Affected entries:', 'rrze-faudir') . '</strong></p>';
            echo '<ul style="margin-left:18px;list-style:disc;">';
            foreach ($items as $it) {
                if (!is_array($it)) {
                    continue;
                }

                $post_id = isset($it['post_id']) ? (int) $it['post_id'] : 0;
                if ($post_id <= 0) {
                    continue;
                }

                $title = get_the_title($post_id);
                if ($title === '') {
                    $title = '#' . $post_id;
                }

                $edit_url = get_edit_post_link($post_id, 'url');
                $when     = isset($it['at']) ? (int) $it['at'] : 0;

                echo '<li>';
                if ($edit_url) {
                    echo '<a href="' . esc_url($edit_url) . '">' . esc_html($title) . '</a>';
                } else {
                    echo esc_html($title);
                }
                if ($when > 0) {
                    echo ' <span class="description">(' . esc_html(wp_date('Y-m-d H:i:s T', $when)) . ')</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }

        $dismiss_url = $this->get_dismiss_url();
        echo '<p style="margin-top:12px;">';
        echo '<a class="button button-secondary" href="' . esc_url($dismiss_url) . '">';
        echo esc_html__('Acknowledge and hide this notice', 'rrze-faudir');
        echo '</a>';
        echo '</p>';
    }

    public function handle_dismiss(): void {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'rrze-faudir'));
        }

        check_admin_referer(Constants::NONCE_DASHBOARD_DISMISS);

        delete_option(Constants::OPTION_DASHBOARD_PRIVATE_ALERTS);

        $redirect = admin_url('index.php');
        wp_safe_redirect($redirect);
        exit;
    }

    private function get_dismiss_url(): string {
        $url = admin_url('admin-post.php');
        $url = add_query_arg('action', Constants::DASHBOARD_DISMISS_ACTION, $url);
        $url = wp_nonce_url($url, Constants::NONCE_DASHBOARD_DISMISS);
        return $url;
    }
}