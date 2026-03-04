<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\RentCalculator;

if (!defined('ABSPATH')) { exit; }

final class ContractMetaBox {

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_contract', 'vgbm_pm_contract_nonce');

        // Units (multi) + legacy
        $unit_ids = get_post_meta($post->ID, '_vgbm_unit_ids', true);
        if (!is_array($unit_ids) || empty($unit_ids)) {
            $legacy_unit = (int) get_post_meta($post->ID, '_vgbm_unit_id', true);
            $unit_ids = $legacy_unit ? [$legacy_unit] : [];
        }
        $unit_ids = array_values(array_filter(array_map('intval', $unit_ids), fn($v) => $v > 0));

        // Renters (profiles)
        $renter_ids = get_post_meta($post->ID, '_vgbm_renter_ids', true);
        if (!is_array($renter_ids)) { $renter_ids = []; }

        $start    = (string) get_post_meta($post->ID, '_vgbm_start_date', true);
        $end      = (string) get_post_meta($post->ID, '_vgbm_end_date', true);
        $status   = (string) get_post_meta($post->ID, '_vgbm_contract_status', true);
        if ($status === '') { $status = 'active'; }

        // Billing rules
        $due_day = (int) get_post_meta($post->ID, '_vgbm_due_day', true);
        if ($due_day < 1 || $due_day > 28) { $due_day = 1; }
        $prorate_meta = get_post_meta($post->ID, '_vgbm_bill_prorate', true);
        $prorate_enabled = ($prorate_meta === '' || (int)$prorate_meta === 1);

        // Rent elements
        $rent_elements = RentCalculator::normalize_elements(get_post_meta($post->ID, '_vgbm_rent_elements', true));
        if (empty($rent_elements)) {
            $rent_elements = [
                ['label' => __('Base rent', 'vgbm-property-manager'), 'amount' => 0.0, 'indexable' => true],
            ];
        }

        $history = get_post_meta($post->ID, '_vgbm_indexation_history', true);
        if (!is_array($history)) { $history = []; }
        $last_year = RentCalculator::last_year($history);

        $doc_id   = (int) get_post_meta($post->ID, '_vgbm_contract_document_id', true);

