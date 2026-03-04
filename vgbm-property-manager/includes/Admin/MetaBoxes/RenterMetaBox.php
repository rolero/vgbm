<?php
namespace VGBM\PM\Admin\MetaBoxes;

if (!defined('ABSPATH')) { exit; }

final class RenterMetaBox {

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_renter', 'vgbm_pm_renter_nonce');

        $email = (string) get_post_meta($post->ID, '_vgbm_email', true);
        $phone = (string) get_post_meta($post->ID, '_vgbm_phone', true);
        $dob   = (string) get_post_meta($post->ID, '_vgbm_dob', true);

        $street = (string) get_post_meta($post->ID, '_vgbm_street', true);
        $postal = (string) get_post_meta($post->ID, '_vgbm_postal', true);
        $city   = (string) get_post_meta($post->ID, '_vgbm_city', true);
        $country= (string) get_post_meta($post->ID, '_vgbm_country', true);
        if ($country === '') { $country = 'NL'; }

        // Optional portal link to WP user
        $linked_user_id = (int) get_post_meta($post->ID, '_vgbm_linked_user_id', true);

        $users = get_users([
            'number' => 500,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);

        ?>
        <p>
            <label><strong><?php esc_html_e('Email', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="email" class="widefat" name="vgbm_email" value="<?php echo esc_attr($email); ?>">
        </p>
        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Phone', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_phone" value="<?php echo esc_attr($phone); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Date of birth', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_dob" value="<?php echo esc_attr($dob); ?>">
            </span>
        </p>

        <hr>

        <p>
            <label><strong><?php esc_html_e('Street + number', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="text" class="widefat" name="vgbm_street" value="<?php echo esc_attr($street); ?>">
        </p>
        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Postal code', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_postal" value="<?php echo esc_attr($postal); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('City', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_city" value="<?php echo esc_attr($city); ?>">
            </span>
        </p>
        <p>
            <label><strong><?php esc_html_e('Country', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="text" class="widefat" name="vgbm_country" value="<?php echo esc_attr($country); ?>">
        </p>

        <hr>

        <p>
            <label><strong><?php esc_html_e('Optional: link to WordPress user (portal login)', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_linked_user_id" class="widefat">
                <option value="0"><?php esc_html_e('— Not linked —', 'vgbm-property-manager'); ?></option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo esc_attr((string)$u->ID); ?>" <?php selected($linked_user_id, $u->ID); ?>>
                        <?php echo esc_html($u->display_name . ' (' . $u->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description">
                <?php esc_html_e('Only link a user if you want this renter to log into a portal.', 'vgbm-property-manager'); ?>
            </span>
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_renter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_renter_nonce'])), 'vgbm_pm_save_renter')) {
            return;
        }

        $email = isset($_POST['vgbm_email']) ? sanitize_email(wp_unslash($_POST['vgbm_email'])) : '';
        update_post_meta($post_id, '_vgbm_email', $email);

        $phone = isset($_POST['vgbm_phone']) ? sanitize_text_field(wp_unslash($_POST['vgbm_phone'])) : '';
        update_post_meta($post_id, '_vgbm_phone', $phone);

        $dob = isset($_POST['vgbm_dob']) ? sanitize_text_field(wp_unslash($_POST['vgbm_dob'])) : '';
        update_post_meta($post_id, '_vgbm_dob', $dob);

        $street = isset($_POST['vgbm_street']) ? sanitize_text_field(wp_unslash($_POST['vgbm_street'])) : '';
        $postal = isset($_POST['vgbm_postal']) ? sanitize_text_field(wp_unslash($_POST['vgbm_postal'])) : '';
        $city   = isset($_POST['vgbm_city']) ? sanitize_text_field(wp_unslash($_POST['vgbm_city'])) : '';
        $country= isset($_POST['vgbm_country']) ? sanitize_text_field(wp_unslash($_POST['vgbm_country'])) : '';

        update_post_meta($post_id, '_vgbm_street', $street);
        update_post_meta($post_id, '_vgbm_postal', $postal);
        update_post_meta($post_id, '_vgbm_city', $city);
        update_post_meta($post_id, '_vgbm_country', $country);

        $linked_user_id = isset($_POST['vgbm_linked_user_id']) ? (int) $_POST['vgbm_linked_user_id'] : 0;
        update_post_meta($post_id, '_vgbm_linked_user_id', $linked_user_id);
    }
}
