<?php
namespace VGBM\PM\Documents;

if (!defined('ABSPATH')) { exit; }

final class Config {

    public const OPT_TYPES  = 'vgbm_pm_doc_types';
    public const OPT_LABELS = 'vgbm_pm_doc_labels';

    /**
     * Ensure default document types and labels exist.
     * Safe to call on every request (idempotent).
     */
    public static function ensure_defaults(): void {
        $types = get_option(self::OPT_TYPES, null);
        $labels = get_option(self::OPT_LABELS, null);

        if (!is_array($labels) || empty($labels)) {
            update_option(self::OPT_LABELS, self::default_labels());
        }

        if (!is_array($types) || empty($types)) {
            update_option(self::OPT_TYPES, self::default_types());
        }
    }

    public static function default_labels(): array {
        // key => [name, sensitivity]
        // sensitivity: public|tenant|internal|confidential|sensitive
        return [
            'public' => [
                'name' => __('Public', 'vgbm-property-manager'),
                'sensitivity' => 'public',
            ],
            'tenant' => [
                'name' => __('Tenant-visible', 'vgbm-property-manager'),
                'sensitivity' => 'tenant',
            ],
            'internal' => [
                'name' => __('Internal', 'vgbm-property-manager'),
                'sensitivity' => 'internal',
            ],
            'confidential' => [
                'name' => __('Confidential', 'vgbm-property-manager'),
                'sensitivity' => 'confidential',
            ],
            'sensitive' => [
                'name' => __('Sensitive (GDPR/PII)', 'vgbm-property-manager'),
                'sensitivity' => 'sensitive',
            ],
        ];
    }

