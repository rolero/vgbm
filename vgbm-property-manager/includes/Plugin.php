<?php
namespace VGBM\PM;

use VGBM\PM\Admin\Admin;
use VGBM\PM\Admin\Assets;
use VGBM\PM\Admin\BillingPage;
use VGBM\PM\Admin\DocumentSettingsPage;
use VGBM\PM\Admin\DocumentNotices;
use VGBM\PM\Admin\DocumentLinksAjax;
use VGBM\PM\Admin\IndexationPage;
use VGBM\PM\Admin\ListTableFix;
use VGBM\PM\Admin\Menu;
use VGBM\PM\Admin\TitleSync;
use VGBM\PM\Admin\UserProfile;
use VGBM\PM\Admin\Validation;
use VGBM\PM\DB\Schema;
use VGBM\PM\Documents\Config;
use VGBM\PM\Frontend\ContractDownload;
use VGBM\PM\Frontend\DocumentDownload;
use VGBM\PM\PostTypes\PostTypes;
use VGBM\PM\Roles\Roles;
use VGBM\PM\Shortcodes\MyContracts;
use VGBM\PM\Shortcodes\MyTickets;
use VGBM\PM\Shortcodes\TicketForm;

if (!defined('ABSPATH')) { exit; }

final class Plugin {

    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new Plugin();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function boot(): void {
        // i18n
        add_action('init', function () {
            load_plugin_textdomain('vgbm-property-manager', false, dirname(plugin_basename(VGBM_PM_FILE)) . '/languages');
        });

        // Ensure DB schema exists / upgrades
        if (get_option('vgbm_pm_schema_version') !== '0.4.1') {
            Schema::create_tables();
        }

        // Ensure document defaults exist
        Config::ensure_defaults();

        // Allow additional upload mimes based on document settings (admins/managers only)
        add_filter('upload_mimes', [Config::class, 'filter_upload_mimes']);

        // Migration: allocation post type slug must be <= 20 chars (WP requirement).
        $this->migrate_allocation_post_type();

        // Core
        (new PostTypes())->register();
        (new Roles())->register();
        (new Admin())->register();
        (new Assets())->register();
        (new ListTableFix())->register();
        (new Menu())->register();
        (new IndexationPage())->register();
        (new BillingPage())->register();
        (new DocumentSettingsPage())->register();
        (new DocumentNotices())->register();
        (new DocumentLinksAjax())->register();
        (new TitleSync())->register();
        (new Validation())->register();
        (new UserProfile())->register();

        // Front-end
        (new TicketForm())->register();
        (new MyTickets())->register();
        (new MyContracts())->register();
        (new ContractDownload())->register();
        (new DocumentDownload())->register();
    }

    public function activate(): void {
        // Ensure post types exist before flushing rules.
        $pts = new PostTypes();
        $pts->register_post_types();

        (new Roles())->register_roles_and_caps();

        // Ensure defaults
        Config::ensure_defaults();

        // Create/upgrade DB tables for billing
        Schema::create_tables();

        flush_rewrite_rules();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }

    private function migrate_allocation_post_type(): void {
        // Old slug exceeded 20 chars: vgbm_utility_allocation
        $done = get_option('vgbm_pm_migration_053_allocation_slug');
        if ($done) { return; }

        global $wpdb;
        $old = 'vgbm_utility_allocation';
        $new = PostTypes::CPT_ALLOCATION;

        // Only migrate if there are posts with old type
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type=%s", $old));
        if ($count > 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_type=%s WHERE post_type=%s", $new, $old));
        }

        update_option('vgbm_pm_migration_053_allocation_slug', 1);
    }
}
