<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class AllocationMetaBox {

    public static function split_methods(): array {
        return [
            'exclusive' => __('Exclusive (100% one contract)', 'vgbm-property-manager'),
            'equal' => __('Equal share', 'vgbm-property-manager'),
            'fixed_percent' => __('Fixed percentage', 'vgbm-property-manager'),
            'submeter' => __('Sub-meter based (future)', 'vgbm-property-manager'),
            'm2_ratio' => __('m² ratio (future)', 'vgbm-property-manager'),
        ];
    }

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_allocation', 'vgbm_pm_allocation_nonce');

        $utility_id = (int) get_post_meta($post->ID, '_vgbm_utility_id', true);
        $split = (string) get_post_meta($post->ID, '_vgbm_split_method', true);
        if ($split === '') { $split = 'exclusive'; }

        $effective_from = (string) get_post_meta($post->ID, '_vgbm_effective_from', true);
        $effective_to   = (string) get_post_meta($post->ID, '_vgbm_effective_to', true);

        $participants = get_post_meta($post->ID, '_vgbm_participants', true);
        if (!is_array($participants)) { $participants = []; }

        if (!$utility_id && !empty($_GET['vgbm_utility_id'])) {
            $utility_id = (int) $_GET['vgbm_utility_id'];
        }

        $utilities = get_posts([
            'post_type' => PostTypes::CPT_UTILITY,
            'numberposts' => 2000,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $contracts = get_posts([
            'post_type' => PostTypes::CPT_CONTRACT,
            'numberposts' => 2000,
            'post_status' => ['publish','draft','private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        if (!empty($_GET['vgbm_contract_id'])) {
            $prefill_contract_id = (int) $_GET['vgbm_contract_id'];
        } else {
            $prefill_contract_id = 0;
        }

        if (empty($participants)) {
            $participants = [['contract_id' => $prefill_contract_id, 'share' => 100]];
        } elseif ($prefill_contract_id && empty($participants[0]['contract_id'])) {
            $participants[0]['contract_id'] = $prefill_contract_id;
        }
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
                <label><strong><?php esc_html_e('Split method', 'vgbm-property-manager'); ?></strong></label><br>
                <select name="vgbm_split_method" class="widefat">
                    <?php foreach (self::split_methods() as $k => $label): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($split, $k); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Effective from', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_effective_from" value="<?php echo esc_attr($effective_from); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Effective to', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_effective_to" value="<?php echo esc_attr($effective_to); ?>">
            </span>
        </p>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Participants', 'vgbm-property-manager'); ?></h3>
        <p class="description"><?php esc_html_e('Select one or more contracts that use this utility. For shared meters, use Equal share or Fixed percentage.', 'vgbm-property-manager'); ?></p>

        <table class="widefat striped" id="vgbm-allocation-participants">
            <thead>
                <tr>
                    <th style="width:70%;"><?php esc_html_e('Contract', 'vgbm-property-manager'); ?></th>
                    <th style="width:20%;"><?php esc_html_e('Share (%)', 'vgbm-property-manager'); ?></th>
                    <th style="width:10%;"><?php esc_html_e('Remove', 'vgbm-property-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $i => $row): ?>
                    <?php $cid = (int) ($row['contract_id'] ?? 0); $share = (float) ($row['share'] ?? 0); ?>
                    <tr>
                        <td>
                            <select name="vgbm_participants[<?php echo (int)$i; ?>][contract_id]" class="widefat">
                                <option value="0"><?php esc_html_e('— Select —', 'vgbm-property-manager'); ?></option>
                                <?php foreach ($contracts as $c): ?>
                                    <option value="<?php echo esc_attr((string)$c->ID); ?>" <?php selected($cid, $c->ID); ?>>
                                        <?php echo esc_html($c->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" class="widefat" name="vgbm_participants[<?php echo (int)$i; ?>][share]" value="<?php echo esc_attr((string)$share); ?>">
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="button vgbm-remove-row">&times;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:8px;">
            <button type="button" class="button" id="vgbm-add-participant"><?php esc_html_e('Add participant', 'vgbm-property-manager'); ?></button>
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_allocation_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_allocation_nonce'])), 'vgbm_pm_save_allocation')) {
            return;
        }

        $utility_id = isset($_POST['vgbm_utility_id']) ? (int) $_POST['vgbm_utility_id'] : 0;
        update_post_meta($post_id, '_vgbm_utility_id', $utility_id);

        $split = isset($_POST['vgbm_split_method']) ? sanitize_text_field(wp_unslash($_POST['vgbm_split_method'])) : 'exclusive';
        if (!array_key_exists($split, self::split_methods())) { $split = 'exclusive'; }
        update_post_meta($post_id, '_vgbm_split_method', $split);

        $effective_from = isset($_POST['vgbm_effective_from']) ? sanitize_text_field(wp_unslash($_POST['vgbm_effective_from'])) : '';
        $effective_to   = isset($_POST['vgbm_effective_to']) ? sanitize_text_field(wp_unslash($_POST['vgbm_effective_to'])) : '';
        update_post_meta($post_id, '_vgbm_effective_from', $effective_from);
        update_post_meta($post_id, '_vgbm_effective_to', $effective_to);

        $raw = isset($_POST['vgbm_participants']) ? (array) $_POST['vgbm_participants'] : [];
        $participants = [];
        foreach ($raw as $row) {
            if (!is_array($row)) { continue; }
            $cid = isset($row['contract_id']) ? (int) $row['contract_id'] : 0;
            if ($cid <= 0) { continue; }
            $share = isset($row['share']) ? (float) $row['share'] : 0.0;
            $participants[] = ['contract_id' => $cid, 'share' => round(max(0.0, min(100.0, $share)), 2)];
        }

        if ($split === 'exclusive' && !empty($participants)) {
            $participants = [$participants[0]];
            $participants[0]['share'] = 100.0;
        }

        update_post_meta($post_id, '_vgbm_participants', $participants);

        // Helper meta for fast querying allocations by contract
        $contract_ids = array_values(array_unique(array_filter(array_map(fn($p) => (int)($p['contract_id'] ?? 0), $participants), fn($v) => $v > 0)));
        update_post_meta($post_id, '_vgbm_contract_ids_json', wp_json_encode($contract_ids));
    }
}
