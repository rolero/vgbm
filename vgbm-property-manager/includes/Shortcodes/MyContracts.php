<?php
namespace VGBM\PM\Shortcodes;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\Security;

if (!defined('ABSPATH')) { exit; }

final class MyContracts {

    public function register(): void {
        add_shortcode('vgbm_my_contracts', [$this, 'render']);
    }

    public function render($atts = []): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view your contracts.', 'vgbm-property-manager') . '</p>';
        }

        if (!Security::current_user_is_vgbm_renter_or_staff()) {
            return '<p>' . esc_html__('You are not allowed to view contracts.', 'vgbm-property-manager') . '</p>';
        }

        $uid = get_current_user_id();

        // Find renter profiles linked to this WP user.
        $renter_posts = get_posts([
            'post_type' => PostTypes::CPT_RENTER,
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

        $renter_ids = [];
        foreach ($renter_posts as $p) {
            $renter_ids[] = (int) $p->ID;
        }

        // If no renter profile linked, show friendly message (except admins).
        if (empty($renter_ids) && !current_user_can('manage_options')) {
            return '<p>' . esc_html__('No renter profile is linked to your account yet.', 'vgbm-property-manager') . '</p>';
        }

        // Build meta_query: match any renter id in stored renter ids.
        $meta_or = [];
        foreach ($renter_ids as $rid) {
            $meta_or[] = [
                'key' => '_vgbm_renter_ids',
                'value' => '"' . $rid . '"',
                'compare' => 'LIKE',
            ];
        }

        // Legacy support: contracts stored WP user IDs (older versions)
        $meta_or[] = [
            'key' => '_vgbm_renter_user_ids',
            'value' => '"' . $uid . '"',
            'compare' => 'LIKE',
        ];

        $meta_query = ['relation' => 'OR'];
        foreach ($meta_or as $cond) {
            $meta_query[] = $cond;
        }

        $q = new \WP_Query([
            'post_type' => PostTypes::CPT_CONTRACT,
            'posts_per_page' => 50,
            'post_status' => ['publish', 'private', 'draft'],
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ]);

        if (!$q->have_posts()) {
            return '<p>' . esc_html__('No contracts found.', 'vgbm-property-manager') . '</p>';
        }

        ob_start();
        ?>
        <div class="vgbm-pm vgbm-pm-my-contracts">
            <ul>
                <?php while ($q->have_posts()): $q->the_post(); ?>
                    <?php
                        $contract_id = get_the_ID();

                        $unit_ids = get_post_meta($contract_id, '_vgbm_unit_ids', true);
                        if (!is_array($unit_ids) || empty($unit_ids)) {
                            $legacy = (int) get_post_meta($contract_id, '_vgbm_unit_id', true);
                            $unit_ids = $legacy ? [$legacy] : [];
                        }
                        $unit_ids = array_values(array_filter(array_map('intval', (array)$unit_ids)));

                        $unit_titles = [];
                        foreach ($unit_ids as $id) {
                            $t = get_the_title((int)$id);
                            if ($t) { $unit_titles[] = $t; }
                        }

                        $start = (string) get_post_meta($contract_id, '_vgbm_start_date', true);
                        $end = (string) get_post_meta($contract_id, '_vgbm_end_date', true);
                        $status = (string) get_post_meta($contract_id, '_vgbm_contract_status', true);

                        $doc_id = (int) get_post_meta($contract_id, '_vgbm_contract_document_id', true);
                        $doc_url = $doc_id ? add_query_arg(['vgbm_contract_doc' => $contract_id], home_url('/')) : '';
                    ?>
                    <li style="margin-bottom:1rem;">
                        <strong><?php echo esc_html(get_the_title()); ?></strong><br>
                        <small>
                            <?php if (!empty($unit_titles)): ?>
                                <?php echo esc_html__('Unit(s):', 'vgbm-property-manager') . ' ' . esc_html(implode(', ', $unit_titles)); ?> ·
                            <?php endif; ?>
                            <?php echo esc_html__('Start:', 'vgbm-property-manager') . ' ' . esc_html($start ?: '—'); ?> ·
                            <?php echo esc_html__('End:', 'vgbm-property-manager') . ' ' . esc_html($end ?: '—'); ?> ·
                            <?php echo esc_html__('Status:', 'vgbm-property-manager') . ' ' . esc_html($status ?: '—'); ?>
                        </small>

                        <?php if ($doc_url): ?>
                            <div style="margin-top:.5rem;">
                                <a href="<?php echo esc_url($doc_url); ?>" target="_blank" rel="noopener">
                                    <?php esc_html_e('View / download contract document', 'vgbm-property-manager'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (get_the_content()): ?>
                            <div style="margin-top:.5rem;">
                                <?php echo wp_kses_post(wpautop(get_the_content())); ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endwhile; wp_reset_postdata(); ?>
            </ul>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
