<?php
namespace VGBM\PM\Utils;

if (!defined('ABSPATH')) { exit; }

/**
 * Utility for rent element calculations and indexation history.
 * All amounts are assumed monthly gross amounts in EUR.
 */
final class RentCalculator {

    /**
     * Normalizes elements array from post meta.
     *
     * @return array<int, array{label:string, amount:float, indexable:bool}>
     */
    public static function normalize_elements($raw): array {
        if (!is_array($raw)) { return []; }

        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) { continue; }
            $label = isset($row['label']) ? sanitize_text_field((string) $row['label']) : '';
            $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
            $indexable = !empty($row['indexable']);

            if ($label === '' && $amount == 0.0) { continue; }

            $out[] = [
                'label' => $label !== '' ? $label : __('(unnamed)', 'vgbm-property-manager'),
                'amount' => round($amount, 2),
                'indexable' => (bool) $indexable,
            ];
        }
        return $out;
    }

    public static function total(array $elements): float {
        $sum = 0.0;
        foreach ($elements as $e) {
            $sum += isset($e['amount']) ? (float) $e['amount'] : 0.0;
        }
        return round($sum, 2);
    }

    /**
     * Returns last applied year from history.
     */
    public static function last_year($history): int {
        if (!is_array($history) || empty($history)) { return 0; }
        $years = array_map('intval', array_keys($history));
        rsort($years);
        return (int) ($years[0] ?? 0);
    }

    /**
     * Returns elements for the latest applied year, or initial elements.
     */
    public static function base_elements_for_year(array $initial_elements, $history, int $year): array {
        $history = is_array($history) ? $history : [];
        $latest = 0;
        foreach ($history as $y => $row) {
            $y = (int) $y;
            if ($y < $year && $y > $latest && isset($row['elements']) && is_array($row['elements'])) {
                $latest = $y;
            }
        }

        if ($latest > 0 && isset($history[(string)$latest]['elements']) && is_array($history[(string)$latest]['elements'])) {
            return self::normalize_elements($history[(string)$latest]['elements']);
        }

        return $initial_elements;
    }

    /**
     * Applies a percentage indexation to indexable elements.
     *
     * @param array $base_elements normalized elements
     * @param float $rate_percent e.g. 2.5
     * @return array{elements: array, total: float}
     */
    public static function apply_indexation(array $base_elements, float $rate_percent): array {
        $mult = 1.0 + ($rate_percent / 100.0);

        $new = [];
        foreach ($base_elements as $e) {
            $amount = (float) $e['amount'];
            if (!empty($e['indexable'])) {
                $amount = round($amount * $mult, 2);
            }
            $new[] = [
                'label' => (string) $e['label'],
                'amount' => $amount,
                'indexable' => (bool) $e['indexable'],
            ];
        }

        return [
            'elements' => $new,
            'total' => self::total($new),
        ];
    }

    /**
     * Builds a preview/apply result for a given year.
     *
     * @return array{year:int, rate:float, base_total:float, new_total:float, base_elements:array, new_elements:array, base_year:int}
     */
    public static function compute_for_year(array $initial_elements, $history, int $year, float $rate_percent): array {
        $initial_elements = self::normalize_elements($initial_elements);
        $history = is_array($history) ? $history : [];

        $base_elements = self::base_elements_for_year($initial_elements, $history, $year);
        $base_total = self::total($base_elements);

        $applied = self::apply_indexation($base_elements, $rate_percent);

        // Determine base year used for compounding display
        $base_year = 0;
        foreach ($history as $y => $row) {
            $y = (int)$y;
            if ($y < $year && isset($row['elements']) && is_array($row['elements'])) {
                $base_year = max($base_year, $y);
            }
        }

        return [
            'year' => $year,
            'rate' => round($rate_percent, 4),
            'base_total' => $base_total,
            'new_total' => (float) $applied['total'],
            'base_elements' => $base_elements,
            'new_elements' => (array) $applied['elements'],
            'base_year' => $base_year,
        ];
    }

    /**
     * Persists indexation result into history.
     */
    public static function save_history(int $contract_id, array $result): void {
        $year = (int) ($result['year'] ?? 0);
        if ($year <= 0) { return; }

        $history = get_post_meta($contract_id, '_vgbm_indexation_history', true);
        if (!is_array($history)) { $history = []; }

        $history[(string)$year] = [
            'rate' => (float) ($result['rate'] ?? 0.0),
            'base_year' => (int) ($result['base_year'] ?? 0),
            'base_total' => (float) ($result['base_total'] ?? 0.0),
            'new_total' => (float) ($result['new_total'] ?? 0.0),
            'elements' => (array) ($result['new_elements'] ?? []),
            'applied_at' => time(),
        ];

        update_post_meta($contract_id, '_vgbm_indexation_history', $history);
    }

    /**
     * Gets current (latest) elements and total.
     *
     * @return array{year:int, total:float}
     */
    public static function current_total(int $contract_id): array {
        $initial = get_post_meta($contract_id, '_vgbm_rent_elements', true);
        $initial = self::normalize_elements($initial);

        $history = get_post_meta($contract_id, '_vgbm_indexation_history', true);
        if (!is_array($history)) { $history = []; }

        $last = self::last_year($history);
        if ($last > 0 && isset($history[(string)$last]['new_total'])) {
            return ['year' => $last, 'total' => round((float) $history[(string)$last]['new_total'], 2)];
        }

        return ['year' => 0, 'total' => self::total($initial)];
    }
}
