<?php
/**
 * Plugin Name:       VGBM Property Manager (MVP)
 * Description:       Basic property/unit management + renter ticket intake for VGBM. Secure, modern structure, and extendable.
 * Version:           0.6.1
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            VGBM
 * Text Domain:       vgbm-property-manager
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) { exit; }

define('VGBM_PM_VERSION', '0.6.1');
define('VGBM_PM_FILE', __FILE__);
define('VGBM_PM_DIR', plugin_dir_path(__FILE__));
define('VGBM_PM_URL', plugin_dir_url(__FILE__));

require_once VGBM_PM_DIR . 'includes/Autoloader.php';

add_action('plugins_loaded', function () {
    \VGBM\PM\Plugin::instance()->boot();
});

register_activation_hook(__FILE__, function () {
    \VGBM\PM\Plugin::instance()->activate();
});

register_deactivation_hook(__FILE__, function () {
    \VGBM\PM\Plugin::instance()->deactivate();
});
