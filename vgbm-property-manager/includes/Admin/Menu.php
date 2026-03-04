<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class Menu {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('admin_menu', [$this, 'add_menu'], 9);
    }

    public function add_menu(): void {
        $can_see = current_user_can('vgbm_manage') || current_user_can('manage_options');
        if (!$can_see) {
            return;
        }

        add_menu_page(
            __('VGBM', 'vgbm-property-manager'),
            __('VGBM', 'vgbm-property-manager'),
            'read',
            'vgbm_pm',
            [$this, 'landing'],
            'dashicons-admin-home',
            25
        );

        // NOTE:
        // CPTs are registered with 'show_in_menu' => 'vgbm_pm'
        // so WordPress automatically adds their list screens under this menu.
    }

    public function landing(): void {
        $targets = [
            admin_url('edit.php?post_type=' . PostTypes::CPT_PORTFOLIO),
            admin_url('edit.php?post_type=' . PostTypes::CPT_PROPERTY),
            admin_url('edit.php?post_type=' . PostTypes::CPT_UNIT),
            admin_url('edit.php?post_type=' . PostTypes::CPT_RENTER),
            admin_url('edit.php?post_type=' . PostTypes::CPT_CONTRACT),
            admin_url('edit.php?post_type=' . PostTypes::CPT_DOCUMENT),
            admin_url('edit.php?post_type=' . PostTypes::CPT_TICKET),
        ];

        wp_safe_redirect($targets[0]);
        exit;
    }
}
