<?php
namespace VGBM\PM\Admin;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\Security;

if (!defined('ABSPATH')) { exit; }

final class UserProfile {

    private const META_UNITS = 'vgbm_assigned_units';

    public function register(): void {
        if (!is_admin()) { return; }

        add_action('show_user_profile', [$this, 'render']);
        add_action('edit_user_profile', [$this, 'render']);

        add_action('personal_options_update', [$this, 'save']);
        add_action('edit_user_profile_update', [$this, 'save']);
    }

    public function render(\WP_User $user): void {
        if (!Security::can_manage_vgbm()) { return; }

        $units = get_user_meta($user->ID, self::META_UNITS, true);
        if (!is_array($units)) { $units = []; }

        $all_units = get_posts([
            'post_type' => PostTypes::CPT_UNIT,
            'numberposts' => 400,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ?>
        <h2><?php esc_html_e('VGBM Property Manager', 'vgbm-property-manager'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="vgbm_assigned_units"><?php esc_html_e('Assigned units', 'vgbm-property-manager'); ?></label></th>
                <td>
                    <?php wp_nonce_field('vgbm_pm_save_user_units', 'vgbm_pm_user_units_nonce'); ?>
                    <select id="vgbm_assigned_units" name="vgbm_assigned_units[]" multiple size="8" style="width: 25em;">
                        <?php foreach ($all_units as $unit): ?>
                            <option value="<?php echo esc_attr((string)$unit->ID); ?>" <?php selected(in_array($unit->ID, $units, true)); ?>>
                                <?php echo esc_html($unit->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Select the unit(s) this user rents. The ticket form will show only these units.', 'vgbm-property-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save(int $user_id): void {
        if (!Security::can_manage_vgbm()) { return; }

        if (empty($_POST['vgbm_pm_user_units_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_user_units_nonce'])), 'vgbm_pm_save_user_units')) {
            return;
        }

        $units = isset($_POST['vgbm_assigned_units']) ? (array) $_POST['vgbm_assigned_units'] : [];
        $units = array_values(array_filter(array_map('intval', $units), fn($v) => $v > 0));

        update_user_meta($user_id, self::META_UNITS, $units);
    }

    public static function get_assigned_units_for_current_user(): array {
        if (!is_user_logged_in()) { return []; }

        $uid = get_current_user_id();

        // 1) Manual assignment
        $units = get_user_meta($uid, self::META_UNITS, true);
        if (is_array($units) && !empty($units)) {
            return array_values(array_map('intval', $units));
        }

        // 2) Derive via renter profile(s) linked to this WP user (optional portal model)
        $renter_posts = get_posts([
            'post_type' => \VGBM\PM\PostTypes\PostTypes::CPT_RENTER,
            'numberposts' => 50,
            'post_status' => ['publish', 'draft', 'private'],
            'meta_query' => [
                [
                    'key' => '_vgbm_linked_user_id',
                    'value' => $uid,
                    'compare' => '=',
                ],
            ],
        ]);

        $renter_ids = array_map(fn($p) => (int)$p->ID, $renter_posts);

        $meta_or = [];

        foreach ($renter_ids as $rid) {
            $meta_or[] = [
                'key' => '_vgbm_renter_ids',
                'value' => '"' . $rid . '"',
                'compare' => 'LIKE',
            ];
        }

        // Legacy support
        $meta_or[] = [
            'key' => '_vgbm_renter_user_ids',
            'value' => '"' . $uid . '"',
            'compare' => 'LIKE',
        ];

        $q = new \WP_Query([
            'post_type' => \VGBM\PM\PostTypes\PostTypes::CPT_CONTRACT,
            'posts_per_page' => 200,
            'post_status' => ['publish', 'private', 'draft'],
            'meta_query' => [
                'relation' => 'OR',
                ...$meta_or,
            ],
        ]);

        $unit_ids = [];
        if ($q->have_posts()) {
            foreach ($q->posts as $p) {
                $ids = get_post_meta($p->ID, '_vgbm_unit_ids', true);
                if (!is_array($ids) || empty($ids)) {
                    $legacy = (int) get_post_meta($p->ID, '_vgbm_unit_id', true);
                    $ids = $legacy ? [$legacy] : [];
                }
                foreach ((array)$ids as $uid) {
                    $uid = (int)$uid;
                    if ($uid > 0) { $unit_ids[] = $uid; }
                }
            }
        }
        wp_reset_postdata();

        $unit_ids = array_values(array_unique(array_filter($unit_ids)));
        return $unit_ids;
    }
}
