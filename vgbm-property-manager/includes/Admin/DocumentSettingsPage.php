<?php
namespace VGBM\PM\Admin;

use VGBM\PM\Documents\Config;

if (!defined('ABSPATH')) { exit; }

final class DocumentSettingsPage {

    public function register(): void {
        if (!is_admin()) { return; }
        add_action('admin_menu', [$this, 'add_submenu'], 45);
        add_action('admin_post_vgbm_pm_save_doc_settings', [$this, 'handle_save']);
    }

    public function add_submenu(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'vgbm_pm',
            __('Document settings', 'vgbm-property-manager'),
            __('Document settings', 'vgbm-property-manager'),
            'read',
            'vgbm_pm_documents_settings',
            [$this, 'render']
        );
    }

    public function render(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            wp_die(esc_html__('No access.', 'vgbm-property-manager'));
        }

        Config::ensure_defaults();

        $types = Config::get_types();
        $labels = Config::get_labels();

        $msg = '';
        if (!empty($_GET['vgbm_saved']) && $_GET['vgbm_saved'] === '1') {
            $msg = '<div class="notice notice-success"><p>' . esc_html__('Saved.', 'vgbm-property-manager') . '</p></div>';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Document settings', 'vgbm-property-manager') . '</h1>';
        echo $msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '<p class="description">' . esc_html__('Define document types, allowed upload formats and privacy labels. These settings control validation when uploading new document versions.', 'vgbm-property-manager') . '</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="vgbm_pm_save_doc_settings">';
        wp_nonce_field('vgbm_pm_save_doc_settings', 'vgbm_pm_doc_settings_nonce');

        echo '<h2 style="margin-top:18px;">' . esc_html__('Privacy labels', 'vgbm-property-manager') . '</h2>';
        echo '<p class="description">' . esc_html__('Labels indicate how sensitive a document is (e.g., tenant-visible vs GDPR/PII). Enforcement is applied by secure document downloads.', 'vgbm-property-manager') . '</p>';

        echo '<table class="widefat striped" id="vgbm-doc-labels">';
        echo '<thead><tr><th style="width:18%;">' . esc_html__('Key', 'vgbm-property-manager') . '</th><th style="width:45%;">' . esc_html__('Name', 'vgbm-property-manager') . '</th><th style="width:25%;">' . esc_html__('Sensitivity', 'vgbm-property-manager') . '</th><th style="width:12%;">' . esc_html__('Remove', 'vgbm-property-manager') . '</th></tr></thead><tbody>';

        $i = 0;
        foreach ($labels as $key => $lbl) {
            $name = (string)($lbl['name'] ?? '');
            $sens = (string)($lbl['sensitivity'] ?? 'internal');

            echo '<tr>';
            echo '<td><input class="widefat" name="labels[' . esc_attr((string)$i) . '][key]" value="' . esc_attr((string)$key) . '" /></td>';
            echo '<td><input class="widefat" name="labels[' . esc_attr((string)$i) . '][name]" value="' . esc_attr($name) . '" /></td>';
            echo '<td><select class="widefat" name="labels[' . esc_attr((string)$i) . '][sensitivity]">';
            foreach (['public','tenant','internal','confidential','sensitive'] as $s) {
                echo '<option value="' . esc_attr($s) . '" ' . selected($sens, $s, false) . '>' . esc_html($s) . '</option>';
            }
            echo '</select></td>';
            echo '<td style="text-align:center;"><button type="button" class="button vgbm-remove-row">&times;</button></td>';
            echo '</tr>';
            $i++;
        }

        echo '</tbody></table>';
        echo '<p style="margin-top:8px;"><button type="button" class="button" id="vgbm-add-label">' . esc_html__('Add label', 'vgbm-property-manager') . '</button></p>';

        echo '<hr style="margin:22px 0;">';

        echo '<h2>' . esc_html__('Document types', 'vgbm-property-manager') . '</h2>';
        echo '<p class="description">' . esc_html__('For each document type, define allowed file extensions (comma separated) and the default privacy label.', 'vgbm-property-manager') . '</p>';

        echo '<table class="widefat striped" id="vgbm-doc-types">';
        echo '<thead><tr><th style="width:16%;">' . esc_html__('Key', 'vgbm-property-manager') . '</th><th style="width:30%;">' . esc_html__('Name', 'vgbm-property-manager') . '</th><th style="width:30%;">' . esc_html__('Allowed extensions', 'vgbm-property-manager') . '</th><th style="width:16%;">' . esc_html__('Default label', 'vgbm-property-manager') . '</th><th style="width:8%;">' . esc_html__('Enabled', 'vgbm-property-manager') . '</th></tr></thead><tbody>';

        $j = 0;
        foreach ($types as $key => $t) {
            $name = (string)($t['name'] ?? '');
            $ext = $t['allowed_extensions'] ?? [];
            if (is_array($ext)) {
                $ext = implode(', ', array_map('strval', $ext));
            } else {
                $ext = (string)$ext;
            }
            $def_lbl = (string)($t['default_label'] ?? 'internal');
            $enabled = (int)($t['enabled'] ?? 1);

            echo '<tr>';
            echo '<td><input class="widefat" name="types[' . esc_attr((string)$j) . '][key]" value="' . esc_attr((string)$key) . '" /></td>';
            echo '<td><input class="widefat" name="types[' . esc_attr((string)$j) . '][name]" value="' . esc_attr($name) . '" /></td>';
            echo '<td><input class="widefat" name="types[' . esc_attr((string)$j) . '][ext]" value="' . esc_attr($ext) . '" placeholder="pdf, jpg, png" /></td>';

            echo '<td><select class="widefat" name="types[' . esc_attr((string)$j) . '][default_label]">';
            foreach ($labels as $lk => $lv) {
                $ln = (string)($lv['name'] ?? $lk);
                echo '<option value="' . esc_attr((string)$lk) . '" ' . selected($def_lbl, $lk, false) . '>' . esc_html($ln) . '</option>';
            }
            echo '</select></td>';

            echo '<td style="text-align:center;"><input type="checkbox" name="types[' . esc_attr((string)$j) . '][enabled]" value="1" ' . checked(1, $enabled, false) . '></td>';
            echo '</tr>';
            $j++;
        }

        echo '</tbody></table>';
        echo '<p style="margin-top:8px;"><button type="button" class="button" id="vgbm-add-type">' . esc_html__('Add type', 'vgbm-property-manager') . '</button></p>';

        echo '<p style="margin-top:16px;"><button type="submit" class="button button-primary">' . esc_html__('Save settings', 'vgbm-property-manager') . '</button></p>';

        echo '</form>';

        // Lightweight JS for add/remove rows.
        echo '<script>
            (function(){
                function addRow(tableId, html){
                    var t = document.getElementById(tableId);
                    if(!t) return;
                    var tb = t.querySelector("tbody");
                    if(!tb) return;
                    tb.insertAdjacentHTML("beforeend", html);
                }
                document.addEventListener("click", function(e){
                    if(e.target && e.target.classList.contains("vgbm-remove-row")){
                        e.preventDefault();
                        var tr = e.target.closest("tr");
                        if(tr) tr.remove();
                    }
                });

                var addLabel = document.getElementById("vgbm-add-label");
                if(addLabel){
                    addLabel.addEventListener("click", function(e){
                        e.preventDefault();
                        var idx = document.querySelectorAll("#vgbm-doc-labels tbody tr").length;
                        addRow("vgbm-doc-labels",
                            "<tr>" +
                            "<td><input class=\"widefat\" name=\"labels["+idx+"][key]\" value=\"\"></td>" +
                            "<td><input class=\"widefat\" name=\"labels["+idx+"][name]\" value=\"\"></td>" +
                            "<td><select class=\"widefat\" name=\"labels["+idx+"][sensitivity]\">" +
                                "<option value=\"public\">public</option>" +
                                "<option value=\"tenant\">tenant</option>" +
                                "<option value=\"internal\" selected>internal</option>" +
                                "<option value=\"confidential\">confidential</option>" +
                                "<option value=\"sensitive\">sensitive</option>" +
                            "</select></td>" +
                            "<td style=\"text-align:center;\"><button type=\"button\" class=\"button vgbm-remove-row\">&times;</button></td>" +
                            "</tr>"
                        );
                    });
                }

                var addType = document.getElementById("vgbm-add-type");
                if(addType){
                    addType.addEventListener("click", function(e){
                        e.preventDefault();
                        var idx = document.querySelectorAll("#vgbm-doc-types tbody tr").length;
                        var labelOptions = "";
                        var selects = document.querySelectorAll("#vgbm-doc-types select");
                        if(selects.length){
                            labelOptions = selects[0].innerHTML;
                        } else {
                            labelOptions = "<option value=\"internal\">Internal</option>";
                        }
                        addRow("vgbm-doc-types",
                            "<tr>" +
                            "<td><input class=\"widefat\" name=\"types["+idx+"][key]\" value=\"\"></td>" +
                            "<td><input class=\"widefat\" name=\"types["+idx+"][name]\" value=\"\"></td>" +
                            "<td><input class=\"widefat\" name=\"types["+idx+"][ext]\" value=\"pdf\" placeholder=\"pdf, jpg, png\"></td>" +
                            "<td><select class=\"widefat\" name=\"types["+idx+"][default_label]\">" + labelOptions + "</select></td>" +
                            "<td style=\"text-align:center;\"><input type=\"checkbox\" name=\"types["+idx+"][enabled]\" value=\"1\" checked></td>" +
                            "</tr>"
                        );
                    });
                }
            })();
        </script>';

        echo '</div>';
    }

    public function handle_save(): void {
        if (!current_user_can('vgbm_manage') && !current_user_can('manage_options')) {
            wp_die(esc_html__('No access.', 'vgbm-property-manager'));
        }

        if (empty($_POST['vgbm_pm_doc_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vgbm_pm_doc_settings_nonce'])), 'vgbm_pm_save_doc_settings')) {
            wp_die(esc_html__('Invalid nonce.', 'vgbm-property-manager'));
        }

        $raw_labels = isset($_POST['labels']) ? (array) $_POST['labels'] : [];
        $labels = [];
        foreach ($raw_labels as $row) {
            if (!is_array($row)) { continue; }
            $k = sanitize_key((string)($row['key'] ?? ''));
            if ($k === '') { continue; }
            $labels[$k] = [
                'name' => sanitize_text_field((string)($row['name'] ?? '')),
                'sensitivity' => sanitize_key((string)($row['sensitivity'] ?? 'internal')),
            ];
        }
        $labels = Config::sanitize_labels($labels);

        $raw_types = isset($_POST['types']) ? (array) $_POST['types'] : [];
        $types = [];
        foreach ($raw_types as $row) {
            if (!is_array($row)) { continue; }
            $k = sanitize_key((string)($row['key'] ?? ''));
            if ($k === '') { continue; }
            $types[$k] = [
                'name' => sanitize_text_field((string)($row['name'] ?? '')),
                'allowed_extensions' => (string)($row['ext'] ?? ''),
                'default_label' => sanitize_key((string)($row['default_label'] ?? 'internal')),
                'enabled' => !empty($row['enabled']) ? 1 : 0,
            ];
        }
        $types = Config::sanitize_types($types);

        // Ensure defaults exist for any referenced default labels
        foreach ($types as $k => $t) {
            $dl = (string)($t['default_label'] ?? 'internal');
            if (!isset($labels[$dl])) {
                $types[$k]['default_label'] = 'internal';
            }
        }

        update_option(Config::OPT_LABELS, $labels);
        update_option(Config::OPT_TYPES, $types);

        wp_safe_redirect(add_query_arg(['page' => 'vgbm_pm_documents_settings', 'vgbm_saved' => '1'], admin_url('admin.php')));
        exit;
    }
}
