<?php
namespace VGBM\PM\Admin\MetaBoxes;

if (!defined('ABSPATH')) { exit; }

final class PortfolioMetaBox {

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_portfolio', 'vgbm_pm_portfolio_nonce');

        $company = get_post_meta($post->ID, '_vgbm_company', true);
        $kvk     = get_post_meta($post->ID, '_vgbm_kvk', true);
        $contact = get_post_meta($post->ID, '_vgbm_contact', true);
        $email   = get_post_meta($post->ID, '_vgbm_email', true);
        $phone   = get_post_meta($post->ID, '_vgbm_phone', true);

        ?>
        <p>
            <label><strong><?php esc_html_e('Company name', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="text" class="widefat" name="vgbm_company" value="<?php echo esc_attr($company); ?>">
        </p>
        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('KvK', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_kvk" value="<?php echo esc_attr($kvk); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Contact person', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_contact" value="<?php echo esc_attr($contact); ?>">
            </span>
        </p>
        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Email', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="email" class="widefat" name="vgbm_email" value="<?php echo esc_attr($email); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Phone', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_phone" value="<?php echo esc_attr($phone); ?>">
            </span>
        </p>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_portfolio_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_portfolio_nonce'])), 'vgbm_pm_save_portfolio')) {
            return;
        }

        $fields = [
            '_vgbm_company' => 'vgbm_company',
            '_vgbm_kvk'     => 'vgbm_kvk',
            '_vgbm_contact' => 'vgbm_contact',
            '_vgbm_email'   => 'vgbm_email',
            '_vgbm_phone'   => 'vgbm_phone',
        ];

        foreach ($fields as $meta_key => $post_key) {
            $val = isset($_POST[$post_key]) ? sanitize_text_field(wp_unslash($_POST[$post_key])) : '';
            update_post_meta($post_id, $meta_key, $val);
        }
    }
}
