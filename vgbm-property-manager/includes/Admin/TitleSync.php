<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class TitleSync {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('save_post_' . PostTypes::CPT_PORTFOLIO, [$this, 'sync_portfolio_title'], 20, 3);
        add_filter('enter_title_here', [$this, 'title_placeholder'], 10, 2);
    }

    public function title_placeholder(string $placeholder, \WP_Post $post): string {
        if ($post->post_type === PostTypes::CPT_PORTFOLIO) {
            return __('Portfolio name (e.g. client name / portfolio)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_PROPERTY) {
            return __('Property name (e.g. address or internal label)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_RENTER) {
            return __('Renter name (e.g. Jane Doe)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_UTILITY) {
            return __('Utility name (e.g. Electricity main meter)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_READING) {
            return __('Reading title (e.g. 2026-02-01)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_ALLOCATION) {
            return __('Allocation title (e.g. Electricity shared meter)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_DOCUMENT) {
            return __('Document title (e.g. Energy label 2026)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_CONTRACT) {
            return __('Contract title (e.g. Unit 12A - Jan 2026)', 'vgbm-property-manager');
        }
        if ($post->post_type === PostTypes::CPT_UNIT) {
            return __('Unit name (e.g. Unit 12A)', 'vgbm-property-manager');
        }
        return $placeholder;
    }

    public function sync_portfolio_title(int $post_id, \WP_Post $post, bool $update): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (wp_is_post_revision($post_id)) { return; }

        if (trim((string) $post->post_title) !== '') { return; }

        // Prefer submitted value, fallback to stored meta.
        $company = '';
        if (isset($_POST['vgbm_company'])) {
            $company = sanitize_text_field(wp_unslash($_POST['vgbm_company']));
        }
        if ($company === '') {
            $company = (string) get_post_meta($post_id, '_vgbm_company', true);
        }

        $company = trim($company);
        if ($company === '') { return; }

        // Prevent recursion.
        remove_action('save_post_' . PostTypes::CPT_PORTFOLIO, [$this, 'sync_portfolio_title'], 20);

        wp_update_post([
            'ID' => $post_id,
            'post_title' => $company,
        ]);

        add_action('save_post_' . PostTypes::CPT_PORTFOLIO, [$this, 'sync_portfolio_title'], 20, 3);
    }
}
