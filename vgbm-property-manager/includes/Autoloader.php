<?php
namespace VGBM\PM;

if (!defined('ABSPATH')) { exit; }

/**
 * Very small PSR-4 style autoloader for this plugin.
 */
spl_autoload_register(function ($class) {
    if (strpos($class, 'VGBM\\PM\\') !== 0) {
        return;
    }

    $relative = substr($class, strlen('VGBM\\PM\\'));
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);

    $file = VGBM_PM_DIR . 'includes' . DIRECTORY_SEPARATOR . $relative . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});
