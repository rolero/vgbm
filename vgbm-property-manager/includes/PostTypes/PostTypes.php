<?php
namespace VGBM\PM\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class PostTypes {

    public const CPT_PORTFOLIO  = 'vgbm_portfolio';
    public const CPT_PROPERTY   = 'vgbm_property';
    public const CPT_UNIT       = 'vgbm_unit';

    public const CPT_RENTER     = 'vgbm_renter'; // renter profile (not necessarily WP user)

    public const CPT_UTILITY    = 'vgbm_utility';           // connection / meter
    public const CPT_READING    = 'vgbm_meter_reading';     // meter reading
    public const CPT_ALLOCATION = 'vgbm_u_alloc'; // utility allocation (shared/exclusive usage)

    public const CPT_DOCUMENT   = 'vgbm_document'; // document management

    public const CPT_CONTRACT   = 'vgbm_contract';
    public const CPT_TICKET     = 'vgbm_ticket';

    public function register(): void {
        add_action('init', [$this, 'register_post_types']);
    }

    public function register_post_types(): void {
        $this->register_portfolio();
        $this->register_property();
        $this->register_unit();
        $this->register_renter();
        $this->register_utility();
        $this->register_reading();
        $this->register_allocation();
        $this->register_document();
        $this->register_contract();
        $this->register_ticket();
    }

    private function base_hidden_menu_args(): array {
        return [
            'show_in_menu' => 'vgbm_pm',
            'show_in_admin_bar' => false,
        ];
    }

    private function register_portfolio(): void {
        $labels = [
            'name' => __('Portfolios', 'vgbm-property-manager'),
            'singular_name' => __('Portfolio', 'vgbm-property-manager'),
            'menu_name' => __('Portfolios', 'vgbm-property-manager'),
            'add_new' => __('Add portfolio', 'vgbm-property-manager'),
            'add_new_item' => __('Add new portfolio', 'vgbm-property-manager'),
            'edit_item' => __('Edit portfolio', 'vgbm-property-manager'),
            'new_item' => __('New portfolio', 'vgbm-property-manager'),
            'view_item' => __('View portfolio', 'vgbm-property-manager'),
            'search_items' => __('Search portfolios', 'vgbm-property-manager'),
            'not_found' => __('No portfolios found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No portfolios found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All portfolios', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_PORTFOLIO, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_portfolio', 'vgbm_portfolios'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => true,
        ], $this->base_hidden_menu_args()));
    }

    private function register_property(): void {
        $labels = [
            'name' => __('Properties', 'vgbm-property-manager'),
            'singular_name' => __('Property', 'vgbm-property-manager'),
            'menu_name' => __('Properties', 'vgbm-property-manager'),
            'add_new' => __('Add property', 'vgbm-property-manager'),
            'add_new_item' => __('Add new property', 'vgbm-property-manager'),
            'edit_item' => __('Edit property', 'vgbm-property-manager'),
            'new_item' => __('New property', 'vgbm-property-manager'),
            'view_item' => __('View property', 'vgbm-property-manager'),
            'search_items' => __('Search properties', 'vgbm-property-manager'),
            'not_found' => __('No properties found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No properties found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All properties', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_PROPERTY, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_property', 'vgbm_properties'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => true,
        ], $this->base_hidden_menu_args()));
    }

    private function register_unit(): void {
        $labels = [
            'name' => __('Units', 'vgbm-property-manager'),
            'singular_name' => __('Unit', 'vgbm-property-manager'),
            'menu_name' => __('Units', 'vgbm-property-manager'),
            'add_new' => __('Add unit', 'vgbm-property-manager'),
            'add_new_item' => __('Add new unit', 'vgbm-property-manager'),
            'edit_item' => __('Edit unit', 'vgbm-property-manager'),
            'new_item' => __('New unit', 'vgbm-property-manager'),
            'view_item' => __('View unit', 'vgbm-property-manager'),
            'search_items' => __('Search units', 'vgbm-property-manager'),
            'not_found' => __('No units found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No units found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All units', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_UNIT, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_unit', 'vgbm_units'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => true,
        ], $this->base_hidden_menu_args()));
    }

    private function register_renter(): void {
        $labels = [
            'name' => __('Renters', 'vgbm-property-manager'),
            'singular_name' => __('Renter', 'vgbm-property-manager'),
            'menu_name' => __('Renters', 'vgbm-property-manager'),
            'add_new' => __('Add renter', 'vgbm-property-manager'),
            'add_new_item' => __('Add new renter', 'vgbm-property-manager'),
            'edit_item' => __('Edit renter', 'vgbm-property-manager'),
            'new_item' => __('New renter', 'vgbm-property-manager'),
            'view_item' => __('View renter', 'vgbm-property-manager'),
            'search_items' => __('Search renters', 'vgbm-property-manager'),
            'not_found' => __('No renters found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No renters found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All renters', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_RENTER, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_renter', 'vgbm_renters'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => false,
        ], $this->base_hidden_menu_args()));
    }

    private function register_utility(): void {
        $labels = [
            'name' => __('Utilities', 'vgbm-property-manager'),
            'singular_name' => __('Utility', 'vgbm-property-manager'),
            'menu_name' => __('Utilities', 'vgbm-property-manager'),
            'add_new' => __('Add utility', 'vgbm-property-manager'),
            'add_new_item' => __('Add new utility', 'vgbm-property-manager'),
            'edit_item' => __('Edit utility', 'vgbm-property-manager'),
            'new_item' => __('New utility', 'vgbm-property-manager'),
            'view_item' => __('View utility', 'vgbm-property-manager'),
            'search_items' => __('Search utilities', 'vgbm-property-manager'),
            'not_found' => __('No utilities found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No utilities found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All utilities', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_UTILITY, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_utility', 'vgbm_utilities'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => false,
        ], $this->base_hidden_menu_args()));
    }

    private function register_reading(): void {
        $labels = [
            'name' => __('Meter readings', 'vgbm-property-manager'),
            'singular_name' => __('Meter reading', 'vgbm-property-manager'),
            'menu_name' => __('Meter readings', 'vgbm-property-manager'),
            'add_new' => __('Add meter reading', 'vgbm-property-manager'),
            'add_new_item' => __('Add new meter reading', 'vgbm-property-manager'),
            'edit_item' => __('Edit meter reading', 'vgbm-property-manager'),
            'new_item' => __('New meter reading', 'vgbm-property-manager'),
            'view_item' => __('View meter reading', 'vgbm-property-manager'),
            'search_items' => __('Search meter readings', 'vgbm-property-manager'),
            'not_found' => __('No meter readings found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No meter readings found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All meter readings', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_READING, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_reading', 'vgbm_readings'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => false,
        ], $this->base_hidden_menu_args()));
    }

    private function register_allocation(): void {
        $labels = [
            'name' => __('Utility allocations', 'vgbm-property-manager'),
            'singular_name' => __('Utility allocation', 'vgbm-property-manager'),
            'menu_name' => __('Utility allocations', 'vgbm-property-manager'),
            'add_new' => __('Add allocation', 'vgbm-property-manager'),
            'add_new_item' => __('Add new allocation', 'vgbm-property-manager'),
            'edit_item' => __('Edit allocation', 'vgbm-property-manager'),
            'new_item' => __('New allocation', 'vgbm-property-manager'),
            'view_item' => __('View allocation', 'vgbm-property-manager'),
            'search_items' => __('Search allocations', 'vgbm-property-manager'),
            'not_found' => __('No allocations found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No allocations found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All allocations', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_ALLOCATION, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => ['vgbm_allocation', 'vgbm_allocations'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => false,
        ], $this->base_hidden_menu_args()));
    }

