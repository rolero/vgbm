<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class UtilityMetaBox {

    public static function kinds(): array {
        return [
            'electricity' => __('Electricity', 'vgbm-property-manager'),
            'gas' => __('Gas', 'vgbm-property-manager'),
            'water' => __('Water', 'vgbm-property-manager'),
            'district_heating' => __('District heating', 'vgbm-property-manager'),
            'internet' => __('Internet', 'vgbm-property-manager'),
            'service' => __('Service', 'vgbm-property-manager'),
        ];
    }

    public static function units(): array {
        return [
            'kwh' => __('kWh', 'vgbm-property-manager'),
            'm3'  => __('m³', 'vgbm-property-manager'),
            'gj'  => __('GJ', 'vgbm-property-manager'),
            'l'   => __('L', 'vgbm-property-manager'),
            'pcs' => __('pcs', 'vgbm-property-manager'),
        ];
    }

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_utility', 'vgbm_pm_utility_nonce');

        $kind = (string) get_post_meta($post->ID, '_vgbm_kind', true);
        if ($kind === '') { $kind = 'electricity'; }

        $property_id = (int) get_post_meta($post->ID, '_vgbm_property_id', true);
        $unit_id     = (int) get_post_meta($post->ID, '_vgbm_unit_id', true);

        $ean = (string) get_post_meta($post->ID, '_vgbm_ean', true);
        $meter_number = (string) get_post_meta($post->ID, '_vgbm_meter_number', true);
        $opname_number = (string) get_post_meta($post->ID, '_vgbm_opname_number', true);

        $uom = (string) get_post_meta($post->ID, '_vgbm_uom', true);
        if ($uom === '') {
            if ($kind === 'electricity') { $uom = 'kwh'; }
            elseif ($kind === 'gas' || $kind === 'water') { $uom = 'm3'; }
            elseif ($kind === 'district_heating') { $uom = 'gj'; }
            else { $uom = 'pcs'; }
        }

        $parent_id = (int) get_post_meta($post->ID, '_vgbm_parent_utility_id', true);

        $photo_id = (int) get_post_meta($post->ID, '_vgbm_photo_id', true);

        $properties = get_posts([
            'post_type' => PostTypes::CPT_PROPERTY,
            'numberposts' => 500,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $units = get_posts([
            'post_type' => PostTypes::CPT_UNIT,
            'numberposts' => 2000,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $utilities = get_posts([
            'post_type' => PostTypes::CPT_UTILITY,
            'numberposts' => 2000,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'title',
            'order' => 'ASC',
            'exclude' => [$post->ID],
        ]);

        $photo_label = $photo_id ? (get_the_title($photo_id) ?: ('#' . $photo_id)) : '';
        ?>
        <p>
            <label><strong><?php esc_html_e('Type', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_kind" class="widefat">
                <?php foreach (self::kinds() as $k => $label): ?>
                    <option value="<?php echo esc_attr($k); ?>" <?php selected($kind, $k); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Property', 'vgbm-property-manager'); ?></strong></label><br>
                <select name="vgbm_property_id" class="widefat">
                    <option value="0"><?php esc_html_e('— Select —', 'vgbm-property-manager'); ?></option>
                    <?php foreach ($properties as $p): ?>
                        <option value="<?php echo esc_attr((string)$p->ID); ?>" <?php selected($property_id, $p->ID); ?>>
                            <?php echo esc_html($p->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="description"><?php esc_html_e('Use this for a main connection on the building/property.', 'vgbm-property-manager'); ?></span>
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Unit (optional)', 'vgbm-property-manager'); ?></strong></label><br>
                <select name="vgbm_unit_id" class="widefat">
                    <option value="0"><?php esc_html_e('— None —', 'vgbm-property-manager'); ?></option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?php echo esc_attr((string)$u->ID); ?>" <?php selected($unit_id, $u->ID); ?>>
                            <?php echo esc_html($u->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="description"><?php esc_html_e('Use this for unit-level meters (sub-meters).', 'vgbm-property-manager'); ?></span>
            </span>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('EAN (electricity/gas)', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_ean" value="<?php echo esc_attr($ean); ?>" placeholder="18 digits">
                <span class="description"><?php esc_html_e('In NL, the EAN identifies the connection point for electricity/gas (not the meter itself).', 'vgbm-property-manager'); ?></span>
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Meter number', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_meter_number" value="<?php echo esc_attr($meter_number); ?>">
                <span class="description"><?php esc_html_e('The meter number is on the meter.', 'vgbm-property-manager'); ?></span>
            </span>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Water opname number (optional)', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_opname_number" value="<?php echo esc_attr($opname_number); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Unit of measure', 'vgbm-property-manager'); ?></strong></label><br>
                <select name="vgbm_uom" class="widefat">
                    <?php foreach (self::units() as $k => $label): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($uom, $k); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </p>

        <p>
            <label><strong><?php esc_html_e('Parent meter (optional)', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_parent_utility_id" class="widefat">
                <option value="0"><?php esc_html_e('— None (main meter) —', 'vgbm-property-manager'); ?></option>
                <?php foreach ($utilities as $m): ?>
                    <option value="<?php echo esc_attr((string)$m->ID); ?>" <?php selected($parent_id, $m->ID); ?>>
                        <?php echo esc_html($m->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description"><?php esc_html_e('Use this to model a main meter on the property with sub-meters per unit.', 'vgbm-property-manager'); ?></span>
        </p>

        <hr>

        <p>
            <label><strong><?php esc_html_e('Photo (meter / connection)', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="hidden" id="vgbm_utility_photo_id" name="vgbm_utility_photo_id" value="<?php echo esc_attr((string)$photo_id); ?>">
            <button type="button" class="button" id="vgbm_utility_photo_pick"><?php esc_html_e('Select / Upload photo', 'vgbm-property-manager'); ?></button>
            <button type="button" class="button" id="vgbm_utility_photo_clear" style="margin-left:6px;"><?php esc_html_e('Clear', 'vgbm-property-manager'); ?></button>
            <span id="vgbm_utility_photo_label" style="margin-left:10px;">
                <?php echo $photo_id ? esc_html($photo_label) : esc_html__('No photo selected', 'vgbm-property-manager'); ?>
            </span>
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_utility_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_utility_nonce'])), 'vgbm_pm_save_utility')) {
            return;
        }

        $kind = isset($_POST['vgbm_kind']) ? sanitize_text_field(wp_unslash($_POST['vgbm_kind'])) : 'electricity';
        if (!array_key_exists($kind, self::kinds())) { $kind = 'electricity'; }
        update_post_meta($post_id, '_vgbm_kind', $kind);

        $property_id = isset($_POST['vgbm_property_id']) ? (int) $_POST['vgbm_property_id'] : 0;
        $unit_id     = isset($_POST['vgbm_unit_id']) ? (int) $_POST['vgbm_unit_id'] : 0;
        update_post_meta($post_id, '_vgbm_property_id', $property_id);
        update_post_meta($post_id, '_vgbm_unit_id', $unit_id);

        $ean = isset($_POST['vgbm_ean']) ? preg_replace('/\s+/', '', sanitize_text_field(wp_unslash($_POST['vgbm_ean']))) : '';
        update_post_meta($post_id, '_vgbm_ean', $ean);

        $meter_number = isset($_POST['vgbm_meter_number']) ? sanitize_text_field(wp_unslash($_POST['vgbm_meter_number'])) : '';
        update_post_meta($post_id, '_vgbm_meter_number', $meter_number);

        $opname_number = isset($_POST['vgbm_opname_number']) ? preg_replace('/\s+/', '', sanitize_text_field(wp_unslash($_POST['vgbm_opname_number']))) : '';
        update_post_meta($post_id, '_vgbm_opname_number', $opname_number);

        $uom = isset($_POST['vgbm_uom']) ? sanitize_text_field(wp_unslash($_POST['vgbm_uom'])) : 'kwh';
        if (!array_key_exists($uom, self::units())) { $uom = 'kwh'; }
        update_post_meta($post_id, '_vgbm_uom', $uom);

        $parent_id = isset($_POST['vgbm_parent_utility_id']) ? (int) $_POST['vgbm_parent_utility_id'] : 0;
        if ($parent_id === $post_id) { $parent_id = 0; }
        update_post_meta($post_id, '_vgbm_parent_utility_id', $parent_id);

        $photo_id = isset($_POST['vgbm_utility_photo_id']) ? (int) $_POST['vgbm_utility_photo_id'] : 0;
        update_post_meta($post_id, '_vgbm_photo_id', $photo_id);
    }
}
