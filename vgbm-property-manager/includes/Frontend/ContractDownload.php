<?php
namespace VGBM\PM\Frontend;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class ContractDownload {

    public function register(): void {
        add_filter('query_vars', [$this, 'query_vars']);

        // Serve as early as possible when requested via query string.
        // This avoids "headers already sent" issues on some setups.
        add_action('init', [$this, 'maybe_serve_early'], 0);

        // Also serve in the normal WP flow for pretty/query-var requests.
        add_action('template_redirect', [$this, 'maybe_serve'], 0);
    }

    public function query_vars(array $vars): array {
        $vars[] = 'vgbm_contract_doc';
        return $vars;
    }

    private function current_user_can_access_contract(int $contract_id): bool {
        // Staff/admin access
        if (current_user_can('manage_options') || current_user_can('edit_vgbm_contracts') || current_user_can('vgbm_manage')) {
            return true;
        }

        if (!is_user_logged_in()) { return false; }

        $uid = get_current_user_id();

        // New: contract linked renter profiles
        $renter_ids = get_post_meta($contract_id, '_vgbm_renter_ids', true);
        if (is_array($renter_ids) && !empty($renter_ids)) {
            foreach ($renter_ids as $rid) {
                $linked_user = (int) get_post_meta((int)$rid, '_vgbm_linked_user_id', true);
                if ($linked_user && $linked_user === $uid) {
                    return true;
                }
            }
        }

        // Legacy: contract stored renter user ids (WP users)
        $legacy_user_ids = get_post_meta($contract_id, '_vgbm_renter_user_ids', true);
        if (!is_array($legacy_user_ids)) { $legacy_user_ids = []; }
        return in_array($uid, array_map('intval', $legacy_user_ids), true);
    }

    public function maybe_serve_early(): void {
        if (empty($_GET['vgbm_contract_doc'])) { return; }
        $contract_id = (int) $_GET['vgbm_contract_doc'];
        if ($contract_id <= 0) { return; }
        $this->serve($contract_id);
    }

    public function maybe_serve(): void {
        $contract_id = (int) get_query_var('vgbm_contract_doc');
        if (!$contract_id) { return; }
        $this->serve($contract_id);
    }

    private function serve(int $contract_id): void {
        // If headers already sent, bail with a clear message (prevents warnings).
        if (headers_sent()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_die(__('Cannot serve contract document because output has already started. Please disable error display/output buffering issues (WP_DEBUG_DISPLAY) or conflicting plugins.', 'vgbm-property-manager'));
        }

        $post = get_post($contract_id);
        if (!$post || $post->post_type !== PostTypes::CPT_CONTRACT) {
            status_header(404);
            exit;
        }

        if (!$this->current_user_can_access_contract($contract_id)) {
            status_header(403);
            exit;
        }

        $att_id = (int) get_post_meta($contract_id, '_vgbm_contract_document_id', true);
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
