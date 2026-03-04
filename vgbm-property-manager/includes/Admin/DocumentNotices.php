<?php
namespace VGBM\PM\Admin;

if (!defined('ABSPATH')) { exit; }

final class DocumentNotices {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('admin_notices', [$this, 'render']);
    }

    public function render(): void {
        $uid = get_current_user_id();
        if (!$uid) { return; }

        $msg = get_transient('vgbm_pm_doc_upload_error_' . $uid);
        if ($msg) {
            delete_transient('vgbm_pm_doc_upload_error_' . $uid);
            echo '<div class="notice notice-error"><p>' . esc_html((string)$msg) . '</p></div>';
        }
    }
}
