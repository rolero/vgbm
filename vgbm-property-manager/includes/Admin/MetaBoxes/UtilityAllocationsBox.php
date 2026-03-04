<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class UtilityAllocationsBox {

    public function render(\WP_Post $post): void {
        $q = new \WP_Query([
            'post_type' => PostTypes::CPT_ALLOCATION,
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

        $add_url = admin_url('post-new.php?post_type=' . PostTypes::CPT_ALLOCATION . '&vgbm_utility_id=' . (int)$post->ID);

        echo '<p><a class="button button-primary" href="' . esc_url($add_url) . '">' . esc_html__('Create allocation', 'vgbm-property-manager') . '</a></p>';

        if (!$q->have_posts()) {
            echo '<p>' . esc_html__('No allocations yet.', 'vgbm-property-manager') . '</p>';
            return;
        }

        echo '<ul>';
        while ($q->have_posts()) {
            $q->the_post();
            $aid = get_the_ID();
            $split = (string) get_post_meta($aid, '_vgbm_split_method', true);
            $edit = admin_url('post.php?post=' . $aid . '&action=edit');
            echo '<li><a href="' . esc_url($edit) . '">' . esc_html(get_the_title()) . '</a>' . ($split ? ' <small>(' . esc_html($split) . ')</small>' : '') . '</li>';
        }
        wp_reset_postdata();
        echo '</ul>';
    }
}
