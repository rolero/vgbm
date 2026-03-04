<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Admin\MetaBoxes\PortfolioMetaBox;
use VGBM\PM\Admin\MetaBoxes\PropertyMetaBox;
use VGBM\PM\Admin\MetaBoxes\UnitMetaBox;
use VGBM\PM\Admin\MetaBoxes\RenterMetaBox;
use VGBM\PM\Admin\MetaBoxes\UtilityMetaBox;
use VGBM\PM\Admin\MetaBoxes\UtilityReadingsBox;
use VGBM\PM\Admin\MetaBoxes\UtilityAllocationsBox;
use VGBM\PM\Admin\MetaBoxes\ReadingMetaBox;
use VGBM\PM\Admin\MetaBoxes\AllocationMetaBox;
use VGBM\PM\Admin\MetaBoxes\DocumentMetaBox;
use VGBM\PM\Admin\MetaBoxes\EntityDocumentsBox;
use VGBM\PM\Admin\MetaBoxes\ContractMetaBox;
use VGBM\PM\Admin\MetaBoxes\TicketMetaBox;

if (!defined('ABSPATH')) { exit; }

final class Admin {

    public function register(): void {
        if (!is_admin()) { return; }

        add_action('add_meta_boxes', [$this, 'meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_PORTFOLIO . '_posts_columns', [$this, 'portfolio_columns']);
        add_action('manage_' . PostTypes::CPT_PORTFOLIO . '_posts_custom_column', [$this, 'render_portfolio_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_PROPERTY . '_posts_columns', [$this, 'property_columns']);
        add_action('manage_' . PostTypes::CPT_PROPERTY . '_posts_custom_column', [$this, 'render_property_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_UNIT . '_posts_columns', [$this, 'unit_columns']);
        add_action('manage_' . PostTypes::CPT_UNIT . '_posts_custom_column', [$this, 'render_unit_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_RENTER . '_posts_columns', [$this, 'renter_columns']);
        add_action('manage_' . PostTypes::CPT_RENTER . '_posts_custom_column', [$this, 'render_renter_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_UTILITY . '_posts_columns', [$this, 'utility_columns']);
        add_action('manage_' . PostTypes::CPT_UTILITY . '_posts_custom_column', [$this, 'render_utility_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_READING . '_posts_columns', [$this, 'reading_columns']);
        add_action('manage_' . PostTypes::CPT_READING . '_posts_custom_column', [$this, 'render_reading_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_ALLOCATION . '_posts_columns', [$this, 'allocation_columns']);
        add_action('manage_' . PostTypes::CPT_ALLOCATION . '_posts_custom_column', [$this, 'render_allocation_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_DOCUMENT . '_posts_columns', [$this, 'document_columns']);
        add_action('manage_' . PostTypes::CPT_DOCUMENT . '_posts_custom_column', [$this, 'render_document_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_CONTRACT . '_posts_columns', [$this, 'contract_columns']);
        add_action('manage_' . PostTypes::CPT_CONTRACT . '_posts_custom_column', [$this, 'render_contract_column'], 10, 2);

        add_filter('manage_' . PostTypes::CPT_TICKET . '_posts_columns', [$this, 'ticket_columns']);
        add_action('manage_' . PostTypes::CPT_TICKET . '_posts_custom_column', [$this, 'render_ticket_column'], 10, 2);
    }

    public function meta_boxes(): void {
        add_meta_box('vgbm_pm_portfolio_details', __('Portfolio details', 'vgbm-property-manager'),
            [new PortfolioMetaBox(), 'render'], PostTypes::CPT_PORTFOLIO, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_property_details', __('Property details', 'vgbm-property-manager'),
            [new PropertyMetaBox(), 'render'], PostTypes::CPT_PROPERTY, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_unit_details', __('Unit details', 'vgbm-property-manager'),
            [new UnitMetaBox(), 'render'], PostTypes::CPT_UNIT, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_renter_details', __('Renter details', 'vgbm-property-manager'),
            [new RenterMetaBox(), 'render'], PostTypes::CPT_RENTER, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_utility_details', __('Utility / Meter details', 'vgbm-property-manager'),
            [new UtilityMetaBox(), 'render'], PostTypes::CPT_UTILITY, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_utility_allocations', __('Allocations', 'vgbm-property-manager'),
            [new UtilityAllocationsBox(), 'render'], PostTypes::CPT_UTILITY, 'side', 'default'
        );

        add_meta_box('vgbm_pm_utility_readings', __('Recent meter readings', 'vgbm-property-manager'),
            [new UtilityReadingsBox(), 'render'], PostTypes::CPT_UTILITY, 'side', 'default'
        );

        add_meta_box('vgbm_pm_reading_details', __('Meter reading details', 'vgbm-property-manager'),
            [new ReadingMetaBox(), 'render'], PostTypes::CPT_READING, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_allocation_details', __('Allocation details', 'vgbm-property-manager'),
            [new AllocationMetaBox(), 'render'], PostTypes::CPT_ALLOCATION, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_document_details', __('Document details', 'vgbm-property-manager'),
            [new DocumentMetaBox(), 'render'], PostTypes::CPT_DOCUMENT, 'normal', 'default'
        );

        // Documents on entities
        $docBox = new EntityDocumentsBox();
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_PORTFOLIO, 'side', 'default'
        );
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_PROPERTY, 'side', 'default'
        );
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_UNIT, 'side', 'default'
        );
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_RENTER, 'side', 'default'
        );
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_CONTRACT, 'side', 'default'
        );
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_UTILITY, 'side', 'default'
        );
        add_meta_box('vgbm_pm_entity_documents', __('Documents', 'vgbm-property-manager'),
            [$docBox, 'render'], PostTypes::CPT_READING, 'side', 'default'
        );

        add_meta_box('vgbm_pm_contract_details', __('Contract details', 'vgbm-property-manager'),
            [new ContractMetaBox(), 'render'], PostTypes::CPT_CONTRACT, 'normal', 'default'
        );

        add_meta_box('vgbm_pm_ticket_details', __('Ticket details', 'vgbm-property-manager'),
            [new TicketMetaBox(), 'render'], PostTypes::CPT_TICKET, 'side', 'default'
        );
    }

    public function save_meta_boxes(int $post_id, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (wp_is_post_revision($post_id)) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }

        switch ($post->post_type) {
            case PostTypes::CPT_PORTFOLIO:
                (new PortfolioMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_PROPERTY:
                (new PropertyMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_UNIT:
                (new UnitMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_RENTER:
                (new RenterMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_UTILITY:
                (new UtilityMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_READING:
                (new ReadingMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_ALLOCATION:
                (new AllocationMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_DOCUMENT:
                (new DocumentMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_CONTRACT:
                (new ContractMetaBox())->save($post_id);
                break;
            case PostTypes::CPT_TICKET:
                (new TicketMetaBox())->save($post_id);
                break;
        }
    }

    private function ensure_checkbox_column(array $columns): array {
        if (!isset($columns['cb'])) {
            $columns = array_merge(['cb' => '<input type="checkbox" />'], $columns);
        } else {
            $cb = ['cb' => $columns['cb']];
            unset($columns['cb']);
            $columns = $cb + $columns;
        }
        return $columns;
    }

    public function portfolio_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_company'] = __('Company', 'vgbm-property-manager');
                $new['vgbm_email'] = __('Email', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_portfolio_column(string $column, int $post_id): void {
        if ($column === 'vgbm_company') {
            $company = get_post_meta($post_id, '_vgbm_company', true);
            echo $company ? esc_html((string)$company) : '—';
        }
        if ($column === 'vgbm_email') {
            $email = get_post_meta($post_id, '_vgbm_email', true);
            echo $email ? esc_html((string)$email) : '—';
        }
    }

    public function property_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_portfolio'] = __('Portfolio', 'vgbm-property-manager');
                $new['vgbm_city'] = __('City', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_property_column(string $column, int $post_id): void {
        if ($column === 'vgbm_portfolio') {
            $pf = (int) get_post_meta($post_id, '_vgbm_portfolio_id', true);
            echo $pf ? esc_html(get_the_title($pf)) : '—';
        }
        if ($column === 'vgbm_city') {
            $city = get_post_meta($post_id, '_vgbm_city', true);
            echo $city ? esc_html((string)$city) : '—';
        }
    }

    public function unit_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_property'] = __('Property', 'vgbm-property-manager');
                $new['vgbm_rent'] = __('Rent', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_unit_column(string $column, int $post_id): void {
        if ($column === 'vgbm_property') {
            $pid = (int) get_post_meta($post_id, '_vgbm_property_id', true);
            echo $pid ? esc_html(get_the_title($pid)) : '—';
        }
        if ($column === 'vgbm_rent') {
            $rent = get_post_meta($post_id, '_vgbm_rent_amount', true);
            echo $rent ? esc_html((string)$rent) : '—';
        }
    }

    public function renter_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_email'] = __('Email', 'vgbm-property-manager');
                $new['vgbm_phone'] = __('Phone', 'vgbm-property-manager');
                $new['vgbm_linked_user'] = __('Portal user', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_renter_column(string $column, int $post_id): void {
        if ($column === 'vgbm_email') {
            $email = get_post_meta($post_id, '_vgbm_email', true);
            echo $email ? esc_html((string)$email) : '—';
        }
        if ($column === 'vgbm_phone') {
            $phone = get_post_meta($post_id, '_vgbm_phone', true);
            echo $phone ? esc_html((string)$phone) : '—';
        }
        if ($column === 'vgbm_linked_user') {
            $uid = (int) get_post_meta($post_id, '_vgbm_linked_user_id', true);
            if (!$uid) { echo '—'; return; }
            $u = get_user_by('id', $uid);
            echo $u ? esc_html((string)$u->display_name) : '—';
        }
    }

    public function utility_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_kind'] = __('Type', 'vgbm-property-manager');
                $new['vgbm_scope'] = __('Scope', 'vgbm-property-manager');
                $new['vgbm_ean'] = __('EAN', 'vgbm-property-manager');
                $new['vgbm_meter'] = __('Meter no.', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_utility_column(string $column, int $post_id): void {
        if ($column === 'vgbm_kind') {
            $kind = (string) get_post_meta($post_id, '_vgbm_kind', true);
            echo $kind ? esc_html($kind) : '—';
        }
        if ($column === 'vgbm_scope') {
            $pid = (int) get_post_meta($post_id, '_vgbm_property_id', true);
            $uid = (int) get_post_meta($post_id, '_vgbm_unit_id', true);
            if ($uid) {
                echo esc_html__('Unit', 'vgbm-property-manager') . ': ' . esc_html(get_the_title($uid));
            } elseif ($pid) {
                echo esc_html__('Property', 'vgbm-property-manager') . ': ' . esc_html(get_the_title($pid));
            } else {
                echo '—';
            }
        }
        if ($column === 'vgbm_ean') {
            $ean = (string) get_post_meta($post_id, '_vgbm_ean', true);
            echo $ean ? esc_html($ean) : '—';
        }
        if ($column === 'vgbm_meter') {
            $mn = (string) get_post_meta($post_id, '_vgbm_meter_number', true);
            echo $mn ? esc_html($mn) : '—';
        }
    }

    public function reading_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_utility'] = __('Utility', 'vgbm-property-manager');
                $new['vgbm_date'] = __('Date', 'vgbm-property-manager');
                $new['vgbm_value'] = __('Value', 'vgbm-property-manager');
                $new['vgbm_uom'] = __('Unit', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_reading_column(string $column, int $post_id): void {
        if ($column === 'vgbm_utility') {
            $uid = (int) get_post_meta($post_id, '_vgbm_utility_id', true);
            echo $uid ? esc_html(get_the_title($uid)) : '—';
        }
        if ($column === 'vgbm_date') {
            $d = (string) get_post_meta($post_id, '_vgbm_reading_date', true);
            echo $d ? esc_html($d) : '—';
        }
        if ($column === 'vgbm_value') {
            $v = get_post_meta($post_id, '_vgbm_value', true);
            echo ($v !== '') ? esc_html((string)$v) : '—';
        }
        if ($column === 'vgbm_uom') {
            $uom = (string) get_post_meta($post_id, '_vgbm_uom', true);
            echo $uom ? esc_html($uom) : '—';
        }
    }

    public function allocation_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_utility'] = __('Utility', 'vgbm-property-manager');
                $new['vgbm_split'] = __('Split', 'vgbm-property-manager');
                $new['vgbm_participants'] = __('Participants', 'vgbm-property-manager');
                $new['vgbm_effective'] = __('Effective', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_allocation_column(string $column, int $post_id): void {
        if ($column === 'vgbm_utility') {
            $uid = (int) get_post_meta($post_id, '_vgbm_utility_id', true);
            echo $uid ? esc_html(get_the_title($uid)) : '—';
        }
        if ($column === 'vgbm_split') {
            $split = (string) get_post_meta($post_id, '_vgbm_split_method', true);
            echo $split ? esc_html($split) : '—';
        }
        if ($column === 'vgbm_participants') {
            $parts = get_post_meta($post_id, '_vgbm_participants', true);
            if (!is_array($parts) || empty($parts)) { echo '—'; return; }
            $labels = [];
            foreach ($parts as $p) {
                $cid = (int)($p['contract_id'] ?? 0);
                if (!$cid) { continue; }
                $share = (float)($p['share'] ?? 0);
                $labels[] = get_the_title($cid) . ($share ? (' (' . $share . '%)') : '');
            }
            echo $labels ? esc_html(implode('; ', $labels)) : '—';
        }
        if ($column === 'vgbm_effective') {
            $f = (string) get_post_meta($post_id, '_vgbm_effective_from', true);
            $t = (string) get_post_meta($post_id, '_vgbm_effective_to', true);
            if (!$f && !$t) { echo '—'; return; }
            echo esc_html(($f ?: '—') . ' → ' . ($t ?: '—'));
        }
    }

public function document_columns(array $columns): array {
    $columns = $this->ensure_checkbox_column($columns);
    $new = [];
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['vgbm_doc_type'] = __('Type', 'vgbm-property-manager');
            $new['vgbm_doc_label'] = __('Privacy', 'vgbm-property-manager');
            $new['vgbm_doc_linked'] = __('Linked', 'vgbm-property-manager');
            $new['vgbm_doc_file'] = __('File', 'vgbm-property-manager');
        }
    }
    return $new;
}

public function render_document_column(string $column, int $post_id): void {
    if ($column === 'vgbm_doc_type') {
        $type = (string) get_post_meta($post_id, '_vgbm_doc_type', true);
        echo esc_html(\VGBM\PM\Documents\Config::type_name($type));
    }
    if ($column === 'vgbm_doc_label') {
        $label = (string) get_post_meta($post_id, '_vgbm_doc_label', true);
        echo esc_html(\VGBM\PM\Documents\Config::label_name($label));
    }
    if ($column === 'vgbm_doc_linked') {
        $linked = get_post_meta($post_id, '_vgbm_linked_post_ids', true);
        if (!is_array($linked)) { echo '—'; return; }
        $linked = array_values(array_filter(array_map('intval', $linked), fn($v) => $v > 0));
        echo $linked ? esc_html((string)count($linked)) : '—';
    }
    if ($column === 'vgbm_doc_file') {
        $att = (int) get_post_meta($post_id, '_vgbm_current_attachment_id', true);
        if (!$att) { echo '—'; return; }
        $url = add_query_arg(['vgbm_doc_file' => $post_id], home_url('/'));
        echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html__('Download', 'vgbm-property-manager') . '</a>';
    }
}

    public function contract_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_units'] = __('Unit(s)', 'vgbm-property-manager');
                $new['vgbm_renters'] = __('Renters', 'vgbm-property-manager');
                $new['vgbm_period'] = __('Period', 'vgbm-property-manager');
                $new['vgbm_rent_total'] = __('Rent total', 'vgbm-property-manager');
                $new['vgbm_index_year'] = __('Index year', 'vgbm-property-manager');
                $new['vgbm_status'] = __('Status', 'vgbm-property-manager');
                $new['vgbm_doc'] = __('Document', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_contract_column(string $column, int $post_id): void {
        if ($column === 'vgbm_rent_total') {
            $cur = \VGBM\PM\Utils\RentCalculator::current_total($post_id);
            echo esc_html(number_format_i18n((float)$cur['total'], 2));
        }
        if ($column === 'vgbm_index_year') {
            $cur = \VGBM\PM\Utils\RentCalculator::current_total($post_id);
            echo $cur['year'] ? esc_html((string)$cur['year']) : '—';
        }

        if ($column === 'vgbm_units') {
            $unit_ids = get_post_meta($post_id, '_vgbm_unit_ids', true);
            if (!is_array($unit_ids) || empty($unit_ids)) {
                $legacy = (int) get_post_meta($post_id, '_vgbm_unit_id', true);
                $unit_ids = $legacy ? [$legacy] : [];
            }
            $unit_ids = array_values(array_filter(array_map('intval', $unit_ids), fn($v) => $v > 0));
            if (empty($unit_ids)) { echo '—'; return; }
            $titles = array_map(fn($id) => get_the_title($id), $unit_ids);
            $titles = array_values(array_filter($titles));
            if (empty($titles)) { echo '—'; return; }
            $first = $titles[0];
            $extra = count($titles) - 1;
            echo esc_html($extra > 0 ? ($first . ' +' . $extra) : $first);
        }

        if ($column === 'vgbm_renters') {
            $renter_ids = get_post_meta($post_id, '_vgbm_renter_ids', true);
            if (!is_array($renter_ids) || empty($renter_ids)) { echo '—'; return; }
            $names = [];
            foreach ($renter_ids as $rid) {
                $p = get_post((int)$rid);
                if ($p) { $names[] = $p->post_title; }
            }
            echo $names ? esc_html(implode(', ', $names)) : '—';
        }
        if ($column === 'vgbm_period') {
            $start = (string) get_post_meta($post_id, '_vgbm_start_date', true);
            $end = (string) get_post_meta($post_id, '_vgbm_end_date', true);
            echo esc_html(($start ?: '—') . ' → ' . ($end ?: '—'));
        }
        if ($column === 'vgbm_status') {
            $status = (string) get_post_meta($post_id, '_vgbm_contract_status', true);
            echo $status ? esc_html($status) : '—';
        }
        if ($column === 'vgbm_doc') {
            $doc_id = (int) get_post_meta($post_id, '_vgbm_contract_document_id', true);
            if ($doc_id) {
                $url = add_query_arg(['vgbm_contract_doc' => $post_id], home_url('/'));
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html__('View', 'vgbm-property-manager') . '</a>';
            } else {
                echo '—';
            }
        }
    }

    public function ticket_columns(array $columns): array {
        $columns = $this->ensure_checkbox_column($columns);
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['vgbm_status'] = __('Status', 'vgbm-property-manager');
                $new['vgbm_unit'] = __('Unit', 'vgbm-property-manager');
                $new['vgbm_assigned'] = __('Assigned to', 'vgbm-property-manager');
            }
        }
        return $new;
    }

    public function render_ticket_column(string $column, int $post_id): void {
        if ($column === 'vgbm_status') {
            echo esc_html(get_post_meta($post_id, '_vgbm_status', true) ?: 'open');
        }
        if ($column === 'vgbm_unit') {
            $unit_id = (int) get_post_meta($post_id, '_vgbm_unit_id', true);
            echo $unit_id ? esc_html(get_the_title($unit_id)) : '—';
        }
        if ($column === 'vgbm_assigned') {
            $uid = (int) get_post_meta($post_id, '_vgbm_assigned_user', true);
            if ($uid) {
                $u = get_user_by('id', $uid);
                echo $u ? esc_html($u->display_name) : '—';
            } else {
                echo '—';
            }
        }
    }
}
