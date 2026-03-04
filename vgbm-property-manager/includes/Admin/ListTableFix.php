<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class ListTableFix {

    public function register(): void {
        if (!is_admin()) { return; }

        $pts = [
            PostTypes::CPT_PORTFOLIO,
            PostTypes::CPT_PROPERTY,
            PostTypes::CPT_UNIT,
            PostTypes::CPT_RENTER,
            PostTypes::CPT_UTILITY,
            PostTypes::CPT_READING,
            PostTypes::CPT_ALLOCATION,
            PostTypes::CPT_DOCUMENT,
            PostTypes::CPT_CONTRACT,
            PostTypes::CPT_TICKET,
        ];

        foreach ($pts as $pt) {
            add_filter('manage_' . $pt . '_posts_columns', [$this, 'ensure_cb_first'], 9999);
            add_filter('manage_edit-' . $pt . '_columns', [$this, 'ensure_cb_first'], 9999);
        }
    }

    public function ensure_cb_first(array $columns): array {
        if (!isset($columns['cb'])) {
            return ['cb' => '<input type="checkbox" />'] + $columns;
        }

        $cb = ['cb' => $columns['cb']];
        unset($columns['cb']);
        return $cb + $columns;
    }
}
