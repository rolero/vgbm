<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class PropertyMetaBox {

    public function render(\WP_Post $post): void {
        wp_nonce_field('vgbm_pm_save_property', 'vgbm_pm_property_nonce');

        $portfolio_id = (int) get_post_meta($post->ID, '_vgbm_portfolio_id', true);

        $street = get_post_meta($post->ID, '_vgbm_street', true);
        $postal = get_post_meta($post->ID, '_vgbm_postal', true);
        $city   = get_post_meta($post->ID, '_vgbm_city', true);
        $country= get_post_meta($post->ID, '_vgbm_country', true) ?: 'NL';

        $portfolios = get_posts([
            'post_type' => PostTypes::CPT_PORTFOLIO,
            'numberposts' => 400,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ?>
        <p>
            <label><strong><?php esc_html_e('Portfolio (client/account)', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_portfolio_id" class="widefat">
                <option value="0"><?php esc_html_e('— Select —', 'vgbm-property-manager'); ?></option>
                <?php foreach ($portfolios as $pf): ?>
                    <?php
                        $label = (string) $pf->post_title;
                        if (trim($label) === '') {
                            $company = (string) get_post_meta($pf->ID, '_vgbm_company', true);
                            $label = trim($company) !== '' ? $company : __('(untitled portfolio)', 'vgbm-property-manager');
                        }
                    ?>
                    <option value="<?php echo esc_attr((string)$pf->ID); ?>" <?php selected($portfolio_id, $pf->ID); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description">
                <?php esc_html_e('Use portfolios to separate customer portfolios that VGBM manages.', 'vgbm-property-manager'); ?>
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
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_property_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_property_nonce'])), 'vgbm_pm_save_property')) {
            return;
        }

        $portfolio_id = isset($_POST['vgbm_portfolio_id']) ? (int) $_POST['vgbm_portfolio_id'] : 0;
        update_post_meta($post_id, '_vgbm_portfolio_id', $portfolio_id);

        $fields = [
            '_vgbm_street'  => 'vgbm_street',
            '_vgbm_postal'  => 'vgbm_postal',
            '_vgbm_city'    => 'vgbm_city',
            '_vgbm_country' => 'vgbm_country',
        ];

        foreach ($fields as $meta_key => $post_key) {
            $val = isset($_POST[$post_key]) ? sanitize_text_field(wp_unslash($_POST[$post_key])) : '';
            update_post_meta($post_id, $meta_key, $val);
        }
    }
}
