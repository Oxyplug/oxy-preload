<?php
/**
 * PHPUnit bootstrap for the Oxyplug Preload unit tests.
 *
 * These are pure unit tests for the plugin's .htaccess generation/stripping
 * logic — the riskiest code — so they do NOT load the full WordPress test
 * suite. Instead we define just enough constants and no-op WordPress functions
 * for oxy-preload.php to be included and its class instantiated, then exercise
 * the target methods directly via reflection.
 */

declare(strict_types=1);

// The plugin file exits unless this is defined; point it at a temp dir so any
// incidental ABSPATH-based path building stays harmless during tests.
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}

// Functions called in OxyPreload::__construct() when the file is loaded.
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}
if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {}
}
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {}
}
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {}
}

require_once __DIR__ . '/../oxy-preload.php';
