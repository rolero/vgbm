<?php
namespace VGBM\PM\Billing;

if (!defined('ABSPATH')) { exit; }

final class PaymentRepository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'vgbm_pm_payments';
    }

    public function list_for_charge(int $charge_id): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE charge_id=%d ORDER BY paid_at DESC, id DESC", $charge_id),
            ARRAY_A
        );
        return $rows ?: [];
    }

    public function insert(array $data): int {
        global $wpdb;

        $defaults = [
            'charge_id' => 0,
            'amount' => 0.00,
            'paid_at' => current_time('Y-m-d'),
            'method' => 'manual',
            'reference' => null,
            'note' => null,
            'created_by' => get_current_user_id(),
        ];
        $data = array_merge($defaults, $data);

        $ok = $wpdb->insert($this->table, [
            'charge_id' => (int)$data['charge_id'],
            'amount' => (float)$data['amount'],
            'paid_at' => (string)$data['paid_at'],
            'method' => (string)$data['method'],
            'reference' => $data['reference'],
            'note' => $data['note'],
            'created_by' => (int)$data['created_by'],
        ], ['%d','%f','%s','%s','%s','%s','%d']);

        return $ok ? (int)$wpdb->insert_id : 0;
    }

    public function sum_for_charge(int $charge_id): float {
        global $wpdb;
        $sum = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$this->table} WHERE charge_id=%d", $charge_id));
        return round((float)$sum, 2);
    }
}
