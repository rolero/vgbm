<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\RentCalculator;

if (!defined('ABSPATH')) { exit; }

final class IndexationPage {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('admin_menu', [$this, 'add_submenu'], 30);
    }

    public function add_submenu(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'vgbm_pm',
            __('Indexation', 'vgbm-property-manager'),
            __('Indexation', 'vgbm-property-manager'),
            'read',
            'vgbm_pm_indexation',
            [$this, 'render']
        );
    }

    private function get_contracts(): array {
        return get_posts([
            'post_type' => PostTypes::CPT_CONTRACT,
            'numberposts' => 500,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    public function render(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            wp_die(esc_html__('No access.', 'vgbm-property-manager'));
        }

        $year = (int) (isset($_POST['vgbm_year']) ? $_POST['vgbm_year'] : (int) date_i18n('Y'));
        $rate = (float) (isset($_POST['vgbm_rate']) ? $_POST['vgbm_rate'] : 0.0);
        $mode = isset($_POST['vgbm_mode']) ? sanitize_text_field(wp_unslash($_POST['vgbm_mode'])) : 'preview';
        $selected = isset($_POST['vgbm_contract_ids']) ? array_map('intval', (array) $_POST['vgbm_contract_ids']) : [];

        $results = [];
        $message = '';

        if (!empty($_POST['vgbm_indexation_submit'])) {
            check_admin_referer('vgbm_pm_indexation', 'vgbm_pm_indexation_nonce');

            if ($year < 2000 || $year > 2100) {
                $message = '<div class="notice notice-error"><p>' . esc_html__('Invalid year.', 'vgbm-property-manager') . '</p></div>';
            } elseif ($rate < -50 || $rate > 50) {
                $message = '<div class="notice notice-error"><p>' . esc_html__('Invalid rate. Use a percentage between -50 and 50.', 'vgbm-property-manager') . '</p></div>';
            } elseif (empty($selected)) {
                $message = '<div class="notice notice-warning"><p>' . esc_html__('Select at least one contract.', 'vgbm-property-manager') . '</p></div>';
            } else {
                foreach ($selected as $cid) {
                    $initial = get_post_meta($cid, '_vgbm_rent_elements', true);
                    $history = get_post_meta($cid, '_vgbm_indexation_history', true);

                    $res = RentCalculator::compute_for_year(
                        is_array($initial) ? $initial : [],
                        $history,
                        $year,
                        $rate
                    );

                    $res['contract_id'] = $cid;
                    $res['contract_title'] = get_the_title($cid);

                    $results[] = $res;

                    if ($mode === 'apply') {
                        RentCalculator::save_history($cid, $res);
                    }
                }

                if ($mode === 'apply') {
                    $message = '<div class="notice notice-success"><p>' . esc_html__('Indexation applied.', 'vgbm-property-manager') . '</p></div>';
                } else {
                    $message = '<div class="notice notice-info"><p>' . esc_html__('Preview (test run) — nothing was saved.', 'vgbm-property-manager') . '</p></div>';
                }
            }
        }

        $contracts = $this->get_contracts();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Indexation (batch)', 'vgbm-property-manager'); ?></h1>

            <p class="description">
                <?php esc_html_e('Test-run or apply yearly indexation per contract. Later we can add per property and per portfolio batches.', 'vgbm-property-manager'); ?>
            </p>

            <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <form method="post">
                <?php wp_nonce_field('vgbm_pm_indexation', 'vgbm_pm_indexation_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="vgbm_year"><?php esc_html_e('Year', 'vgbm-property-manager'); ?></label></th>
                        <td><input type="number" id="vgbm_year" name="vgbm_year" value="<?php echo esc_attr((string)$year); ?>" min="2000" max="2100"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vgbm_rate"><?php esc_html_e('Indexation rate (%)', 'vgbm-property-manager'); ?></label></th>
                        <td><input type="number" step="0.01" id="vgbm_rate" name="vgbm_rate" value="<?php echo esc_attr((string)$rate); ?>" min="-50" max="50"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mode', 'vgbm-property-manager'); ?></th>
                        <td>
                            <label><input type="radio" name="vgbm_mode" value="preview" <?php checked($mode, 'preview'); ?>> <?php esc_html_e('Test run (preview)', 'vgbm-property-manager'); ?></label><br>
                            <label><input type="radio" name="vgbm_mode" value="apply" <?php checked($mode, 'apply'); ?>> <?php esc_html_e('Apply (save to contracts)', 'vgbm-property-manager'); ?></label>
                        </td>
                    </tr>
                </table>

                <h2 style="margin-top:20px;"><?php esc_html_e('Contracts', 'vgbm-property-manager'); ?></h2>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="vgbm_select_all"></th>
                            <th><?php esc_html_e('Contract', 'vgbm-property-manager'); ?></th>
                            <th><?php esc_html_e('Current total (monthly)', 'vgbm-property-manager'); ?></th>
                            <th><?php esc_html_e('Latest index year', 'vgbm-property-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $c): ?>
                            <?php
                                $cur = RentCalculator::current_total($c->ID);
                                $checked = in_array((int)$c->ID, $selected, true);
                            ?>
                            <tr>
                                <td><input type="checkbox" name="vgbm_contract_ids[]" value="<?php echo esc_attr((string)$c->ID); ?>" <?php checked($checked); ?>></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $c->ID . '&action=edit')); ?>">
                                        <?php echo esc_html($c->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(number_format_i18n((float)$cur['total'], 2)); ?></td>
                                <td><?php echo $cur['year'] ? esc_html((string)$cur['year']) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p style="margin-top:12px;">
                    <button type="submit" class="button button-primary" name="vgbm_indexation_submit" value="1">
                        <?php esc_html_e('Run', 'vgbm-property-manager'); ?>
                    </button>
                </p>
            </form>

            <?php if (!empty($results)): ?>
                <h2 style="margin-top:24px;"><?php esc_html_e('Results', 'vgbm-property-manager'); ?></h2>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Contract', 'vgbm-property-manager'); ?></th>
                            <th><?php esc_html_e('Base total', 'vgbm-property-manager'); ?></th>
                            <th><?php esc_html_e('New total', 'vgbm-property-manager'); ?></th>
                            <th><?php esc_html_e('Rate (%)', 'vgbm-property-manager'); ?></th>
                            <th><?php esc_html_e('Details', 'vgbm-property-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . (int)$r['contract_id'] . '&action=edit')); ?>">
                                        <?php echo esc_html((string)$r['contract_title']); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(number_format_i18n((float)$r['base_total'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float)$r['new_total'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float)$r['rate'], 2)); ?></td>
                                <td>
                                    <details>
                                        <summary><?php esc_html_e('Show elements', 'vgbm-property-manager'); ?></summary>
                                        <table class="widefat striped" style="margin-top:8px;">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Element', 'vgbm-property-manager'); ?></th>
                                                    <th><?php esc_html_e('Before', 'vgbm-property-manager'); ?></th>
                                                    <th><?php esc_html_e('After', 'vgbm-property-manager'); ?></th>
                                                    <th><?php esc_html_e('Indexed', 'vgbm-property-manager'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $before = (array) ($r['base_elements'] ?? []);
                                                    $after  = (array) ($r['new_elements'] ?? []);
                                                    foreach ($after as $i => $el):
                                                        $b = $before[$i] ?? ['amount' => 0, 'label' => $el['label'] ?? ''];
                                                ?>
                                                    <tr>
                                                        <td><?php echo esc_html((string)($el['label'] ?? '')); ?></td>
                                                        <td><?php echo esc_html(number_format_i18n((float)($b['amount'] ?? 0), 2)); ?></td>
                                                        <td><?php echo esc_html(number_format_i18n((float)($el['amount'] ?? 0), 2)); ?></td>
                                                        <td><?php echo !empty($el['indexable']) ? esc_html__('Yes', 'vgbm-property-manager') : esc_html__('No', 'vgbm-property-manager'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>

        <script>
            (function() {
                var selAll = document.getElementById('vgbm_select_all');
                if (!selAll) return;
                selAll.addEventListener('change', function() {
                    var boxes = document.querySelectorAll('input[name="vgbm_contract_ids[]"]');
                    boxes.forEach(function(b) { b.checked = selAll.checked; });
                });
            })();
        </script>
        <?php
    }
}
