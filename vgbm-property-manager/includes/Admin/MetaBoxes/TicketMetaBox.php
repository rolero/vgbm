<?php
namespace VGBM\PM\Admin\MetaBoxes;

if (!defined('ABSPATH')) { exit; }

final class TicketMetaBox {

    private const STATUSES = ['open', 'in_progress', 'closed'];

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_ticket', 'vgbm_pm_ticket_nonce');

        $status = get_post_meta($post->ID, '_vgbm_status', true) ?: 'open';
        $assigned_user = (int) get_post_meta($post->ID, '_vgbm_assigned_user', true);

        $maintenance_users = get_users([
            'role__in' => ['vgbm_maintenance', 'vgbm_manager', 'administrator'],
            'number' => 200,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);

        ?>
        <p>
            <label><strong><?php esc_html_e('Status', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_status" class="widefat">
                <?php foreach (self::STATUSES as $s): ?>
                    <option value="<?php echo esc_attr($s); ?>" <?php selected($status, $s); ?>>
                        <?php echo esc_html($s); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong><?php esc_html_e('Assigned to', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_assigned_user" class="widefat">
                <option value="0"><?php esc_html_e('— Unassigned —', 'vgbm-property-manager'); ?></option>
                <?php foreach ($maintenance_users as $u): ?>
                    <option value="<?php echo esc_attr((string)$u->ID); ?>" <?php selected($assigned_user, $u->ID); ?>>
                        <?php echo esc_html($u->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_ticket_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_ticket_nonce'])), 'vgbm_pm_save_ticket')) {
            return;
        }

        $status = isset($_POST['vgbm_status']) ? sanitize_text_field(wp_unslash($_POST['vgbm_status'])) : 'open';
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'open';
        }
        update_post_meta($post_id, '_vgbm_status', $status);

        $assigned = isset($_POST['vgbm_assigned_user']) ? (int) $_POST['vgbm_assigned_user'] : 0;
        update_post_meta($post_id, '_vgbm_assigned_user', $assigned);
    }
}
