<?php
namespace VGBM\PM\Frontend;

use VGBM\PM\Documents\Config;
use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class DocumentDownload {

    public function register(): void {
        add_filter('query_vars', [$this, 'query_vars']);
        add_action('init', [$this, 'maybe_serve_early'], 0);
        add_action('template_redirect', [$this, 'maybe_serve'], 0);
    }

    public function query_vars(array $vars): array {
        $vars[] = 'vgbm_doc_file';
        return $vars;
    }

    public function maybe_serve_early(): void {
        if (empty($_GET['vgbm_doc_file'])) { return; }
        $doc_id = (int) $_GET['vgbm_doc_file'];
        if ($doc_id <= 0) { return; }
        $this->serve($doc_id);
    }

    public function maybe_serve(): void {
        $doc_id = (int) get_query_var('vgbm_doc_file');
        if (!$doc_id) { return; }
        $this->serve($doc_id);
    }

    private function is_staff(): bool {
        return current_user_can('manage_options') || current_user_can('vgbm_manage') || current_user_can('edit_vgbm_contracts');
    }

    private function current_user_can_access_contract(int $contract_id): bool {
        if ($this->is_staff()) { return true; }
        if (!is_user_logged_in()) { return false; }
        $uid = get_current_user_id();

        $renter_ids = get_post_meta($contract_id, '_vgbm_renter_ids', true);
        if (is_array($renter_ids) && !empty($renter_ids)) {
            foreach ($renter_ids as $rid) {
                $linked_user = (int) get_post_meta((int)$rid, '_vgbm_linked_user_id', true);
                if ($linked_user && $linked_user === $uid) {
                    return true;
                }
            }
        }

        $legacy_user_ids = get_post_meta($contract_id, '_vgbm_renter_user_ids', true);
        if (!is_array($legacy_user_ids)) { $legacy_user_ids = []; }
        return in_array($uid, array_map('intval', $legacy_user_ids), true);
    }

    private function current_user_can_access_document(int $doc_id): bool {
        Config::ensure_defaults();

        if ($this->is_staff()) {
            return true;
        }

        $label_key = (string) get_post_meta($doc_id, '_vgbm_doc_label', true);
        $labels = Config::get_labels();
        $sens = isset($labels[$label_key]['sensitivity']) ? (string)$labels[$label_key]['sensitivity'] : 'internal';

        // Public documents: allow anyone
        if ($sens === 'public') { return true; }

        // Tenant-visible docs: allow linked renters (via contract or renter profile)
        if ($sens === 'tenant') {
            if (!is_user_logged_in()) { return false; }
            $uid = get_current_user_id();

            $linked = get_post_meta($doc_id, '_vgbm_linked_post_ids', true);
            if (!is_array($linked)) { $linked = []; }
            $linked = array_values(array_filter(array_map('intval', $linked), fn($v) => $v > 0));

            foreach ($linked as $pid) {
                $p = get_post($pid);
                if (!$p) { continue; }

                if ($p->post_type === PostTypes::CPT_RENTER) {
                    $linked_user = (int) get_post_meta($pid, '_vgbm_linked_user_id', true);
                    if ($linked_user && $linked_user === $uid) { return true; }
                }

                if ($p->post_type === PostTypes::CPT_CONTRACT) {
                    if ($this->current_user_can_access_contract($pid)) { return true; }
                }
            }
        }

        // Internal/confidential/sensitive: staff only
        return false;
    }

    private function serve(int $doc_id): void {
        if (headers_sent()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_die(__('Cannot serve document because output has already started.', 'vgbm-property-manager'));
        }

        $post = get_post($doc_id);
        if (!$post || $post->post_type !== PostTypes::CPT_DOCUMENT) {
            status_header(404);
            exit;
        }

        if (!$this->current_user_can_access_document($doc_id)) {
            status_header(403);
            exit;
        }

        $att_id = (int) get_post_meta($doc_id, '_vgbm_current_attachment_id', true);
        if (!$att_id) {
            status_header(404);
            exit;
        }

        $file = get_attached_file($att_id);
        if (!$file || !is_readable($file)) {
            status_header(404);
            exit;
        }

        $mime = get_post_mime_type($att_id);
        if (!$mime) { $mime = 'application/octet-stream'; }

        $filename = basename($file);

        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));

        readfile($file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
        exit;
    }
}
