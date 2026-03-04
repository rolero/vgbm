<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\Documents\Config;
use VGBM\PM\Documents\Links;
use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class EntityDocumentsBox {

    public function render(\WP_Post $post): void {
        Config::ensure_defaults();

        $doc_ids = Links::get_docs_for_post((int)$post->ID, 200);
        $doc_ids = array_values(array_unique(array_filter(array_map('intval', $doc_ids), fn($v) => $v > 0)));

        $add_url = admin_url('post-new.php?post_type=' . PostTypes::CPT_DOCUMENT . '&vgbm_link_post_id=' . (int)$post->ID);
        $manage_url = admin_url('edit.php?post_type=' . PostTypes::CPT_DOCUMENT);

        $nonce = wp_create_nonce('vgbm_pm_doc_links');

        echo '<div class="vgbm-doc-links-box" data-post-id="' . esc_attr((string)$post->ID) . '" data-nonce="' . esc_attr($nonce) . '">';

        echo '<p>';
        echo '<a class="button button-primary" href="' . esc_url($add_url) . '">' . esc_html__('Add document', 'vgbm-property-manager') . '</a> ';
        echo '<a class="button" href="' . esc_url($manage_url) . '">' . esc_html__('All documents', 'vgbm-property-manager') . '</a>';
        echo '</p>';

        echo '<hr style="margin:10px 0;">';
        echo '<p style="margin:0 0 6px 0;"><strong>' . esc_html__('Attach existing document', 'vgbm-property-manager') . '</strong></p>';
        echo '<input type="text" class="widefat vgbm-doc-search-q" placeholder="' . esc_attr__('Search documents…', 'vgbm-property-manager') . '">';
        echo '<div class="vgbm-doc-search-results" style="margin-top:8px;"></div>';
        echo '<p class="description" style="margin-top:6px;">' . esc_html__('Type at least 2 characters. Click Attach to link an existing document to this record. The same document can be linked to multiple records.', 'vgbm-property-manager') . '</p>';

        echo '<hr style="margin:10px 0;">';

        echo '<p style="margin:0 0 6px 0;"><strong>' . esc_html__('Linked documents', 'vgbm-property-manager') . '</strong></p>';

        echo '<table class="widefat striped vgbm-linked-docs">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Document', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Type', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Privacy', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('File', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Action', 'vgbm-property-manager') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($doc_ids)) {
            echo '<tr class="no-items"><td colspan="5">' . esc_html__('No documents linked yet.', 'vgbm-property-manager') . '</td></tr>';
            echo '</tbody></table></div>';
            return;
        }

        $docs = get_posts([
            'post_type' => PostTypes::CPT_DOCUMENT,
            'post__in' => $doc_ids,
            'numberposts' => 200,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($docs)) {
            echo '<tr class="no-items"><td colspan="5">' . esc_html__('No documents found.', 'vgbm-property-manager') . '</td></tr>';
            echo '</tbody></table></div>';
            return;
        }

        foreach ($docs as $d) {
            $edit = admin_url('post.php?post=' . (int)$d->ID . '&action=edit');
            $type = (string) get_post_meta($d->ID, '_vgbm_doc_type', true);
            $label = (string) get_post_meta($d->ID, '_vgbm_doc_label', true);
            $cur_att = (int) get_post_meta($d->ID, '_vgbm_current_attachment_id', true);

            $download = $cur_att ? add_query_arg(['vgbm_doc_file' => (int)$d->ID], home_url('/')) : '';

            echo '<tr data-doc-id="' . esc_attr((string)$d->ID) . '">';
            echo '<td><a href="' . esc_url($edit) . '">' . esc_html($d->post_title ?: ('#' . $d->ID)) . '</a></td>';
            echo '<td>' . esc_html(Config::type_name($type)) . '</td>';
            echo '<td>' . esc_html(Config::label_name($label)) . '</td>';
            if ($download) {
                echo '<td><a href="' . esc_url($download) . '" target="_blank" rel="noopener">' . esc_html__('Download', 'vgbm-property-manager') . '</a></td>';
            } else {
                echo '<td>—</td>';
            }
            echo '<td><a href="#" class="vgbm-doc-detach">' . esc_html__('Detach', 'vgbm-property-manager') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}