private function register_document(): void {
    $labels = [
        'name' => __('Documents', 'vgbm-property-manager'),
        'singular_name' => __('Document', 'vgbm-property-manager'),
        'menu_name' => __('Documents', 'vgbm-property-manager'),
        'add_new' => __('Add document', 'vgbm-property-manager'),
        'add_new_item' => __('Add new document', 'vgbm-property-manager'),
        'edit_item' => __('Edit document', 'vgbm-property-manager'),
        'new_item' => __('New document', 'vgbm-property-manager'),
        'view_item' => __('View document', 'vgbm-property-manager'),
        'search_items' => __('Search documents', 'vgbm-property-manager'),
        'not_found' => __('No documents found', 'vgbm-property-manager'),
        'not_found_in_trash' => __('No documents found in Trash', 'vgbm-property-manager'),
        'all_items' => __('All documents', 'vgbm-property-manager'),
    ];

    register_post_type(self::CPT_DOCUMENT, array_merge([
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
        'capability_type' => ['vgbm_document', 'vgbm_documents'],
        'map_meta_cap' => true,
        'has_archive' => false,
        'show_in_rest' => false,
    ], $this->base_hidden_menu_args()));
}

    private function register_contract(): void {
        $labels = [
            'name' => __('Rental contracts', 'vgbm-property-manager'),
            'singular_name' => __('Rental contract', 'vgbm-property-manager'),
            'menu_name' => __('Rental contracts', 'vgbm-property-manager'),
            'add_new' => __('Add contract', 'vgbm-property-manager'),
            'add_new_item' => __('Add new contract', 'vgbm-property-manager'),
            'edit_item' => __('Edit contract', 'vgbm-property-manager'),
            'new_item' => __('New contract', 'vgbm-property-manager'),
            'view_item' => __('View contract', 'vgbm-property-manager'),
            'search_items' => __('Search contracts', 'vgbm-property-manager'),
            'not_found' => __('No contracts found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No contracts found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All contracts', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_CONTRACT, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => ['vgbm_contract', 'vgbm_contracts'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => false,
        ], $this->base_hidden_menu_args()));
    }

    private function register_ticket(): void {
        $labels = [
            'name' => __('Tickets', 'vgbm-property-manager'),
            'singular_name' => __('Ticket', 'vgbm-property-manager'),
            'menu_name' => __('Tickets', 'vgbm-property-manager'),
            'add_new' => __('Add ticket', 'vgbm-property-manager'),
            'add_new_item' => __('Add new ticket', 'vgbm-property-manager'),
            'edit_item' => __('Edit ticket', 'vgbm-property-manager'),
            'new_item' => __('New ticket', 'vgbm-property-manager'),
            'view_item' => __('View ticket', 'vgbm-property-manager'),
            'search_items' => __('Search tickets', 'vgbm-property-manager'),
            'not_found' => __('No tickets found', 'vgbm-property-manager'),
            'not_found_in_trash' => __('No tickets found in Trash', 'vgbm-property-manager'),
            'all_items' => __('All tickets', 'vgbm-property-manager'),
        ];

        register_post_type(self::CPT_TICKET, array_merge([
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => ['vgbm_ticket', 'vgbm_tickets'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'show_in_rest' => false,
        ], $this->base_hidden_menu_args()));
    }
}
