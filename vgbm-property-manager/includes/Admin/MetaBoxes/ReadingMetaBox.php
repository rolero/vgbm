<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class ReadingMetaBox {

    public static function reading_types(): array {
        return [
            'manual' => __('Manual', 'vgbm-property-manager'),
            'smart' => __('Smart meter', 'vgbm-property-manager'),
            'estimate' => __('Estimate', 'vgbm-property-manager'),
            'correction' => __('Correction', 'vgbm-property-manager'),
        ];
    }

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_reading', 'vgbm_pm_reading_nonce');

        $utility_id = (int) get_post_meta($post->ID, '_vgbm_utility_id', true);
        $reading_date = (string) get_post_meta($post->ID, '_vgbm_reading_date', true);
        if ($reading_date === '') { $reading_date = current_time('Y-m-d'); }
        $value = (string) get_post_meta($post->ID, '_vgbm_value', true);
        $rtype = (string) get_post_meta($post->ID, '_vgbm_reading_type', true);
        if ($rtype === '') { $rtype = 'manual'; }
        $uom = (string) get_post_meta($post->ID, '_vgbm_uom', true);

        $photo_id = (int) get_post_meta($post->ID, '_vgbm_photo_id', true);

        if (!$utility_id && !empty($_GET['vgbm_utility_id'])) {
            $utility_id = (int) $_GET['vgbm_utility_id'];
        }

        if ($uom === '' && $utility_id) {
            $uom = (string) get_post_meta($utility_id, '_vgbm_uom', true);
        }
        if ($uom === '') { $uom = 'kwh'; }

        $utilities = get_posts([
            'post_type' => PostTypes::CPT_UTILITY,
            'numberposts' => 2000,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $photo_label = $photo_id ? (get_the_title($photo_id) ?: ('#' . $photo_id)) : '';
        ?>
        <p>
            <label><strong><?php esc_html_e('Utility / Meter', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_utility_id" class="widefat">
                <option value="0"><?php esc_html_e('— Select —', 'vgbm-property-manager'); ?></option>
                <?php foreach ($utilities as $u): ?>
                    <?php $kind = (string) get_post_meta($u->ID, '_vgbm_kind', true); ?>
                    <option value="<?php echo esc_attr((string)$u->ID); ?>" <?php selected($utility_id, $u->ID); ?>>
                        <?php echo esc_html($u->post_title . ($kind ? ' (' . $kind . ')' : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Reading date', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_reading_date" value="<?php echo esc_attr($reading_date); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Reading type', 'vgbm-property-manager'); ?></strong></label><br>
                <select name="vgbm_reading_type" class="widefat">
                    <?php foreach (self::reading_types() as $k => $label): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($rtype, $k); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Value', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="number" step="0.001" min="0" class="widefat" name="vgbm_value" value="<?php echo esc_attr($value); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Unit', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_uom" value="<?php echo esc_attr($uom); ?>">
                <span class="description"><?php esc_html_e('Defaults to the unit configured on the utility.', 'vgbm-property-manager'); ?></span>
            </span>
        </p>

        <hr>

        <p>
            <label><strong><?php esc_html_e('Photo (meter reading)', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="hidden" id="vgbm_reading_photo_id" name="vgbm_reading_photo_id" value="<?php echo esc_attr((string)$photo_id); ?>">
            <button type="button" class="button" id="vgbm_reading_photo_pick"><?php esc_html_e('Select / Upload photo', 'vgbm-property-manager'); ?></button>
            <button type="button" class="button" id="vgbm_reading_photo_clear" style="margin-left:6px;"><?php esc_html_e('Clear', 'vgbm-property-manager'); ?></button>
            <span id="vgbm_reading_photo_label" style="margin-left:10px;">
                <?php echo $photo_id ? esc_html($photo_label) : esc_html__('No photo selected', 'vgbm-property-manager'); ?>
            </span>
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_reading_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_reading_nonce'])), 'vgbm_pm_save_reading')) {
            return;
        }

        $utility_id = isset($_POST['vgbm_utility_id']) ? (int) $_POST['vgbm_utility_id'] : 0;
        update_post_meta($post_id, '_vgbm_utility_id', $utility_id);

        $reading_date = isset($_POST['vgbm_reading_date']) ? sanitize_text_field(wp_unslash($_POST['vgbm_reading_date'])) : '';
        update_post_meta($post_id, '_vgbm_reading_date', $reading_date);

        $rtype = isset($_POST['vgbm_reading_type']) ? sanitize_text_field(wp_unslash($_POST['vgbm_reading_type'])) : 'manual';
        if (!array_key_exists($rtype, self::reading_types())) { $rtype = 'manual'; }
        update_post_meta($post_id, '_vgbm_reading_type', $rtype);

        $value = isset($_POST['vgbm_value']) ? (float) $_POST['vgbm_value'] : 0.0;
        update_post_meta($post_id, '_vgbm_value', round(max(0.0, $value), 3));

        $uom = isset($_POST['vgbm_uom']) ? sanitize_text_field(wp_unslash($_POST['vgbm_uom'])) : '';
        if ($uom === '' && $utility_id) {
            $uom = (string) get_post_meta($utility_id, '_vgbm_uom', true);
        }
        update_post_meta($post_id, '_vgbm_uom', $uom);

        $photo_id = isset($_POST['vgbm_reading_photo_id']) ? (int) $_POST['vgbm_reading_photo_id'] : 0;
        update_post_meta($post_id, '_vgbm_photo_id', $photo_id);
    }
}
