<?php
namespace VGBM\PM\Documents;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class Links {

    public static function link(int $doc_id, int $post_id): bool {
        if ($doc_id <= 0 || $post_id <= 0) { return false; }

        $doc = get_post($doc_id);
        $post = get_post($post_id);

        if (!$doc || $doc->post_type !== PostTypes::CPT_DOCUMENT) { return false; }
        if (!$post) { return false; }

        $linked = get_post_meta($doc_id, '_vgbm_linked_post_ids', true);
        if (!is_array($linked)) { $linked = []; }
        $linked[] = $post_id;
        $linked = array_values(array_unique(array_filter(array_map('intval', $linked), fn($v) => $v > 0)));
        update_post_meta($doc_id, '_vgbm_linked_post_ids', $linked);

        $doc_ids = get_post_meta($post_id, '_vgbm_document_ids', true);
        if (!is_array($doc_ids)) { $doc_ids = []; }
        $doc_ids[] = $doc_id;
        $doc_ids = array_values(array_unique(array_filter(array_map('intval', $doc_ids), fn($v) => $v > 0)));
        update_post_meta($post_id, '_vgbm_document_ids', $doc_ids);

        return true;
    }

    public static function unlink(int $doc_id, int $post_id): bool {
        if ($doc_id <= 0 || $post_id <= 0) { return false; }

        $doc = get_post($doc_id);
        $post = get_post($post_id);

        if (!$doc || $doc->post_type !== PostTypes::CPT_DOCUMENT) { return false; }
        if (!$post) { return false; }

        $linked = get_post_meta($doc_id, '_vgbm_linked_post_ids', true);
        if (!is_array($linked)) { $linked = []; }
        $linked = array_values(array_filter(array_map('intval', $linked), fn($v) => $v > 0 && (int)$v !== (int)$post_id));
        update_post_meta($doc_id, '_vgbm_linked_post_ids', $linked);

        $doc_ids = get_post_meta($post_id, '_vgbm_document_ids', true);
        if (!is_array($doc_ids)) { $doc_ids = []; }
        $doc_ids = array_values(array_filter(array_map('intval', $doc_ids), fn($v) => $v > 0 && (int)$v !== (int)$doc_id));
        update_post_meta($post_id, '_vgbm_document_ids', $doc_ids);

        return true;
    }

    /**
     * Get docs linked to a post (best-effort self-heal).
     */
    public static function get_docs_for_post(int $post_id, int $limit = 200): array {
        if ($post_id <= 0) { return []; }

        $doc_ids = get_post_meta($post_id, '_vgbm_document_ids', true);
        if (!is_array($doc_ids)) { $doc_ids = []; }
        $doc_ids = array_values(array_unique(array_filter(array_map('intval', $doc_ids), fn($v) => $v > 0)));

        // Also find documents that link to this post via document meta (in case of drift)
        $needle = 'i:' . (int)$post_id . ';';
        $extra = get_posts([
            'post_type' => PostTypes::CPT_DOCUMENT,
            'numberposts' => $limit,
            'post_status' => ['publish','draft','private'],
            'meta_query' => [
                [
                    'key' => '_vgbm_linked_post_ids',
                    'value' => $needle,
                    'compare' => 'LIKE',
                ],
            ],
            'fields' => 'ids',
        ]);

        $extra = is_array($extra) ? array_map('intval', $extra) : [];
        $all = array_values(array_unique(array_merge($doc_ids, $extra)));

        // Self-heal entity meta if needed
        if ($all !== $doc_ids) {
            update_post_meta($post_id, '_vgbm_document_ids', $all);
        }

        return $all;
    }
}
