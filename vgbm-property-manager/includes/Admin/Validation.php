<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class Validation {

    private static bool $title_was_missing = false;
    private static string $title_missing_post_type = '';

    public function register(): void {
        if (!is_admin()) { return; }
        add_filter('wp_insert_post_data', [$this, 'require_title'], 10, 2);
        add_filter('redirect_post_location', [$this, 'add_error_to_redirect'], 10, 2);
        add_action('admin_notices', [$this, 'render_notices']);
    }

    private function is_vgbm_post_type(string $post_type): bool {
        return in_array($post_type, [
            PostTypes::CPT_PORTFOLIO,
            PostTypes::CPT_PROPERTY,
            PostTypes::CPT_UNIT,
            PostTypes::CPT_RENTER,
            PostTypes::CPT_UTILITY,
            PostTypes::CPT_READING,
            PostTypes::CPT_ALLOCATION,
            PostTypes::CPT_DOCUMENT,
            PostTypes::CPT_CONTRACT,
            PostTypes::CPT_TICKET,
        ], true);
    }

    public function require_title(array $data, array $postarr): array {
        $post_type = isset($data['post_type']) ? (string) $data['post_type'] : '';
        if (!$this->is_vgbm_post_type($post_type)) { return $data; }

        $title = isset($data['post_title']) ? trim((string)$data['post_title']) : '';
        $status = isset($data['post_status']) ? (string) $data['post_status'] : '';

        // Portfolio: auto-fill from company name if title empty.
        if ($post_type === PostTypes::CPT_PORTFOLIO && $title === '') {
            $company = '';
            if (isset($_POST['vgbm_company'])) {
                $company = sanitize_text_field(wp_unslash($_POST['vgbm_company']));
            }
            $company = trim($company);
            if ($company !== '') {
                $data['post_title'] = $company;
                $title = $company;
            }
        }

        // Block publish/schedule when title still empty.
        $attempt_publish = in_array($status, ['publish', 'future'], true);
        if ($attempt_publish && $title === '') {
            self::$title_was_missing = true;
            self::$title_missing_post_type = $post_type;
            $data['post_status'] = 'draft';
        }

        return $data;
    }

    public function add_error_to_redirect(string $location, int $post_id): string {
        if (!self::$title_was_missing) { return $location; }
        self::$title_was_missing = false;

        return add_query_arg([
            'vgbm_pm_error' => 'title_required',
            'vgbm_pm_pt'    => self::$title_missing_post_type,
        ], $location);
    }

    public function render_notices(): void {
        if (empty($_GET['vgbm_pm_error']) || $_GET['vgbm_pm_error'] !== 'title_required') {
            return;
        }
        echo '<div class="notice notice-error"><p>'
            . esc_html__('Title is required. The item was saved as Draft.', 'vgbm-property-manager')
            . '</p></div>';
    }
}
