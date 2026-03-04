<?php
namespace VGBM\PM\Admin\MetaBoxes;

use VGBM\PM\Documents\Config;
use VGBM\PM\PostTypes\PostTypes;

if (!defined('ABSPATH')) { exit; }

final class DocumentMetaBox {

    public function render(\WP_Post $post): void {
        Config::ensure_defaults();

        wp_nonce_field('vgbm_pm_save_document', 'vgbm_pm_document_nonce');

        $type_key = (string) get_post_meta($post->ID, '_vgbm_doc_type', true);
        $label_key = (string) get_post_meta($post->ID, '_vgbm_doc_label', true);

        $types = Config::get_types();
        $labels = Config::get_labels();

        if ($type_key === '' && !empty($types)) {
            $type_key = array_key_first($types);
        }
        if ($label_key === '' && $type_key) {
            $label_key = Config::default_label_for_type($type_key);
        }
        if ($label_key === '') { $label_key = 'internal'; }

        $doc_number = (string) get_post_meta($post->ID, '_vgbm_doc_number', true);
        $issue_date = (string) get_post_meta($post->ID, '_vgbm_issue_date', true);
        $expiry_date = (string) get_post_meta($post->ID, '_vgbm_expiry_date', true);
        $issuer = (string) get_post_meta($post->ID, '_vgbm_issuer', true);

        $notes = (string) get_post_meta($post->ID, '_vgbm_notes', true);

        $current_att = (int) get_post_meta($post->ID, '_vgbm_current_attachment_id', true);
        $current_label = $current_att ? (get_the_title($current_att) ?: ('#' . $current_att)) : '';

        $allowed_ext = $type_key ? Config::allowed_extensions_for_type($type_key) : [];
        $allowed_ext_str = $allowed_ext ? implode(', ', $allowed_ext) : '';

        $linked = get_post_meta($post->ID, '_vgbm_linked_post_ids', true);
        if (!is_array($linked)) { $linked = []; }
        $linked = array_values(array_filter(array_map('intval', $linked), fn($v) => $v > 0));

        ?>
        <p>
            <label><strong><?php esc_html_e('Document type', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_doc_type" class="widefat">
                <?php foreach ($types as $k => $t): ?>
                    <?php if (!empty($t['enabled']) || !isset($t['enabled'])): ?>
                        <option value="<?php echo esc_attr((string)$k); ?>" <?php selected($type_key, (string)$k); ?>>
                            <?php echo esc_html((string)($t['name'] ?? $k)); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label><strong><?php esc_html_e('Privacy label', 'vgbm-property-manager'); ?></strong></label><br>
            <select name="vgbm_doc_label" class="widefat">
                <?php foreach ($labels as $k => $l): ?>
                    <option value="<?php echo esc_attr((string)$k); ?>" <?php selected($label_key, (string)$k); ?>>
                        <?php echo esc_html((string)($l['name'] ?? $k)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('File & versions', 'vgbm-property-manager'); ?></h3>
        <p class="description">
            <?php
                echo esc_html__('Upload/select a file. When you replace the file, the old version remains available in the history below.', 'vgbm-property-manager');
                if ($allowed_ext_str) {
                    echo ' ' . esc_html(sprintf(__('Allowed formats for this type: %s', 'vgbm-property-manager'), $allowed_ext_str));
                }
            ?>
        </p>

        <p>
            <input type="hidden" id="vgbm_doc_attachment_id" name="vgbm_doc_attachment_id" value="<?php echo esc_attr((string)$current_att); ?>">
            <button type="button" class="button button-primary" id="vgbm_doc_file_pick"><?php esc_html_e('Select / Upload file', 'vgbm-property-manager'); ?></button>
            <button type="button" class="button" id="vgbm_doc_file_clear" style="margin-left:6px;"><?php esc_html_e('Clear', 'vgbm-property-manager'); ?></button>
            <span id="vgbm_doc_file_label" style="margin-left:10px;"><?php echo $current_att ? esc_html($current_label) : esc_html__('No file selected', 'vgbm-property-manager'); ?></span>
        </p>

        <p>
            <label><strong><?php esc_html_e('Version note (optional)', 'vgbm-property-manager'); ?></strong></label><br>
            <input type="text" class="widefat" name="vgbm_version_note" value="" placeholder="<?php esc_attr_e('e.g. Updated ID (new expiry date)', 'vgbm-property-manager'); ?>">
        </p>

        <?php
            $download_url = $current_att ? add_query_arg(['vgbm_doc_file' => $post->ID], home_url('/')) : '';
        ?>
        <?php if ($download_url): ?>
            <p><a class="button button-secondary" href="<?php echo esc_url($download_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('View / download (secure)', 'vgbm-property-manager'); ?></a></p>
        <?php endif; ?>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Metadata', 'vgbm-property-manager'); ?></h3>
        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Document number', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_doc_number" value="<?php echo esc_attr($doc_number); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Issuer', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="text" class="widefat" name="vgbm_issuer" value="<?php echo esc_attr($issuer); ?>">
            </span>
        </p>

        <p style="display:flex; gap:10px;">
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Issue date', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_issue_date" value="<?php echo esc_attr($issue_date); ?>">
            </span>
            <span style="flex:1;">
                <label><strong><?php esc_html_e('Expiry date', 'vgbm-property-manager'); ?></strong></label><br>
                <input type="date" class="widefat" name="vgbm_expiry_date" value="<?php echo esc_attr($expiry_date); ?>">
            </span>
        </p>

        <p>
            <label><strong><?php esc_html_e('Notes', 'vgbm-property-manager'); ?></strong></label><br>
            <textarea class="widefat" name="vgbm_notes" rows="3"><?php echo esc_textarea($notes); ?></textarea>
        </p>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Linked records', 'vgbm-property-manager'); ?></h3>
        <?php if (empty($linked)): ?>
            <p><?php esc_html_e('No linked records yet. Tip: create documents from a Contract, Renter, Property, Unit, Portfolio, Utility or Meter reading record.', 'vgbm-property-manager'); ?></p>
        <?php else: ?>
            <ul>
                <?php foreach ($linked as $pid): ?>
                    <?php
                        $p = get_post($pid);
                        if (!$p) { continue; }
                        $url = admin_url('post.php?post=' . (int)$pid . '&action=edit');
                    ?>
                    <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($p->post_title); ?></a> <small>(<?php echo esc_html($p->post_type); ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <hr>

        <h3 style="margin-top:10px;"><?php esc_html_e('Version history', 'vgbm-property-manager'); ?></h3>
        <?php
            $attachments = get_posts([
                'post_type' => 'attachment',
                'post_parent' => $post->ID,
                'post_status' => 'inherit',
                'numberposts' => 50,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
        ?>
        <?php if (empty($attachments)): ?>
            <p><?php esc_html_e('No versions yet.', 'vgbm-property-manager'); ?></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('File', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('Note', 'vgbm-property-manager'); ?></th>
                        <th><?php esc_html_e('Current', 'vgbm-property-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $a): ?>
                        <?php
                            $is_current = ((int)$a->ID === (int)$current_att);
                            $note = (string) get_post_meta((int)$a->ID, '_vgbm_version_note', true);
                            $link = wp_get_attachment_url((int)$a->ID);
                        ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($a->post_date_gmt ?: $a->post_date))); ?></td>
                            <td>
                                <?php if ($link): ?>
                                    <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener"><?php echo esc_html($a->post_title ?: ('#' . $a->ID)); ?></a>
                                <?php else: ?>
                                    <?php echo esc_html($a->post_title ?: ('#' . $a->ID)); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $note ? esc_html($note) : '—'; ?></td>
                            <td><?php echo $is_current ? '✓' : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('Note: history links point to the underlying file URL. Secure download applies to the current version via the button above.', 'vgbm-property-manager'); ?></p>
        <?php endif; ?>
        <?php
    }

    public function save(int $post_id): void {
        if (empty($_POST['vgbm_pm_document_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_document_nonce'])), 'vgbm_pm_save_document')) {
            return;
        }

        Config::ensure_defaults();

        $types = Config::get_types();
        $labels = Config::get_labels();

        $type_key = isset($_POST['vgbm_doc_type']) ? sanitize_key(wp_unslash($_POST['vgbm_doc_type'])) : '';
        if ($type_key === '' || !isset($types[$type_key])) {
            $type_key = !empty($types) ? (string) array_key_first($types) : 'other';
        }
        update_post_meta($post_id, '_vgbm_doc_type', $type_key);

        $label_key = isset($_POST['vgbm_doc_label']) ? sanitize_key(wp_unslash($_POST['vgbm_doc_label'])) : '';
        if ($label_key === '' || !isset($labels[$label_key])) {
            $label_key = Config::default_label_for_type($type_key);
            if (!isset($labels[$label_key])) { $label_key = 'internal'; }
        }
        update_post_meta($post_id, '_vgbm_doc_label', $label_key);

        update_post_meta($post_id, '_vgbm_doc_number', isset($_POST['vgbm_doc_number']) ? sanitize_text_field(wp_unslash($_POST['vgbm_doc_number'])) : '');
        update_post_meta($post_id, '_vgbm_issuer', isset($_POST['vgbm_issuer']) ? sanitize_text_field(wp_unslash($_POST['vgbm_issuer'])) : '');
        update_post_meta($post_id, '_vgbm_issue_date', isset($_POST['vgbm_issue_date']) ? sanitize_text_field(wp_unslash($_POST['vgbm_issue_date'])) : '');
        update_post_meta($post_id, '_vgbm_expiry_date', isset($_POST['vgbm_expiry_date']) ? sanitize_text_field(wp_unslash($_POST['vgbm_expiry_date'])) : '');
        update_post_meta($post_id, '_vgbm_notes', isset($_POST['vgbm_notes']) ? sanitize_textarea_field(wp_unslash($_POST['vgbm_notes'])) : '');

        // Link document to a record if created via "Add document" button.
        if (!empty($_GET['vgbm_link_post_id'])) {
            $link_post_id = (int) $_GET['vgbm_link_post_id'];
            if ($link_post_id > 0) {
                $linked = get_post_meta($post_id, '_vgbm_linked_post_ids', true);
                if (!is_array($linked)) { $linked = []; }
                $linked[] = $link_post_id;
                $linked = array_values(array_unique(array_filter(array_map('intval', $linked), fn($v) => $v > 0)));
                update_post_meta($post_id, '_vgbm_linked_post_ids', $linked);

                $doc_ids = get_post_meta($link_post_id, '_vgbm_document_ids', true);
                if (!is_array($doc_ids)) { $doc_ids = []; }
                $doc_ids[] = $post_id;
                $doc_ids = array_values(array_unique(array_filter(array_map('intval', $doc_ids), fn($v) => $v > 0)));
                update_post_meta($link_post_id, '_vgbm_document_ids', $doc_ids);
            }
        }

        // File (current attachment) + versioning
        $new_att = isset($_POST['vgbm_doc_attachment_id']) ? (int) $_POST['vgbm_doc_attachment_id'] : 0;
        $cur_att = (int) get_post_meta($post_id, '_vgbm_current_attachment_id', true);

        if ($new_att <= 0) {
            update_post_meta($post_id, '_vgbm_current_attachment_id', 0);
            return;
        }

        if ($new_att !== $cur_att) {
            $allowed = Config::allowed_extensions_for_type($type_key);

            $file = get_attached_file($new_att);
            $ft = $file ? wp_check_filetype($file) : ['ext' => ''];
            $ext = strtolower((string)($ft['ext'] ?? ''));

            if (!empty($allowed) && $ext && !in_array($ext, $allowed, true)) {
                // Invalid format; keep old current
                set_transient('vgbm_pm_doc_upload_error_' . get_current_user_id(), sprintf(__('File extension .%s is not allowed for this document type.', 'vgbm-property-manager'), $ext), 20);
                return;
            }

            // Ensure attachment is a child of this document for version history
            wp_update_post([
                'ID' => $new_att,
                'post_parent' => $post_id,
            ]);

            update_post_meta($post_id, '_vgbm_current_attachment_id', $new_att);
            update_post_meta($post_id, '_vgbm_current_version_at', time());
            update_post_meta($post_id, '_vgbm_current_version_by', get_current_user_id());

            $note = isset($_POST['vgbm_version_note']) ? sanitize_text_field(wp_unslash($_POST['vgbm_version_note'])) : '';
            if ($note !== '') {
                update_post_meta($new_att, '_vgbm_version_note', $note);
            }
        }
    }
}
