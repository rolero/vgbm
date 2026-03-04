<?php
namespace VGBM\PM\Billing;

if (!defined('ABSPATH')) { exit; }

final class ChargeRepository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'vgbm_pm_charges';
    }

    public function get_table(): string { return $this->table; }

    public function find_by_id(int $id): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id=%d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function find_by_contract_period(int $contract_id, string $period_date, string $type = 'rent'): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE contract_id=%d AND period_date=%s AND type=%s", $contract_id, $period_date, $type),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array {
        global $wpdb;

        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND status=%s";
            $params[] = $filters['status'];
        }
        if (!empty($filters['month'])) {
            $where .= " AND period_date LIKE %s";
            $params[] = $filters['month'] . '%';
        }
        if (!empty($filters['contract_id'])) {
            $where .= " AND contract_id=%d";
            $params[] = (int)$filters['contract_id'];
        }
        if (!empty($filters['type'])) {
            $where .= " AND type=%s";
            $params[] = $filters['type'];
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY period_date DESC, id DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $prepared = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        foreach ($rows as &$r) {
            $r = $this->compute_status($r);
        }

        return $rows ?: [];
    }

    public function count(array $filters = []): int {
        global $wpdb;

        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND status=%s";
            $params[] = $filters['status'];
        }
        if (!empty($filters['month'])) {
            $where .= " AND period_date LIKE %s";
            $params[] = $filters['month'] . '%';
        }
        if (!empty($filters['contract_id'])) {
            $where .= " AND contract_id=%d";
            $params[] = (int)$filters['contract_id'];
        }
        if (!empty($filters['type'])) {
            $where .= " AND type=%s";
            $params[] = $filters['type'];
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";
        $prepared = !empty($params) ? $wpdb->prepare($sql, $params) : $sql;
        return (int) $wpdb->get_var($prepared);
    }

    public function insert(array $data): int {
        global $wpdb;

        $defaults = [
            'contract_id' => 0,
            'period_date' => '',
            'due_date' => '',
            'type' => 'rent',
            'label' => null,
            'amount' => 0.00,
            'currency' => 'EUR',
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'elements' => null,
            'meta' => null,
            'updated_at' => current_time('mysql'),
        ];
        $data = array_merge($defaults, $data);

        $ok = $wpdb->insert($this->table, [
            'contract_id' => (int)$data['contract_id'],
            'period_date' => (string)$data['period_date'],
            'due_date' => (string)$data['due_date'],
            'type' => (string)$data['type'],
            'label' => $data['label'],
            'amount' => (float)$data['amount'],
            'currency' => (string)$data['currency'],
            'paid_amount' => (float)$data['paid_amount'],
            'status' => (string)$data['status'],
            'elements' => $data['elements'],
            'meta' => $data['meta'],
            'updated_at' => (string)$data['updated_at'],
        ], ['%d','%s','%s','%s','%s','%f','%s','%f','%s','%s','%s','%s']);

        return $ok ? (int)$wpdb->insert_id : 0;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        $ok = $wpdb->update($this->table, $data, ['id' => $id]);
        return $ok !== false;
    }

    public function recompute_and_persist_status(int $id): void {
        $row = $this->find_by_id($id);
        if (!$row) { return; }
        $computed = $this->compute_status($row);
        if ($computed['status'] !== $row['status']) {
            $this->update($id, ['status' => $computed['status']]);
        }
    }

    public function compute_status(array $row): array {
        $amount = (float)($row['amount'] ?? 0.0);
        $paid = (float)($row['paid_amount'] ?? 0.0);

        $due = isset($row['due_date']) ? (string)$row['due_date'] : '';
        $today = current_time('Y-m-d');

        $epsilon = 0.01;

        if ($paid + $epsilon >= $amount && $amount > 0) {
            $row['status'] = 'paid';
            return $row;
        }

        if ($paid > 0 && $paid + $epsilon < $amount) {
            $row['status'] = ($due && $today > $due) ? 'overdue' : 'partial';
            return $row;
        }

        if ($due && $today > $due) {
            $row['status'] = 'overdue';
        } else {
            $row['status'] = 'unpaid';
        }

        return $row;
    }
}
