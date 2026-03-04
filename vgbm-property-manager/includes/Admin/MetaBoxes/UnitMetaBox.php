<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class UnitMetaBox {

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_unit', 'vgbm_pm_unit_nonce');

        $property_id = (int) get_post_meta($post->ID, '_vgbm_property_id', true);
        $unit_no     = get_post_meta($post->ID, '_vgbm_unit_no', true);
        $area_m2     = get_post_meta($post->ID, '_vgbm_area_m2', true);
        $rent_amount = get_post_meta($post->ID, '_vgbm_rent_amount', true);

        $properties = get_posts([
            'post_type' => PostTypes::CPT_PROPERTY,
            'numberposts' => 200,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ?>
        <p>
            <label><strong><?php esc_html_e('Property', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_property_id" class="widefat">
                <option value="0"><?php esc_html_e('— Select —', 'vgbm-property-manager'); ?></option>
                <?php foreach ($properties as $p): ?>
                    <option value="<?php echo esc_attr((string)$p->ID); ?>" <?php selected($property_id, $p->ID); ?>>
                        <?php echo esc_html($p->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Unit number', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_unit_no" value="<?php echo esc_attr($unit_no); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Area (m²)', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_area_m2" value="<?php echo esc_attr($area_m2); ?>">
            </span>
        </p>
        <p>
            <label><strong><?php esc_html_e('Rent amount (e.g. 1250.00)', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="text" class="widefat" name="vgbm_rent_amount" value="<?php echo esc_attr($rent_amount); ?>">
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_unit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_unit_nonce'])), 'vgbm_pm_save_unit')) {
            return;
        }

        $property_id = isset($_POST['vgbm_property_id']) ? (int) $_POST['vgbm_property_id'] : 0;
        update_post_meta($post_id, '_vgbm_property_id', $property_id);

        $unit_no = isset($_POST['vgbm_unit_no']) ? sanitize_text_field(wp_unslash($_POST['vgbm_unit_no'])) : '';
        update_post_meta($post_id, '_vgbm_unit_no', $unit_no);

        $area_m2 = isset($_POST['vgbm_area_m2']) ? sanitize_text_field(wp_unslash($_POST['vgbm_area_m2'])) : '';
        update_post_meta($post_id, '_vgbm_area_m2', $area_m2);

        $rent_amount = isset($_POST['vgbm_rent_amount']) ? sanitize_text_field(wp_unslash($_POST['vgbm_rent_amount'])) : '';
        update_post_meta($post_id, '_vgbm_rent_amount', $rent_amount);
    }
}