    public static function default_types(): array {
        // key => [name, allowed_extensions, default_label, meta]
        // allowed_extensions: array of file extensions (lowercase, without dot)
        // meta: used later for UI hints (optional)
        return [
            'rental_contract' => [
                'name' => __('Rental contract', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','doc','docx'],
                'default_label' => 'tenant',
            ],
            'id_document' => [
                'name' => __('Renter ID (passport/ID card)', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'sensitive',
            ],
            'kvk_extract' => [
                'name' => __('Company KVK extract', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf'],
                'default_label' => 'confidential',
            ],
            'sepa_mandate' => [
                'name' => __('SEPA mandate', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'confidential',
            ],
            'architect_drawing' => [
                'name' => __('Architect drawings', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png','dwg','dxf'],
                'default_label' => 'internal',
            ],
            'floor_plan' => [
                'name' => __('Floor plan', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'tenant',
            ],
            'energy_label' => [
                'name' => __('Energy label (Energielabel)', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'tenant',
            ],
            'inspection_in' => [
                'name' => __('Inspection report (check-in)', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'internal',
            ],
            'inspection_out' => [
                'name' => __('Inspection report (check-out)', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'internal',
            ],
            'maintenance' => [
                'name' => __('Maintenance certificate / report', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'internal',
            ],
            'insurance' => [
                'name' => __('Insurance policy', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf'],
                'default_label' => 'confidential',
            ],
            'invoice' => [
                'name' => __('Invoice', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'internal',
            ],
            'permit' => [
                'name' => __('Permit / Omgevingsvergunning', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png'],
                'default_label' => 'internal',
            ],
            'supplier_contract' => [
                'name' => __('Supplier contract', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','doc','docx'],
                'default_label' => 'confidential',
            ],
            'other' => [
                'name' => __('Other document', 'vgbm-property-manager'),
                'allowed_extensions' => ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx','csv','txt','zip'],
                'default_label' => 'internal',
            ],
        ];
    }

    public static function get_labels(): array {
        $labels = get_option(self::OPT_LABELS, []);
        return is_array($labels) ? $labels : [];
    }

    public static function get_types(): array {
        $types = get_option(self::OPT_TYPES, []);
        return is_array($types) ? $types : [];
    }

    public static function label_name(string $key): string {
        $labels = self::get_labels();
        return isset($labels[$key]['name']) ? (string)$labels[$key]['name'] : $key;
    }

    public static function type_name(string $key): string {
        $types = self::get_types();
        return isset($types[$key]['name']) ? (string)$types[$key]['name'] : $key;
    }

    public static function allowed_extensions_for_type(string $type_key): array {
        $types = self::get_types();
        $ext = $types[$type_key]['allowed_extensions'] ?? [];
        if (!is_array($ext)) { return []; }
        $clean = [];
        foreach ($ext as $e) {
            $e = strtolower(trim((string)$e));
            $e = ltrim($e, '.');
            if ($e !== '') { $clean[] = $e; }
        }
        return array_values(array_unique($clean));
    }

    public static function default_label_for_type(string $type_key): string {
        $types = self::get_types();
        $label = (string)($types[$type_key]['default_label'] ?? 'internal');
        return $label ?: 'internal';
    }

    public static function sanitize_labels($raw): array {
        if (!is_array($raw)) { return self::default_labels(); }
        $out = [];
        foreach ($raw as $k => $v) {
            $key = sanitize_key((string)$k);
            if ($key === '') { continue; }
            $name = is_array($v) && isset($v['name']) ? sanitize_text_field($v['name']) : '';
            $sens = is_array($v) && isset($v['sensitivity']) ? sanitize_key($v['sensitivity']) : 'internal';
            if (!in_array($sens, ['public','tenant','internal','confidential','sensitive'], true)) { $sens = 'internal'; }
            if ($name === '') { $name = $key; }
            $out[$key] = ['name' => $name, 'sensitivity' => $sens];
        }
        return !empty($out) ? $out : self::default_labels();
    }

    public static function sanitize_types($raw): array {
        if (!is_array($raw)) { return self::default_types(); }
        $out = [];
        foreach ($raw as $k => $v) {
            $key = sanitize_key((string)$k);
            if ($key === '') { continue; }
            $name = is_array($v) && isset($v['name']) ? sanitize_text_field($v['name']) : '';
            if ($name === '') { $name = $key; }

            $ext_str = is_array($v) && isset($v['allowed_extensions']) ? (string)$v['allowed_extensions'] : '';
            $exts = preg_split('/\s*,\s*/', strtolower($ext_str));
            $exts = array_values(array_unique(array_filter(array_map(function ($e) {
                $e = strtolower(trim((string)$e));
                $e = ltrim($e, '.');
                return $e;
            }, $exts), fn($e) => $e !== '')));

            $default_label = is_array($v) && isset($v['default_label']) ? sanitize_key((string)$v['default_label']) : 'internal';
            if ($default_label === '') { $default_label = 'internal'; }

            $enabled = is_array($v) && isset($v['enabled']) ? (int)$v['enabled'] : 1;

            $out[$key] = [
                'name' => $name,
                'allowed_extensions' => $exts,
                'default_label' => $default_label,
                'enabled' => $enabled ? 1 : 0,
            ];
        }
        return !empty($out) ? $out : self::default_types();
    }

    /**
     * Add additional upload mimes for configured extensions (best-effort).
     * Only for users allowed to manage VGBM data.
     */
    public static function filter_upload_mimes(array $mimes): array {
        if (!current_user_can('manage_options') && !current_user_can('vgbm_manage')) {
            return $mimes;
        }

        $types = self::get_types();
        $exts = [];
        foreach ($types as $t) {
            $allowed = $t['allowed_extensions'] ?? [];
            if (is_array($allowed)) {
                foreach ($allowed as $e) { $exts[] = strtolower(trim((string)$e)); }
            }
        }
        $exts = array_values(array_unique(array_filter($exts)));

        $map = self::extension_mime_map();

        foreach ($exts as $ext) {
            if (isset($map[$ext])) {
                $mimes[$ext] = $map[$ext];
            }
        }
        return $mimes;
    }

    public static function extension_mime_map(): array {
        // Note: some formats (dwg/dxf/ifc) can vary; we register best-effort.
        return [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv'  => 'text/csv',
            'txt'  => 'text/plain',
            'zip'  => 'application/zip',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'dwg'  => 'image/vnd.dwg',
            'dxf'  => 'image/vnd.dxf',
        ];
    }
}
