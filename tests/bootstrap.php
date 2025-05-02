<?php
/**
 * PHPUnit bootstrap file.
 */

// Define constants needed by WordPress tests
define('ABSPATH', true);
define('WP_DEBUG', true);
define('WPINC', 'wp-includes');
define('RPG_SUITE_TESTING', true);
define('RPG_SUITE_PLUGIN_DIR', dirname(__DIR__) . '/');
define('RPG_SUITE_PLUGIN_URL', 'http://example.org/wp-content/plugins/rpg-suite/');
define('RPG_SUITE_PLUGIN_BASENAME', 'rpg-suite/rpg-suite.php');
define('RPG_SUITE_VERSION', '0.1.0');

// Include Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Include WordPress test environment functions
function add_action() {
    // Mock function
}

function add_filter() {
    // Mock function
}

function apply_filters() {
    // Mock function
    $args = func_get_args();
    return isset($args[1]) ? $args[1] : null;
}

function do_action() {
    // Mock function
}

function register_activation_hook() {
    // Mock function
}

function register_deactivation_hook() {
    // Mock function
}

// Common WordPress functions used in the plugin
function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function plugin_dir_url($file) {
    return 'http://example.org/wp-content/plugins/' . dirname(plugin_basename($file)) . '/';
}

function plugin_basename($file) {
    return basename(dirname($file)) . '/' . basename($file);
}

function wp_die($message) {
    throw new Exception($message);
}

function deactivate_plugins($plugins) {
    // Mock function
}

function get_option($option, $default = false) {
    $options = [
        'rpg_suite_active_subsystems' => [
            'health' => true,
            'geo' => true,
            'dice' => true,
            'inventory' => true,
            'combat' => true,
            'quest' => false,
        ],
    ];
    
    return isset($options[$option]) ? $options[$option] : $default;
}

function add_option($option, $value) {
    // Mock function
}

function update_option($option, $value) {
    // Mock function
}

function get_role($role) {
    return new stdClass();
}

// Include the main plugin file
require_once dirname(__DIR__) . '/rpg-suite.php';