        $units = get_posts([
            'post_type' => PostTypes::CPT_UNIT,
            'numberposts' => 2000,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $renters = get_posts([
            'post_type' => PostTypes::CPT_RENTER,
            'numberposts' => 2000,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $statuses = [
            'active' => __('Active', 'vgbm-property-manager'),
            'ended'  => __('Ended', 'vgbm-property-manager'),
            'draft'  => __('Draft', 'vgbm-property-manager'),
        ];

        $doc_name = $doc_id ? get_the_title($doc_id) : '';
        $download_url = $doc_id ? add_query_arg(['vgbm_contract_doc' => $post->ID], home_url('/')) : '';

        ?>
        <p>
            <label><strong><?php esc_html_e('Unit(s)', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_unit_ids[]" class="widefat" multiple size="6">
                <?php foreach ($units as $u): ?>
                    <option value="<?php echo esc_attr((string)$u->ID); ?>" <?php selected(in_array((int)$u->ID, $unit_ids, true)); ?>>
                        <?php echo esc_html($u->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description"><?php esc_html_e('A contract can cover multiple units/addresses (common in NL: 2 addresses under one tenant/contract).', 'vgbm-property-manager'); ?></span>
        </p>

        <p>
            <label><strong><?php esc_html_e('Renter(s)', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_renter_ids[]" multiple size="6" class="widefat">
                <?php foreach ($renters as $r): ?>
                    <?php
                        $is_selected = in_array((int)$r->ID, $renter_ids, true);
                        $email = (string) get_post_meta($r->ID, '_vgbm_email', true);
                        $label = $r->post_title . ($email ? (' - ' . $email) : '');
                    ?>
                    <option value="<?php echo esc_attr((string)$r->ID); ?>" <?php selected($is_selected); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description">
                <?php esc_html_e('Renters are stored as renter profiles. Optionally link a renter to a WordPress user to enable portal login.', 'vgbm-property-manager'); ?>
            </span>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Start date', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_start_date" value="<?php echo esc_attr($start); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('End date', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_end_date" value="<?php echo esc_attr($end); ?>">
            </span>
        </p>

        <p>
            <label><strong><?php esc_html_e('Status', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_contract_status" class="widefat">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Billing rules', 'vgbm-property-manager'); ?></h3>
        <p class="description"><?php esc_html_e('Contract-specific settings for monthly charges.', 'vgbm-property-manager'); ?></p>

        <p style="display:flex; gap:10px; align-items:flex-end;">
            <span style="flex:0 0 220px;">
                <label><strong><?php esc_html_e('Due day (1-28)', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="number" min="1" max="28" class="widefat" name="vgbm_due_day" value="<?php echo esc_attr((string)$due_day); ?>">
                <span class="description"><?php esc_html_e('If empty, the default due day is used when generating charges.', 'vgbm-property-manager'); ?></span>
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Prorate partial months', 'vgbm-property-manager'); ?></strong></label><br>
                <label>
                    <input type="checkbox" name="vgbm_bill_prorate" value="1" <?php checked($prorate_enabled); ?>>
                    <?php esc_html_e('Prorate first/last month based on active days (start/end dates).', 'vgbm-property-manager'); ?>
                </label>
            </span>
        </p>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Rent elements (monthly)', 'vgbm-property-manager'); ?></h3>
        <p class="description"><?php esc_html_e('Define the rent as a sum of elements (e.g. base rent, service costs, utilities). Mark elements that should be indexed yearly.', 'vgbm-property-manager'); ?></p>

        <table class="widefat striped" id="vgbm-rent-elements" style="margin-top:8px;">
            <thead>
                <tr>
                    <th style="width:55%;"><?php esc_html_e('Element', 'vgbm-property-manager'); ?></th>
                    <th style="width:20%;"><?php esc_html_e('Amount (EUR)', 'vgbm-property-manager'); ?></th>
                    <th style="width:15%;"><?php esc_html_e('Index yearly', 'vgbm-property-manager'); ?></th>
                    <th style="width:10%;"><?php esc_html_e('Remove', 'vgbm-property-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rent_elements as $i => $el): ?>
                    <tr>
                        <td>
                            <input type="text" class="widefat" name="vgbm_rent_elements[<?php echo (int)$i; ?>][label]" value="<?php echo esc_attr((string)$el['label']); ?>">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="widefat" name="vgbm_rent_elements[<?php echo (int)$i; ?>][amount]" value="<?php echo esc_attr((string)$el['amount']); ?>">
                        </td>
                        <td style="text-align:center;">
                            <input type="checkbox" name="vgbm_rent_elements[<?php echo (int)$i; ?>][indexable]" value="1" <?php checked(!empty($el['indexable'])); ?>>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="button vgbm-remove-row">&times;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:8px;">
            <button type="button" class="button" id="vgbm-add-rent-element"><?php esc_html_e('Add rent element', 'vgbm-property-manager'); ?></button>
        </p>

        <?php $current = RentCalculator::current_total($post->ID); ?>
        <p>
            <strong><?php esc_html_e('Current total (monthly):', 'vgbm-property-manager'); ?></strong>
            <?php echo esc_html(number_format_i18n($current['total'], 2)); ?>
            <?php if (!empty($current['year'])): ?>
                <span class="description"><?php echo esc_html(sprintf(__('(latest indexation year: %d)', 'vgbm-property-manager'), (int)$current['year'])); ?></span>
            <?php endif; ?>
        </p>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Utilities & allocations', 'vgbm-property-manager'); ?></h3>
        <p class="description">
            <?php esc_html_e('Utilities (EAN/meters) are linked to contracts via Utility Allocations (exclusive/shared rules). Utilities shown here are derived automatically from allocations.', 'vgbm-property-manager'); ?>
        </p>

        <?php
            $allocations = get_posts([
                'post_type' => PostTypes::CPT_ALLOCATION,
                'numberposts' => 2000,
                'post_status' => ['publish','draft','private'],
                'meta_query' => [
                    [
                        'key' => '_vgbm_contract_ids_json',
                        'value' => '"' . (string)$post->ID . '"',
                        'compare' => 'LIKE',
                    ],
                ],
                'orderby' => 'title',
                'order' => 'ASC',
            ]);
if (empty($allocations)) {
    // Fallback for older allocations saved before _vgbm_contract_ids_json existed (serialized participants search)
    $needle = 's:11:"contract_id";i:' . (int)$post->ID . ';';
    $allocations = get_posts([
        'post_type' => PostTypes::CPT_ALLOCATION,
        'numberposts' => 2000,
        'post_status' => ['publish','draft','private'],
        'meta_query' => [
            [
                'key' => '_vgbm_participants',
                'value' => $needle,
                'compare' => 'LIKE',
            ],
        ],
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
}


            $utility_ids = [];
            foreach ($allocations as $a) {
                $uid = (int) get_post_meta($a->ID, '_vgbm_utility_id', true);
                if ($uid) { $utility_ids[] = $uid; }
            }
            $utility_ids = array_values(array_unique(array_filter($utility_ids)));
        ?>

        <p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=' . PostTypes::CPT_ALLOCATION . '&vgbm_contract_id=' . (int)$post->ID)); ?>">
                <?php esc_html_e('Create allocation for this contract', 'vgbm-property-manager'); ?>
            </a>
            <a class="button button-secondary" style="margin-left:6px;" href="<?php echo esc_url(admin_url('edit.php?post_type=' . PostTypes::CPT_ALLOCATION)); ?>">
                <?php esc_html_e('Manage allocations', 'vgbm-property-manager'); ?>
            </a>
        </p>

        <?php if (empty($allocations)): ?>
            <p><?php esc_html_e('No allocations found for this contract yet.', 'vgbm-property-manager'); ?></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Allocation', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('Utility', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('Split', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('Your share', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('Effective', 'vgbm-property-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allocations as $a): ?>
                        <?php
                            $edit = admin_url('post.php?post=' . (int)$a->ID . '&action=edit');
                            $uid = (int) get_post_meta($a->ID, '_vgbm_utility_id', true);
                            $split = (string) get_post_meta($a->ID, '_vgbm_split_method', true);

                            $parts = get_post_meta($a->ID, '_vgbm_participants', true);
                            if (!is_array($parts)) { $parts = []; }

                            $share_display = '—';
                            if ($split === 'exclusive') {
                                $share_display = '100%';
                            } elseif ($split === 'equal' && !empty($parts)) {
                                $share_display = round(100 / max(1, count($parts)), 2) . '%';
                            } elseif ($split === 'fixed_percent') {
                                foreach ($parts as $p) {
                                    if ((int)($p['contract_id'] ?? 0) === (int)$post->ID) {
                                        $share_display = ((float)($p['share'] ?? 0)) . '%';
                                        break;
                                    }
                                }
                            }

                            $f = (string) get_post_meta($a->ID, '_vgbm_effective_from', true);
                            $t = (string) get_post_meta($a->ID, '_vgbm_effective_to', true);
                            $effective = ($f || $t) ? (($f ?: '—') . ' → ' . ($t ?: '—')) : '—';
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url($edit); ?>"><?php echo esc_html($a->post_title); ?></a></td>
                            <td><?php echo $uid ? esc_html(get_the_title($uid)) : '—'; ?></td>
                            <td><?php echo $split ? esc_html($split) : '—'; ?></td>
                            <td><?php echo esc_html($share_display); ?></td>
                            <td><?php echo esc_html($effective); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:8px;">
                <strong><?php esc_html_e('Derived utilities:', 'vgbm-property-manager'); ?></strong>
                <?php
                    if (empty($utility_ids)) {
                        echo esc_html__('—', 'vgbm-property-manager');
                    } else {
                        $names = array_map(fn($id) => get_the_title($id), $utility_ids);
                        $names = array_values(array_filter($names));
                        echo $names ? esc_html(implode(', ', $names)) : esc_html__('—', 'vgbm-property-manager');
                    }
                ?>
            </p>
        <?php endif; ?>

        <?php if ($last_year > 0): ?>
            <details style="margin-top:10px;">
                <summary><strong><?php esc_html_e('Indexation history', 'vgbm-property-manager'); ?></strong></summary>
                <div style="margin-top:8px;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Year', 'vgbm-property-manager'); ?></th>
                                <th><?php esc_html_e('Rate (%)', 'vgbm-property-manager'); ?></th>
                                <th><?php esc_html_e('New total (monthly)', 'vgbm-property-manager'); ?></th>
                                <th><?php esc_html_e('Applied at', 'vgbm-property-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $years = array_map('intval', array_keys($history));
                                rsort($years);
                                foreach ($years as $y):
                                    $row = $history[(string)$y] ?? [];
                            ?>
                                <tr>
                                    <td><?php echo esc_html((string)$y); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float)($row['rate'] ?? 0), 2)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float)($row['new_total'] ?? 0), 2)); ?></td>
                                    <td>
                                        <?php
                                            $ts = (int)($row['applied_at'] ?? 0);
                                            echo $ts ? esc_html(date_i18n('Y-m-d H:i', $ts)) : '—';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endif; ?>

        <hr>

        <p>
            <label><strong><?php esc_html_e('Contract document (PDF/Doc)', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="hidden" id="vgbm_contract_document_id" name="vgbm_contract_document_id" value="<?php echo esc_attr((string)$doc_id); ?>">
            <button type="button" class="button" id="vgbm_contract_document_pick">
                <?php esc_html_e('Select / Upload document', 'vgbm-property-manager'); ?>
            </button>
            <button type="button" class="button" id="vgbm_contract_document_clear" style="margin-left:6px;">
                <?php esc_html_e('Clear', 'vgbm-property-manager'); ?>
            </button>

            <span id="vgbm_contract_document_label" style="margin-left:10px;">
                <?php echo $doc_id ? esc_html($doc_name ?: ('#' . $doc_id)) : esc_html__('No document selected', 'vgbm-property-manager'); ?>
            </span>
        </p>

        <?php if ($doc_id): ?>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url($download_url); ?>" target="_blank" rel="noopener">
                    <?php esc_html_e('View / download document (secure)', 'vgbm-property-manager'); ?>
                </a>
            </p>
        <?php endif; ?>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_contract_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_contract_nonce'])), 'vgbm_pm_save_contract')) {
            return;
        }

        // Units (multi)
        $unit_ids = isset($_POST['vgbm_unit_ids']) ? (array) $_POST['vgbm_unit_ids'] : [];
        $unit_ids = array_values(array_filter(array_map('intval', $unit_ids), fn($v) => $v > 0));
        update_post_meta($post_id, '_vgbm_unit_ids', $unit_ids);

        // Primary unit for backward compatibility
        $primary_unit = !empty($unit_ids) ? (int)$unit_ids[0] : 0;
        update_post_meta($post_id, '_vgbm_unit_id', $primary_unit);

        $renter_ids = isset($_POST['vgbm_renter_ids']) ? (array) $_POST['vgbm_renter_ids'] : [];
        $renter_ids = array_values(array_filter(array_map('intval', $renter_ids), fn($v) => $v > 0));
        update_post_meta($post_id, '_vgbm_renter_ids', $renter_ids);

        $start = isset($_POST['vgbm_start_date']) ? sanitize_text_field(wp_unslash($_POST['vgbm_start_date'])) : '';
        $end   = isset($_POST['vgbm_end_date']) ? sanitize_text_field(wp_unslash($_POST['vgbm_end_date'])) : '';
        update_post_meta($post_id, '_vgbm_start_date', $start);
        update_post_meta($post_id, '_vgbm_end_date', $end);

        $status = isset($_POST['vgbm_contract_status']) ? sanitize_text_field(wp_unslash($_POST['vgbm_contract_status'])) : 'active';
        if (!in_array($status, ['active', 'ended', 'draft'], true)) { $status = 'active'; }
        update_post_meta($post_id, '_vgbm_contract_status', $status);

        // Billing rules
        $due_day = isset($_POST['vgbm_due_day']) ? (int) $_POST['vgbm_due_day'] : 0;
        if ($due_day < 1 || $due_day > 28) { $due_day = 0; }
        update_post_meta($post_id, '_vgbm_due_day', $due_day);

        $prorate_enabled = !empty($_POST['vgbm_bill_prorate']) ? 1 : 0;
        update_post_meta($post_id, '_vgbm_bill_prorate', $prorate_enabled);

        // Rent elements
        $raw = isset($_POST['vgbm_rent_elements']) ? (array) $_POST['vgbm_rent_elements'] : [];
        $normalized = [];
        foreach ($raw as $row) {
            if (!is_array($row)) { continue; }
            $label = isset($row['label']) ? sanitize_text_field(wp_unslash($row['label'])) : '';
            $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
            $indexable = !empty($row['indexable']);

            if ($label === '' && $amount == 0.0) { continue; }

            $normalized[] = [
                'label' => $label,
                'amount' => round($amount, 2),
                'indexable' => $indexable ? 1 : 0,
            ];
        }
        update_post_meta($post_id, '_vgbm_rent_elements', $normalized);

        $doc_id = isset($_POST['vgbm_contract_document_id']) ? (int) $_POST['vgbm_contract_document_id'] : 0;
        update_post_meta($post_id, '_vgbm_contract_document_id', $doc_id);
    }
}
