<?php
namespace VGBM\PM\Utils;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class Security {

    public static function sanitize_int($value): int {
        return (int) (is_scalar($value) ? $value : 0);
    }

    /**
     * Portal access:
     * - Staff roles + administrators
     * - OR any logged-in user that is linked to a renter profile (optional portal model)
     */
    public static function current_user_is_vgbm_renter_or_staff(): bool {
        if (!is_user_logged_in()) { return false; }

        // Staff + WP admins
        if (current_user_can('vgbm_manage') || current_user_can('manage_options') || current_user_can('administrator')) {
            return true;
        }

        // Optional: portal users are normal WP users linked to a renter profile.
        $uid = get_current_user_id();
        $r = get_posts([
            'post_type' => PostTypes::CPT_RENTER,
            'numberposts' => 1,
            'post_status' => ['publish', 'draft', 'private'],
            'meta_query' => [
                [
                    'key' => '_vgbm_linked_user_id',
                    'value' => $uid,
                    'compare' => '=',
                ],
            ],
        ]);

        return !empty($r);
    }

    public static function can_manage_vgbm(): bool {
        return current_user_can('vgbm_manage');
    }
}
