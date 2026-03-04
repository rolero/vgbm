<?php
namespace VGBM\PM\Billing;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\RentCalculator;

if (!defined('ABSPATH')) { exit; }

final class BillingService {

    private ChargeRepository $charges;
    private PaymentRepository $payments;
    private ReminderRepository $reminders;

    public function __construct() {
        $this->charges = new ChargeRepository();
        $this->payments = new PaymentRepository();
        $this->reminders = new ReminderRepository();
    }

    public function charges(): ChargeRepository { return $this->charges; }
    public function payments(): PaymentRepository { return $this->payments; }
    public function reminders(): ReminderRepository { return $this->reminders; }

    public function proration_for_month(int $contract_id, string $month_ym): array {
        $period_start = $month_ym . '-01';
        $dt_start = new \DateTimeImmutable($period_start);

        $dt_end = $dt_start->modify('last day of this month');
        $days_in_month = (int) $dt_end->format('j');

        $contract_start = (string) get_post_meta($contract_id, '_vgbm_start_date', true);
        $contract_end   = (string) get_post_meta($contract_id, '_vgbm_end_date', true);

        $active_start = $dt_start;
        $active_end = $dt_end;

        if ($contract_start) {
            try {
                $cs = new \DateTimeImmutable($contract_start);
                if ($cs > $active_start) { $active_start = $cs; }
            } catch (\Exception $e) {}
        }

        if ($contract_end) {
            try {
                $ce = new \DateTimeImmutable($contract_end);
                if ($ce < $active_end) { $active_end = $ce; }
            } catch (\Exception $e) {}
        }

        if ($active_end < $active_start) {
            return ['factor' => 0.0, 'active_days' => 0, 'days_in_month' => $days_in_month, 'start' => $active_start->format('Y-m-d'), 'end' => $active_end->format('Y-m-d')];
        }

        $active_days = (int) $active_end->diff($active_start)->days + 1;
        $factor = $active_days / max(1, $days_in_month);

        return [
            'factor' => round($factor, 6),
            'active_days' => $active_days,
            'days_in_month' => $days_in_month,
            'start' => $active_start->format('Y-m-d'),
            'end' => $active_end->format('Y-m-d'),
        ];
    }

    public function rent_snapshot_for_month(int $contract_id, string $month_ym): array {
        $year = (int) substr($month_ym, 0, 4);

        $initial = get_post_meta($contract_id, '_vgbm_rent_elements', true);
        $history = get_post_meta($contract_id, '_vgbm_indexation_history', true);

        $base_elements = RentCalculator::base_elements_for_year(
            RentCalculator::normalize_elements(is_array($initial) ? $initial : []),
            $history,
            $year + 1
        );

        $total = RentCalculator::total($base_elements);

        // Billing rules
        $prorate_meta = get_post_meta($contract_id, '_vgbm_bill_prorate', true);
        $prorate_enabled = ($prorate_meta === '' || (int)$prorate_meta === 1);

        $proration = $this->proration_for_month($contract_id, $month_ym);

        if ($prorate_enabled && $proration['factor'] > 0 && $proration['factor'] < 0.999999) {
            $factor = (float) $proration['factor'];
            $new_elements = [];
            foreach ($base_elements as $el) {
                $amt = round(((float)$el['amount']) * $factor, 2);
                $new_elements[] = [
                    'label' => (string)$el['label'],
                    'amount' => $amt,
                    'indexable' => (bool)($el['indexable'] ?? false),
                ];
            }
            $base_elements = $new_elements;
            $total = RentCalculator::total($base_elements);
        }

        $meta = [
            'year' => $year,
            'month' => $month_ym,
            'proration' => $proration,
            'prorate_enabled' => $prorate_enabled,
        ];

        return ['total' => $total, 'elements' => $base_elements, 'meta' => $meta];
    }

    public function generate_rent_charge(int $contract_id, string $month_ym, string $due_date): int {
        $period_date = $month_ym . '-01';
        $existing = $this->charges->find_by_contract_period($contract_id, $period_date, 'rent');
        if ($existing) {
            return (int)$existing['id'];
        }

        $snap = $this->rent_snapshot_for_month($contract_id, $month_ym);
        if (($snap['meta']['proration']['factor'] ?? 0) <= 0) {
            return 0;
        }

        $payload = [
            'elements' => $snap['elements'],
            'meta' => $snap['meta'],
        ];

        return $this->charges->insert([
            'contract_id' => $contract_id,
            'period_date' => $period_date,
            'due_date' => $due_date,
            'type' => 'rent',
            'label' => sprintf(__('Rent %s', 'vgbm-property-manager'), $month_ym),
            'amount' => (float)$snap['total'],
            'currency' => 'EUR',
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'elements' => wp_json_encode($payload),
            'meta' => null,
        ]);
    }

    public function create_correction_charge(int $contract_id, string $period_date, string $due_date, float $amount, string $label, ?string $note = null): int {
        $amount = round($amount, 2);
        if ($amount == 0.0) { return 0; }

        $existing = $this->charges->find_by_contract_period($contract_id, $period_date, 'correction');
        if ($existing) {
            return (int)$existing['id'];
        }

        $meta = [
            'note' => $note,
            'created_via' => 'manual',
        ];

        return $this->charges->insert([
            'contract_id' => $contract_id,
            'period_date' => $period_date,
            'due_date' => $due_date,
            'type' => 'correction',
            'label' => $label,
            'amount' => $amount,
            'currency' => 'EUR',
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'elements' => null,
            'meta' => wp_json_encode($meta),
        ]);
    }

