<?php
namespace VGBM\PM\Admin;

use VGBM\PM\Documents\Config;
use VGBM\PM\Documents\Links;
use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class DocumentLinksAjax {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('wp_ajax_vgbm_pm_doc_search', [$this, 'search']);
        add_action('wp_ajax_vgbm_pm_doc_attach', [$this, 'attach']);
        add_action('wp_ajax_vgbm_pm_doc_detach', [$this, 'detach']);
    }

    private function can_edit_both(int $doc_id, int $post_id): bool {
        if ($doc_id <= 0 || $post_id <= 0) { return false; }
        return current_user_can('edit_post', $doc_id) && current_user_can('edit_post', $post_id);
    }

    public function search(): void {
        check_ajax_referer('vgbm_pm_doc_links', 'nonce');

        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'no_access'], 403);
        }

        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $q = trim($q);

        if (strlen($q) < 2) {
            wp_send_json_success(['items' => []]);
        }

        $docs = get_posts([
            'post_type' => PostTypes::CPT_DOCUMENT,
            'numberposts' => 20,
            'post_status' => ['publish','draft','private'],
            's' => $q,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $items = [];
        foreach ($docs as $d) {
            $type = (string) get_post_meta($d->ID, '_vgbm_doc_type', true);
            $label = (string) get_post_meta($d->ID, '_vgbm_doc_label', true);
            $cur_att = (int) get_post_meta($d->ID, '_vgbm_current_attachment_id', true);

            $items[] = [
                'id' => (int)$d->ID,
                'title' => (string)($d->post_title ?: ('#' . $d->ID)),
                'type' => $type,
                'type_name' => Config::type_name($type),
                'label' => $label,
                'label_name' => Config::label_name($label),
                'has_file' => $cur_att > 0,
            ];
        }

        wp_send_json_success(['items' => $items]);
    }

    public function attach(): void {
        check_ajax_referer('vgbm_pm_doc_links', 'nonce');

        $doc_id = isset($_POST['doc_id']) ? (int) $_POST['doc_id'] : 0;
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

        if (!$this->can_edit_both($doc_id, $post_id)) {
            wp_send_json_error(['message' => 'no_access'], 403);
        }

        $ok = Links::link($doc_id, $post_id);

        if (!$ok) {
            wp_send_json_error(['message' => 'failed'], 400);
        }

        $d = get_post($doc_id);
        $type = (string) get_post_meta($doc_id, '_vgbm_doc_type', true);
        $label = (string) get_post_meta($doc_id, '_vgbm_doc_label', true);
        $cur_att = (int) get_post_meta($doc_id, '_vgbm_current_attachment_id', true);

        $download = $cur_att ? add_query_arg(['vgbm_doc_file' => $doc_id], home_url('/')) : '';
        $edit = admin_url('post.php?post=' . (int)$doc_id . '&action=edit');

        wp_send_json_success([
            'id' => $doc_id,
            'title' => (string)($d ? ($d->post_title ?: ('#' . $doc_id)) : ('#' . $doc_id)),
            'type_name' => Config::type_name($type),
            'label_name' => Config::label_name($label),
            'edit_url' => $edit,
            'download_url' => $download,
        ]);
    }

    public function detach(): void {
        check_ajax_referer('vgbm_pm_doc_links', 'nonce');

        $doc_id = isset($_POST['doc_id']) ? (int) $_POST['doc_id'] : 0;
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

        if (!$this->can_edit_both($doc_id, $post_id)) {
            wp_send_json_error(['message' => 'no_access'], 403);
        }

        $ok = Links::unlink($doc_id, $post_id);
        if (!$ok) {
            wp_send_json_error(['message' => 'failed'], 400);
        }

        wp_send_json_success(['ok' => true]);
    }
}
