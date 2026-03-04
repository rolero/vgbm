<?php
namespace VGBM\PM\Admin;

use VGBM\PM\Billing\BillingService;
use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class BillingPage {

    private BillingService $billing;

    public function __construct() {
        $this->billing = new BillingService();
    }

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('admin_menu', [$this, 'add_submenu'], 35);
    }

    public function add_submenu(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'vgbm_pm',
            __('Rent collection', 'vgbm-property-manager'),
            __('Rent collection', 'vgbm-property-manager'),
            'read',
            'vgbm_pm_billing',
            [$this, 'render']
        );
    }

    private function tab(): string {
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'generate';
        $allowed = ['generate', 'charges', 'settings'];
        return in_array($tab, $allowed, true) ? $tab : 'generate';
    }

    public function render(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            wp_die(esc_html__('No access.', 'vgbm-property-manager'));
        }

        $tab = $this->tab();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Rent collection', 'vgbm-property-manager') . '</h1>';

        $base_url = admin_url('admin.php?page=vgbm_pm_billing');

        echo '<nav class="nav-tab-wrapper" style="margin-top:12px;">';
        echo '<a class="nav-tab ' . ($tab==='generate' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg('tab','generate',$base_url)) . '">' . esc_html__('Generate', 'vgbm-property-manager') . '</a>';
        echo '<a class="nav-tab ' . ($tab==='charges' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg('tab','charges',$base_url)) . '">' . esc_html__('Charges', 'vgbm-property-manager') . '</a>';
        echo '<a class="nav-tab ' . ($tab==='settings' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg('tab','settings',$base_url)) . '">' . esc_html__('Settings', 'vgbm-property-manager') . '</a>';
        echo '</nav>';

        if ($tab === 'generate') {
            $this->render_generate();
        } elseif ($tab === 'charges') {
            $this->render_charges();
        } else {
            $this->render_settings();
        }

        echo '</div>';
    }

    private function render_generate(): void {
        $month = isset($_POST['vgbm_month']) ? sanitize_text_field(wp_unslash($_POST['vgbm_month'])) : date_i18n('Y-m');
        $due_day = (int) (isset($_POST['vgbm_due_day']) ? $_POST['vgbm_due_day'] : 1);

        $msg = '';

        if (!empty($_POST['vgbm_generate_submit'])) {
            check_admin_referer('vgbm_pm_generate_charges', 'vgbm_pm_generate_nonce');

            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                $msg = '<div class="notice notice-error"><p>' . esc_html__('Invalid month.', 'vgbm-property-manager') . '</p></div>';
            } else {
                $res = $this->billing->generate_for_month($month, $due_day);
                $msg = '<div class="notice notice-success"><p>' .
                    esc_html(sprintf(__('Generated: %d, already existed: %d, skipped (not active): %d', 'vgbm-property-manager'), (int)$res['created'], (int)$res['existing'], (int)$res['skipped'])) .
                    '</p></div>';
            }
        }

        if (!empty($_POST['vgbm_create_correction'])) {
            check_admin_referer('vgbm_pm_create_correction', 'vgbm_pm_create_correction_nonce');

            $contract_id = (int) ($_POST['vgbm_c_contract_id'] ?? 0);
            $amount = (float) ($_POST['vgbm_c_amount'] ?? 0);
            $period_date = sanitize_text_field(wp_unslash($_POST['vgbm_c_period_date'] ?? ''));
            $due_date = sanitize_text_field(wp_unslash($_POST['vgbm_c_due_date'] ?? ''));
            $label = sanitize_text_field(wp_unslash($_POST['vgbm_c_label'] ?? 'Correction'));
            $note = sanitize_text_field(wp_unslash($_POST['vgbm_c_note'] ?? ''));

            if ($contract_id <= 0 || !$period_date || !$due_date) {
                $msg .= '<div class="notice notice-error"><p>' . esc_html__('Fill contract, period date and due date.', 'vgbm-property-manager') . '</p></div>';
            } else {
                $id = $this->billing->create_correction_charge($contract_id, $period_date, $due_date, $amount, $label, $note ?: null);
                $msg .= $id
                    ? '<div class="notice notice-success"><p>' . esc_html__('Correction invoice created.', 'vgbm-property-manager') . '</p></div>'
                    : '<div class="notice notice-error"><p>' . esc_html__('Correction invoice not created (duplicate or invalid amount).', 'vgbm-property-manager') . '</p></div>';
            }
        }

        echo $msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '<h2 style="margin-top:16px;">' . esc_html__('Generate monthly rent charges', 'vgbm-property-manager') . '</h2>';
        echo '<p class="description">' . esc_html__('Creates one rent charge per active contract for the selected month. Each contract can define its own due day and whether partial months should be prorated.', 'vgbm-property-manager') . '</p>';

        echo '<form method="post" style="max-width:750px;">';
        wp_nonce_field('vgbm_pm_generate_charges', 'vgbm_pm_generate_nonce');

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="vgbm_month">' . esc_html__('Month', 'vgbm-property-manager') . '</label></th>';
        echo '<td><input type="month" id="vgbm_month" name="vgbm_month" value="' . esc_attr($month) . '"></td></tr>';

        echo '<tr><th scope="row"><label for="vgbm_due_day">' . esc_html__('Default due day', 'vgbm-property-manager') . '</label></th>';
        echo '<td><input type="number" min="1" max="28" id="vgbm_due_day" name="vgbm_due_day" value="' . esc_attr((string)$due_day) . '"> ';
        echo '<span class="description">' . esc_html__('Used only when a contract has no specific due day.', 'vgbm-property-manager') . '</span></td></tr>';

        echo '</table>';

        echo '<p><button type="submit" class="button button-primary" name="vgbm_generate_submit" value="1">' . esc_html__('Generate', 'vgbm-property-manager') . '</button></p>';
        echo '</form>';

        echo '<hr style="margin:24px 0;">';
        echo '<h2>' . esc_html__('Create correction invoice (yearly settlement)', 'vgbm-property-manager') . '</h2>';
        echo '<p class="description">' . esc_html__('Use this for yearly corrections/settlements (e.g. service costs). This creates a separate charge that can be paid and reminded like normal rent.', 'vgbm-property-manager') . '</p>';

        $contracts = get_posts([
            'post_type' => PostTypes::CPT_CONTRACT,
            'numberposts' => 2000,
            'post_status' => ['publish', 'private', 'draft'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $today = current_time('Y-m-d');

        echo '<form method="post" style="max-width:900px;">';
        wp_nonce_field('vgbm_pm_create_correction', 'vgbm_pm_create_correction_nonce');

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row">' . esc_html__('Contract', 'vgbm-property-manager') . '</th><td>';
        echo '<select name="vgbm_c_contract_id" class="regular-text">';
        echo '<option value="0">' . esc_html__('— Select —', 'vgbm-property-manager') . '</option>';
        foreach ($contracts as $c) {
            echo '<option value="' . esc_attr((string)$c->ID) . '">' . esc_html($c->post_title) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Label', 'vgbm-property-manager') . '</th><td><input class="regular-text" type="text" name="vgbm_c_label" value="' . esc_attr__('Yearly settlement', 'vgbm-property-manager') . '"></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Amount (EUR)', 'vgbm-property-manager') . '</th><td><input type="number" step="0.01" name="vgbm_c_amount" value="0.00"></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Period date', 'vgbm-property-manager') . '</th><td><input type="date" name="vgbm_c_period_date" value="' . esc_attr($today) . '"> ';
        echo '<span class="description">' . esc_html__('Use a date inside the period you are settling (e.g. 2026-12-31).', 'vgbm-property-manager') . '</span></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Due date', 'vgbm-property-manager') . '</th><td><input type="date" name="vgbm_c_due_date" value="' . esc_attr($today) . '"></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Note', 'vgbm-property-manager') . '</th><td><input class="regular-text" type="text" name="vgbm_c_note" value=""></td></tr>';

        echo '</table>';

        echo '<p><button type="submit" class="button button-primary" name="vgbm_create_correction" value="1">' . esc_html__('Create correction charge', 'vgbm-property-manager') . '</button></p>';
        echo '</form>';
    }

    private function render_settings(): void {
        $settings = get_option('vgbm_pm_billing_settings', []);
        if (!is_array($settings)) { $settings = []; }

        $msg = '';
        if (!empty($_POST['vgbm_save_settings'])) {
            check_admin_referer('vgbm_pm_billing_settings', 'vgbm_pm_billing_settings_nonce');

            $settings['iban'] = sanitize_text_field(wp_unslash($_POST['vgbm_iban'] ?? ''));
            $settings['account_name'] = sanitize_text_field(wp_unslash($_POST['vgbm_account_name'] ?? ''));
            $settings['reference_template'] = sanitize_text_field(wp_unslash($_POST['vgbm_reference_template'] ?? 'Huur {contract_id} {period}'));

            update_option('vgbm_pm_billing_settings', $settings);
            $msg = '<div class="notice notice-success"><p>' . esc_html__('Saved.', 'vgbm-property-manager') . '</p></div>';
        }

        echo $msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '<h2 style="margin-top:16px;">' . esc_html__('Payment details (for reminders)', 'vgbm-property-manager') . '</h2>';

        echo '<form method="post" style="max-width:650px;">';
        wp_nonce_field('vgbm_pm_billing_settings', 'vgbm_pm_billing_settings_nonce');

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="vgbm_account_name">' . esc_html__('Account name', 'vgbm-property-manager') . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="vgbm_account_name" name="vgbm_account_name" value="' . esc_attr((string)($settings['account_name'] ?? '')) . '"></td></tr>';

        echo '<tr><th scope="row"><label for="vgbm_iban">' . esc_html__('IBAN', 'vgbm-property-manager') . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="vgbm_iban" name="vgbm_iban" value="' . esc_attr((string)($settings['iban'] ?? '')) . '"></td></tr>';

        echo '<tr><th scope="row"><label for="vgbm_reference_template">' . esc_html__('Reference template', 'vgbm-property-manager') . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="vgbm_reference_template" name="vgbm_reference_template" value="' . esc_attr((string)($settings['reference_template'] ?? 'Huur {contract_id} {period}')) . '">';
        echo '<p class="description">' . esc_html__('Use {contract_id} and {period} (YYYY-MM).', 'vgbm-property-manager') . '</p></td></tr>';

        echo '</table>';

        echo '<p><button type="submit" class="button button-primary" name="vgbm_save_settings" value="1">' . esc_html__('Save settings', 'vgbm-property-manager') . '</button></p>';
        echo '</form>';
    }

    private function render_charges(): void {
        $repo = $this->billing->charges();

        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $month = isset($_GET['month']) ? sanitize_text_field(wp_unslash($_GET['month'])) : '';
        $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';

        $filters = [];
        if ($status) { $filters['status'] = $status; }
        if ($month) { $filters['month'] = $month; }
        if ($type) { $filters['type'] = $type; }

        $page = max(1, (int)($_GET['paged'] ?? 1));
        $per_page = 25;
        $total = $repo->count($filters);
        $offset = ($page - 1) * $per_page;

        $msg = '';
        if (!empty($_POST['vgbm_action'])) {
            check_admin_referer('vgbm_pm_charges_action', 'vgbm_pm_charges_nonce');
            $action = sanitize_text_field(wp_unslash($_POST['vgbm_action']));
            $ids = isset($_POST['charge_ids']) ? array_values(array_filter(array_map('intval', (array)$_POST['charge_ids']))) : [];

            if (empty($ids)) {
                $msg = '<div class="notice notice-warning"><p>' . esc_html__('Select at least one charge.', 'vgbm-property-manager') . '</p></div>';
            } else {
                if ($action === 'send_reminder') {
                    $ok = 0; $fail = 0;
                    foreach ($ids as $id) {
                        $res = $this->billing->send_reminder_email($id);
                        if (!empty($res['ok'])) { $ok++; } else { $fail++; }
                    }
                    $msg = '<div class="notice notice-info"><p>' .
                        esc_html(sprintf(__('Reminders sent: %d, failed: %d', 'vgbm-property-manager'), $ok, $fail)) .
                        '</p></div>';
                }
                if ($action === 'add_payment') {
                    $amount = (float)($_POST['payment_amount'] ?? 0);
                    $paid_at = sanitize_text_field(wp_unslash($_POST['payment_date'] ?? current_time('Y-m-d')));
                    $method = sanitize_text_field(wp_unslash($_POST['payment_method'] ?? 'manual'));
                    $reference = sanitize_text_field(wp_unslash($_POST['payment_reference'] ?? ''));
                    $note = sanitize_text_field(wp_unslash($_POST['payment_note'] ?? ''));

                    $first = (int)$ids[0];
                    $pid = $this->billing->add_payment($first, $amount, $paid_at, $method, $reference ?: null, $note ?: null);
                    $msg = $pid
                        ? '<div class="notice notice-success"><p>' . esc_html__('Payment recorded.', 'vgbm-property-manager') . '</p></div>'
                        : '<div class="notice notice-error"><p>' . esc_html__('Payment not recorded (invalid amount).', 'vgbm-property-manager') . '</p></div>';
                }
            }
        }

        echo $msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $base_url = admin_url('admin.php?page=vgbm_pm_billing&tab=charges');

        echo '<h2 style="margin-top:16px;">' . esc_html__('Charges', 'vgbm-property-manager') . '</h2>';

        echo '<form method="get" style="margin:10px 0;">';
        echo '<input type="hidden" name="page" value="vgbm_pm_billing">';
        echo '<input type="hidden" name="tab" value="charges">';

        echo '<label style="margin-right:8px;">' . esc_html__('Month', 'vgbm-property-manager') . ' ';
        echo '<input type="month" name="month" value="' . esc_attr($month) . '"></label>';

        echo '<label style="margin-right:8px;">' . esc_html__('Type', 'vgbm-property-manager') . ' ';
        echo '<select name="type">';
        echo '<option value="">' . esc_html__('All', 'vgbm-property-manager') . '</option>';
        foreach (['rent' => __('Rent', 'vgbm-property-manager'), 'correction' => __('Correction', 'vgbm-property-manager')] as $k => $lbl) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($type, $k, false) . '>' . esc_html($lbl) . '</option>';
        }
        echo '</select></label>';

        echo '<label style="margin-right:8px;">' . esc_html__('Status', 'vgbm-property-manager') . ' ';
        echo '<select name="status">';
        echo '<option value="">' . esc_html__('All', 'vgbm-property-manager') . '</option>';
        foreach (['unpaid','partial','overdue','paid'] as $s) {
            echo '<option value="' . esc_attr($s) . '" ' . selected($status, $s, false) . '>' . esc_html(ucfirst($s)) . '</option>';
        }
        echo '</select></label>';

        echo '<button class="button">' . esc_html__('Filter', 'vgbm-property-manager') . '</button>';
        echo '</form>';

        $rows = $repo->list($filters, $per_page, $offset);

        echo '<form method="post">';
        wp_nonce_field('vgbm_pm_charges_action', 'vgbm_pm_charges_nonce');

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th style="width:40px;"><input type="checkbox" id="vgbm_charge_select_all"></th>';
        echo '<th>' . esc_html__('Period', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Type', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Label', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Contract', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Due', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Amount', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Paid', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Outstanding', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Status', 'vgbm-property-manager') . '</th>';
        echo '<th>' . esc_html__('Reminders', 'vgbm-property-manager') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="11">' . esc_html__('No charges found.', 'vgbm-property-manager') . '</td></tr>';
        } else {
            foreach ($rows as $r) {
                $id = (int)$r['id'];
                $contract_id = (int)$r['contract_id'];
                $amount = (float)$r['amount'];
                $paid = (float)$r['paid_amount'];
                $out = max(0.0, round($amount - $paid, 2));
                $status_label = ucfirst((string)$r['status']);

                $contract_link = admin_url('post.php?post=' . $contract_id . '&action=edit');

                echo '<tr>';
                echo '<td><input type="checkbox" name="charge_ids[]" value="' . esc_attr((string)$id) . '"></td>';
                echo '<td>' . esc_html(substr((string)$r['period_date'], 0, 7)) . '</td>';
                echo '<td>' . esc_html((string)($r['type'] ?? 'rent')) . '</td>';
                echo '<td>' . esc_html((string)($r['label'] ?? '')) . '</td>';
                echo '<td><a href="' . esc_url($contract_link) . '">' . esc_html(get_the_title($contract_id)) . '</a></td>';
                echo '<td>' . esc_html((string)$r['due_date']) . '</td>';
                echo '<td>' . esc_html(number_format_i18n($amount, 2)) . '</td>';
                echo '<td>' . esc_html(number_format_i18n($paid, 2)) . '</td>';
                echo '<td><strong>' . esc_html(number_format_i18n($out, 2)) . '</strong></td>';
                echo '<td>' . esc_html($status_label) . '</td>';
                echo '<td>' . esc_html((string)($r['reminder_count'] ?? 0)) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        echo '<div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-top:12px;">';
        echo '<div>';
        echo '<label><strong>' . esc_html__('Action', 'vgbm-property-manager') . '</strong><br>';
        echo '<select name="vgbm_action">';
        echo '<option value="send_reminder">' . esc_html__('Send reminder email', 'vgbm-property-manager') . '</option>';
        echo '<option value="add_payment">' . esc_html__('Record payment (first selected)', 'vgbm-property-manager') . '</option>';
        echo '</select></label>';
        echo '</div>';

        echo '<div>';
        echo '<label>' . esc_html__('Payment amount', 'vgbm-property-manager') . '<br>';
        echo '<input type="number" step="0.01" name="payment_amount" value="0.00"></label>';
        echo '</div>';

        echo '<div>';
        echo '<label>' . esc_html__('Payment date', 'vgbm-property-manager') . '<br>';
        echo '<input type="date" name="payment_date" value="' . esc_attr(current_time('Y-m-d')) . '"></label>';
        echo '</div>';

        echo '<div>';
        echo '<label>' . esc_html__('Method', 'vgbm-property-manager') . '<br>';
        echo '<select name="payment_method">';
        foreach (['manual','bank_transfer','cash','card'] as $m) {
            echo '<option value="' . esc_attr($m) . '">' . esc_html($m) . '</option>';
        }
        echo '</select></label>';
        echo '</div>';

        echo '<div>';
        echo '<label>' . esc_html__('Reference', 'vgbm-property-manager') . '<br>';
        echo '<input type="text" name="payment_reference" value=""></label>';
        echo '</div>';

        echo '<div style="flex:1; min-width:220px;">';
        echo '<label>' . esc_html__('Note', 'vgbm-property-manager') . '<br>';
        echo '<input type="text" class="widefat" name="payment_note" value=""></label>';
        echo '</div>';

        echo '<div>';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Run', 'vgbm-property-manager') . '</button>';
        echo '</div>';
        echo '</div>';

        echo '</form>';

        $pages = (int) ceil(max(1, $total) / $per_page);
        if ($pages > 1) {
            echo '<div class="tablenav" style="margin-top:12px;">';
            echo '<div class="tablenav-pages">';
            for ($p=1; $p <= $pages; $p++) {
                $url = add_query_arg(['paged' => $p], $base_url);
                if ($status) { $url = add_query_arg(['status' => $status], $url); }
                if ($month) { $url = add_query_arg(['month' => $month], $url); }
                if ($type) { $url = add_query_arg(['type' => $type], $url); }
                $class = $p === $page ? 'button button-primary' : 'button';
                echo '<a class="' . esc_attr($class) . '" style="margin-right:6px;" href="' . esc_url($url) . '">' . esc_html((string)$p) . '</a>';
            }
            echo '</div></div>';
        }

        echo '<script>
            (function(){
                var all = document.getElementById("vgbm_charge_select_all");
                if(!all) return;
                all.addEventListener("change", function(){
                    document.querySelectorAll("input[name=\"charge_ids[]\"]").forEach(function(cb){ cb.checked = all.checked; });
                });
            })();
        </script>';
    }
}