    public function generate_for_month(string $month_ym, int $default_due_day = 1): array {
        $default_due_day = max(1, min(28, $default_due_day));

        $contracts = get_posts([
            'post_type' => PostTypes::CPT_CONTRACT,
            'numberposts' => 4000,
            'post_status' => ['publish', 'private', 'draft'],
            'meta_query' => [
                [
                    'key' => '_vgbm_contract_status',
                    'value' => 'active',
                    'compare' => '=',
                ],
            ],
        ]);

        $created = 0;
        $existing = 0;
        $skipped = 0;
        $ids = [];

        foreach ($contracts as $c) {
            $cid = (int)$c->ID;

            $due_day = (int) get_post_meta($cid, '_vgbm_due_day', true);
            if ($due_day < 1 || $due_day > 28) { $due_day = $default_due_day; }
            $due_date = sprintf('%s-%02d', $month_ym, $due_day);

            $period_date = $month_ym . '-01';
            $found = $this->charges->find_by_contract_period($cid, $period_date, 'rent');
            if ($found) {
                $existing++;
                $ids[] = (int)$found['id'];
                continue;
            }

            $id = $this->generate_rent_charge($cid, $month_ym, $due_date);
            if ($id) {
                $created++;
                $ids[] = $id;
            } else {
                $skipped++;
            }
        }

        return ['created' => $created, 'existing' => $existing, 'skipped' => $skipped, 'charge_ids' => $ids];
    }

    public function add_payment(int $charge_id, float $amount, string $paid_at, string $method = 'manual', ?string $reference = null, ?string $note = null): int {
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) { return 0; }

        $pid = $this->payments->insert([
            'charge_id' => $charge_id,
            'amount' => $amount,
            'paid_at' => $paid_at,
            'method' => $method,
            'reference' => $reference,
            'note' => $note,
        ]);

        if (!$pid) { return 0; }

        $sum = $this->payments->sum_for_charge($charge_id);
        $this->charges->update($charge_id, [
            'paid_amount' => $sum,
        ]);
        $this->charges->recompute_and_persist_status($charge_id);

        return $pid;
    }

    public function send_reminder_email(int $charge_id): array {
        $charge = $this->charges->find_by_id($charge_id);
        if (!$charge) { return ['ok' => false, 'error' => 'not_found']; }

        $contract_id = (int)$charge['contract_id'];
        $period_date = (string)$charge['period_date'];
        $due_date = (string)$charge['due_date'];
        $amount = (float)$charge['amount'];
        $paid = (float)$charge['paid_amount'];
        $outstanding = max(0.0, round($amount - $paid, 2));

        $type = (string)($charge['type'] ?? 'rent');
        $label = (string)($charge['label'] ?? '');

        $renter_ids = get_post_meta($contract_id, '_vgbm_renter_ids', true);
        if (!is_array($renter_ids)) { $renter_ids = []; }

        $emails = [];
        foreach ($renter_ids as $rid) {
            $email = (string) get_post_meta((int)$rid, '_vgbm_email', true);
            $email = sanitize_email($email);
            if ($email) { $emails[] = $email; }
        }
        $emails = array_values(array_unique(array_filter($emails)));

        if (empty($emails)) {
            return ['ok' => false, 'error' => 'no_recipient'];
        }

        $settings = get_option('vgbm_pm_billing_settings', []);
        if (!is_array($settings)) { $settings = []; }

        $iban = sanitize_text_field((string)($settings['iban'] ?? ''));
        $account_name = sanitize_text_field((string)($settings['account_name'] ?? ''));
        $ref_tpl = (string)($settings['reference_template'] ?? 'Huur {contract_id} {period}');
        $period = substr($period_date, 0, 7);
        $reference = str_replace(['{contract_id}', '{period}'], [(string)$contract_id, $period], $ref_tpl);

        $subject = $label ? sprintf(__('Payment reminder: %s', 'vgbm-property-manager'), $label) : sprintf(__('Payment reminder: %s', 'vgbm-property-manager'), $period);

        $body = sprintf(
            __("This is a friendly reminder that your payment is due.

Item: %s
Period: %s
Due date: %s
Outstanding: EUR %s

Payment details:
Account: %s
IBAN: %s
Reference: %s

If you have already paid, please ignore this message.
", 'vgbm-property-manager'),
            $label ?: ucfirst($type),
            $period,
            $due_date,
            number_format((float)$outstanding, 2, '.', ''),
            $account_name ?: '-',
            $iban ?: '-',
            $reference
        );

        $ok = true;
        $errors = [];

        foreach ($emails as $to) {
            $sent = wp_mail($to, $subject, $body);
            if (!$sent) {
                $ok = false;
                $errors[] = "failed:" . $to;
            }
        }

        $this->reminders->insert([
            'charge_id' => $charge_id,
            'sent_at' => current_time('mysql'),
            'channel' => 'email',
            'recipients' => implode(', ', $emails),
            'subject' => $subject,
            'status' => $ok ? 'sent' : 'failed',
            'error' => $ok ? null : implode('; ', $errors),
        ]);

        $this->charges->update($charge_id, [
            'last_reminder_at' => current_time('mysql'),
            'reminder_count' => (int)($charge['reminder_count'] ?? 0) + 1,
        ]);

        return ['ok' => $ok, 'recipients' => $emails, 'error' => $ok ? null : implode('; ', $errors)];
    }
}
