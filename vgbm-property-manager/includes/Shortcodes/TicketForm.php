<?php
namespace VGBM\PM\Shortcodes;

use VGBM\PM\Admin\UserProfile;
use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\Security;

if (!defined('ABSPATH')) { exit; }

final class TicketForm {

    public function register(): void {
        add_shortcode('vgbm_ticket_form', [$this, 'render']);
        add_action('init', [$this, 'handle_submit']);
    }

    public function render($atts = []): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to submit a request.', 'vgbm-property-manager') . '</p>';
        }

        if (!Security::current_user_is_vgbm_renter_or_staff()) {
            return '<p>' . esc_html__('You are not allowed to submit requests.', 'vgbm-property-manager') . '</p>';
        }

        $assigned_units = UserProfile::get_assigned_units_for_current_user();
        $units = [];
        if (!empty($assigned_units)) {
            $units = get_posts([
                'post_type' => PostTypes::CPT_UNIT,
                'post__in' => $assigned_units,
                'numberposts' => 200,
                'post_status' => ['publish', 'draft', 'private'],
                'orderby' => 'title',
                'order' => 'ASC',
            ]);
        }

        $msg = '';
        if (!empty($_GET['vgbm_ticket'])) {
            if ($_GET['vgbm_ticket'] === 'success') {
                $msg = '<div class="notice notice-success"><p>' . esc_html__('Your request has been submitted.', 'vgbm-property-manager') . '</p></div>';
            }
            if ($_GET['vgbm_ticket'] === 'error') {
                $msg = '<div class="notice notice-error"><p>' . esc_html__('Something went wrong. Please try again.', 'vgbm-property-manager') . '</p></div>';
            }
        }

        ob_start();
        ?>
        <div class="vgbm-pm vgbm-pm-ticket-form">
            <?php echo $msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <form method="post">
                <?php wp_nonce_field('vgbm_pm_ticket_submit', 'vgbm_pm_ticket_nonce'); ?>
                <p>
                    <label for="vgbm_subject"><strong><?php esc_html_e('Subject', 'vgbm-property-manager'); ?></strong></label><br>
                    <input id="vgbm_subject" name="vgbm_subject" type="text" required class="widefat">
                </p>

                <p>
                    <label for="vgbm_type"><strong><?php esc_html_e('Type', 'vgbm-property-manager'); ?></strong></label><br>
                    <select id="vgbm_type" name="vgbm_type" class="widefat">
                        <option value="issue"><?php esc_html_e('Issue', 'vgbm-property-manager'); ?></option>
                        <option value="question"><?php esc_html_e('Question', 'vgbm-property-manager'); ?></option>
                    </select>
                </p>

                <?php if (!empty($units)): ?>
                    <p>
                        <label for="vgbm_unit_id"><strong><?php esc_html_e('Unit', 'vgbm-property-manager'); ?></strong></label><br>
                        <select id="vgbm_unit_id" name="vgbm_unit_id" class="widefat">
                            <option value="0"><?php esc_html_e('— Select —', 'vgbm-property-manager'); ?></option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?php echo esc_attr((string)$u->ID); ?>"><?php echo esc_html($u->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php else: ?>
                    <p class="description">
                        <?php esc_html_e('No unit is linked to your account yet. You can still submit a request; VGBM can link your unit later.', 'vgbm-property-manager'); ?>
                    </p>
                <?php endif; ?>

                <p>
                    <label for="vgbm_description"><strong><?php esc_html_e('Description', 'vgbm-property-manager'); ?></strong></label><br>
                    <textarea id="vgbm_description" name="vgbm_description" rows="6" required class="widefat"></textarea>
                </p>

                <p>
                    <button type="submit" class="button button-primary" name="vgbm_ticket_submit" value="1">
                        <?php esc_html_e('Submit', 'vgbm-property-manager'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function handle_submit(): void {
        if (empty($_POST['vgbm_ticket_submit'])) {
            return;
        }

        if (!is_user_logged_in() || !Security::current_user_is_vgbm_renter_or_staff()) {
            wp_safe_redirect(add_query_arg('vgbm_ticket', 'error', wp_get_referer() ?: home_url('/')));
            exit;
        }

        $nonce = isset($_POST['vgbm_pm_ticket_nonce']) ? sanitize_text_field(wp_unslash($_POST['vgbm_pm_ticket_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'vgbm_pm_ticket_submit')) {
            wp_safe_redirect(add_query_arg('vgbm_ticket', 'error', wp_get_referer() ?: home_url('/')));
            exit;
        }

        $subject = isset($_POST['vgbm_subject']) ? sanitize_text_field(wp_unslash($_POST['vgbm_subject'])) : '';
        $type    = isset($_POST['vgbm_type']) ? sanitize_text_field(wp_unslash($_POST['vgbm_type'])) : 'issue';
        $desc    = isset($_POST['vgbm_description']) ? wp_kses_post(wp_unslash($_POST['vgbm_description'])) : '';
        $unit_id = isset($_POST['vgbm_unit_id']) ? (int) $_POST['vgbm_unit_id'] : 0;

        if ($subject === '' || $desc === '') {
            wp_safe_redirect(add_query_arg('vgbm_ticket', 'error', wp_get_referer() ?: home_url('/')));
            exit;
        }

        if (!in_array($type, ['issue', 'question'], true)) {
            $type = 'issue';
        }

        $post_id = wp_insert_post([
            'post_type' => PostTypes::CPT_TICKET,
            'post_title' => $subject,
            'post_content' => $desc,
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('vgbm_ticket', 'error', wp_get_referer() ?: home_url('/')));
            exit;
        }

        update_post_meta($post_id, '_vgbm_type', $type);
        update_post_meta($post_id, '_vgbm_status', 'open');
        update_post_meta($post_id, '_vgbm_unit_id', $unit_id);

        // Notify site admin (configurable later)
        $to = get_option('admin_email');
        $subject_mail = sprintf('[VGBM] New ticket: %s', $subject);
        $body = "A new ticket was submitted.

"
              . "User ID: " . get_current_user_id() . "
"
              . "Type: " . $type . "
"
              . "Unit ID: " . $unit_id . "
"
              . "Ticket ID: " . $post_id . "

"
              . "View in admin: " . admin_url('post.php?post=' . $post_id . '&action=edit');

        wp_mail($to, $subject_mail, $body);

        wp_safe_redirect(add_query_arg('vgbm_ticket', 'success', wp_get_referer() ?: home_url('/')));
        exit;
    }
}
