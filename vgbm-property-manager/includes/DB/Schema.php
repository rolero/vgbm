<?php
namespace VGBM\PM\DB;

if (!defined('ABSPATH')) { exit; }

final class Schema {

    public static function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $charges = $wpdb->prefix . 'vgbm_pm_charges';
        $payments = $wpdb->prefix . 'vgbm_pm_payments';
        $reminders = $wpdb->prefix . 'vgbm_pm_reminders';

        $sql1 = "CREATE TABLE {$charges} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            contract_id BIGINT(20) UNSIGNED NOT NULL,

            period_date DATE NOT NULL,
            due_date DATE NOT NULL,

            type VARCHAR(20) NOT NULL DEFAULT 'rent',
            label VARCHAR(150) NULL,

            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency CHAR(3) NOT NULL DEFAULT 'EUR',
            paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(20) NOT NULL DEFAULT 'unpaid',

            elements LONGTEXT NULL,
            meta LONGTEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            last_reminder_at DATETIME NULL,
            reminder_count INT(11) NOT NULL DEFAULT 0,

            PRIMARY KEY (id),
            UNIQUE KEY contract_period_type (contract_id, period_date, type),
            KEY status (status),
            KEY due_date (due_date),
            KEY contract_id (contract_id),
            KEY type (type)
        ) {$charset};";

        $sql2 = "CREATE TABLE {$payments} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            charge_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            paid_at DATE NOT NULL,
            method VARCHAR(30) NOT NULL DEFAULT 'manual',
            reference VARCHAR(100) NULL,
            note TEXT NULL,
            created_by BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY charge_id (charge_id),
            KEY paid_at (paid_at)
        ) {$charset};";

        $sql3 = "CREATE TABLE {$reminders} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            charge_id BIGINT(20) UNSIGNED NOT NULL,
            sent_at DATETIME NOT NULL,
            channel VARCHAR(20) NOT NULL DEFAULT 'email',
            recipients TEXT NULL,
            subject VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            error TEXT NULL,
            created_by BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY charge_id (charge_id),
            KEY sent_at (sent_at)
        ) {$charset};";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);

        // Upgrade: remove old unique key if present.
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$charges}", ARRAY_A);
        $has_old = false;
        $has_new = false;
        foreach ($indexes as $ix) {
            if (($ix['Key_name'] ?? '') === 'contract_period') { $has_old = true; }
            if (($ix['Key_name'] ?? '') === 'contract_period_type') { $has_new = true; }
        }
        if ($has_old) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query("ALTER TABLE {$charges} DROP INDEX contract_period");
        }
        if (!$has_new) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query("ALTER TABLE {$charges} ADD UNIQUE KEY contract_period_type (contract_id, period_date, type)");
        }

        update_option('vgbm_pm_schema_version', '0.4.1');
    }
}
