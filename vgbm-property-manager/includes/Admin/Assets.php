<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class Assets {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook_suffix): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) { return; }

        // List screens row click selection.
        if ((string) $screen->base === 'edit') {
            $allowed = [
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

            if (in_array((string) $screen->post_type, $allowed, true)) {
                wp_enqueue_script(
                    'vgbm-pm-admin-list-select',
                    VGBM_PM_URL . 'assets/admin-list-select.js',
                    ['jquery'],
                    VGBM_PM_VERSION,
                    true
                );
            }
        }

        // Contract editor: enable media picker + rent elements UI.
        if ((string) $screen->base === 'post' && (string) $screen->post_type === PostTypes::CPT_CONTRACT) {
            wp_enqueue_media();

            wp_enqueue_script(
                'vgbm-pm-admin-contract',
                VGBM_PM_URL . 'assets/admin-contract.js',
                ['jquery'],
                VGBM_PM_VERSION,
                true
            );

            wp_enqueue_script(
                'vgbm-pm-admin-contract-elements',
                VGBM_PM_URL . 'assets/admin-contract-elements.js',
                ['jquery'],
                VGBM_PM_VERSION,
                true
            );
        }

        // Utility editor: media picker
        if ((string) $screen->base === 'post' && (string) $screen->post_type === PostTypes::CPT_UTILITY) {
            wp_enqueue_media();
            wp_enqueue_script(
                'vgbm-pm-admin-utility',
                VGBM_PM_URL . 'assets/admin-utility.js',
                ['jquery'],
                VGBM_PM_VERSION,
                true
            );
        }

        // Meter reading editor: media picker
        if ((string) $screen->base === 'post' && (string) $screen->post_type === PostTypes::CPT_READING) {
            wp_enqueue_media();
            wp_enqueue_script(
                'vgbm-pm-admin-reading',
                VGBM_PM_URL . 'assets/admin-reading.js',
                ['jquery'],
                VGBM_PM_VERSION,
                true
            );
        }

        // Allocation editor: participants UI
        if ((string) $screen->base === 'post' && (string) $screen->post_type === PostTypes::CPT_ALLOCATION) {
            wp_enqueue_script(
                'vgbm-pm-admin-allocation',
                VGBM_PM_URL . 'assets/admin-allocation.js',
                ['jquery'],
                VGBM_PM_VERSION,
                true
            );
        }

        // Document editor: media picker
        if ((string) $screen->base === 'post' && (string) $screen->post_type === PostTypes::CPT_DOCUMENT) {
            wp_enqueue_media();
            wp_enqueue_script(
                'vgbm-pm-admin-document',
                VGBM_PM_URL . 'assets/admin-document.js',
                ['jquery'],
                VGBM_PM_VERSION,
                true
            );
        }

// Entity documents box: attach existing documents (AJAX)
if ((string) $screen->base === 'post') {
    $entity_types = [
        PostTypes::CPT_PORTFOLIO,
        PostTypes::CPT_PROPERTY,
        PostTypes::CPT_UNIT,
        PostTypes::CPT_RENTER,
        PostTypes::CPT_CONTRACT,
        PostTypes::CPT_UTILITY,
        PostTypes::CPT_READING,
    ];
    if (in_array((string) $screen->post_type, $entity_types, true)) {
        wp_enqueue_script(
            'vgbm-pm-admin-doc-links',
            VGBM_PM_URL . 'assets/admin-doc-links.js',
            ['jquery'],
            VGBM_PM_VERSION,
            true
        );
    }
}

    }
}
