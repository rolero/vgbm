<?php
namespace VGBM\PM\Shortcodes;

use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Utils\Security;

if (!defined('ABSPATH')) { exit; }

final class MyTickets {

    public function register(): void {
        add_shortcode('vgbm_my_tickets', [$this, 'render']);
    }

    public function render($atts = []): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view your tickets.', 'vgbm-property-manager') . '</p>';
        }

        if (!Security::current_user_is_vgbm_renter_or_staff()) {
            return '<p>' . esc_html__('You are not allowed to view tickets.', 'vgbm-property-manager') . '</p>';
        }

        $q = new \WP_Query([
            'post_type' => PostTypes::CPT_TICKET,
            'author' => get_current_user_id(),
            'posts_per_page' => 50,
            'post_status' => ['private', 'publish', 'pending', 'draft'],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (!$q->have_posts()) {
            return '<p>' . esc_html__('No tickets yet.', 'vgbm-property-manager') . '</p>';
        }

        ob_start();
        ?>
        <div class="vgbm-pm vgbm-pm-my-tickets">
            <ul>
                <?php while ($q->have_posts()): $q->the_post(); ?>
                    <?php
                        $status = get_post_meta(get_the_ID(), '_vgbm_status', true) ?: 'open';
                        $type   = get_post_meta(get_the_ID(), '_vgbm_type', true) ?: '';
                        $unit_id = (int) get_post_meta(get_the_ID(), '_vgbm_unit_id', true);
                    ?>
                    <li style="margin-bottom:1rem;">
                        <strong><?php echo esc_html(get_the_title()); ?></strong><br>
                        <small>
                            <?php echo esc_html(get_the_date()); ?> ·
                            <?php echo esc_html($status); ?>
                            <?php if ($type): ?> · <?php echo esc_html($type); ?><?php endif; ?>
                            <?php if ($unit_id): ?> · <?php echo esc_html__('Unit:', 'vgbm-property-manager') . ' ' . esc_html(get_the_title($unit_id)); ?><?php endif; ?>
                        </small>
                        <div style="margin-top:.5rem;">
                            <?php echo wp_kses_post(wpautop(get_the_content())); ?>
                        </div>
                    </li>
                <?php endwhile; wp_reset_postdata(); ?>
            </ul>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
