<?php
namespace VGBM\PM\Billing;

if (!defined('ABSPATH')) { exit; }

final class ReminderRepository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'vgbm_pm_reminders';
    }

    public function list_for_charge(int $charge_id): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE charge_id=%d ORDER BY sent_at DESC, id DESC", $charge_id),
            ARRAY_A
        );
        return $rows ?: [];
    }

    public function insert(array $data): int {
        global $wpdb;

        $defaults = [
            'charge_id' => 0,
            'sent_at' => current_time('mysql'),
            'channel' => 'email',
            'recipients' => '',
            'subject' => '',
            'status' => 'sent',
            'error' => null,
            'created_by' => get_current_user_id(),
        ];
        $data = array_merge($defaults, $data);

        $ok = $wpdb->insert($this->table, [
            'charge_id' => (int)$data['charge_id'],
            'sent_at' => (string)$data['sent_at'],
            'channel' => (string)$data['channel'],
            'recipients' => (string)$data['recipients'],
            'subject' => (string)$data['subject'],
            'status' => (string)$data['status'],
            'error' => $data['error'],
            'created_by' => (int)$data['created_by'],
        ], ['%d','%s','%s','%s','%s','%s','%s','%d']);

        return $ok ? (int)$wpdb->insert_id : 0;
    }
}
