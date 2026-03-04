<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class UtilityReadingsBox {

    public function render(\WP_Post $post): void {
        $q = new \WP_Query([
            'post_type' => PostTypes::CPT_READING,
            'posts_per_page' => 10,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_vgbm_utility_id',
                    'value' => $post->ID,
                    'compare' => '=',
                ],
            ],
        ]);

        $add_url = admin_url('post-new.php?post_type=' . PostTypes::CPT_READING . '&vgbm_utility_id=' . (int)$post->ID);

        echo '<p><a class="button button-primary" href="' . esc_url($add_url) . '">' . esc_html__('Add meter reading', 'vgbm-property-manager') . '</a></p>';

        if (!$q->have_posts()) {
            echo '<p>' . esc_html__('No readings yet.', 'vgbm-property-manager') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Date', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Value', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Unit', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Type', 'vgbm-property-manager') . '</th>';
        echo '</tr></thead><tbody>';

        while ($q->have_posts()) {
            $q->the_post();
            $rid = get_the_ID();
            $d = (string) get_post_meta($rid, '_vgbm_reading_date', true);
            $val = (string) get_post_meta($rid, '_vgbm_value', true);
            $uom = (string) get_post_meta($rid, '_vgbm_uom', true);
            $rt = (string) get_post_meta($rid, '_vgbm_reading_type', true);

            $edit = admin_url('post.php?post=' . $rid . '&action=edit');

            echo '<tr>';
            echo '<td><a href="' . esc_url($edit) . '">' . esc_html($d ?: '—') . '</a></td>';
            echo '<td>' . esc_html($val ?: '—') . '</td>';
            echo '<td>' . esc_html($uom ?: '—') . '</td>';
            echo '<td>' . esc_html($rt ?: '—') . '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();

        echo '</tbody></table>';
    }
}
