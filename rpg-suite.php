<?php
/**
 * Plugin Name: RPG Suite
 * Plugin URI: https://github.com/YOUR-USERNAME/RPG-Suite
 * Description: A comprehensive WordPress plugin package for creating RPG/tabletop-style adventure games with subsystems including health, geolocation, dice, inventory, combat, and quests.
 * Version: 0.1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: rpg-suite
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 *
 * @package RPG_Suite
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('RPG_SUITE_VERSION', '0.1.0');
define('RPG_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RPG_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RPG_SUITE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-autoloader.php';

/**
 * The code that runs during plugin activation.
 */
function activate_rpg_suite() {
    // Activation tasks
    require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-activator.php';
    RPG\Suite\Includes\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_rpg_suite() {
    // Deactivation tasks
    require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-deactivator.php';
    RPG\Suite\Includes\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_rpg_suite');
register_deactivation_hook(__FILE__, 'deactivate_rpg_suite');

/**
 * Begins execution of the plugin.
 */
function run_rpg_suite() {
    // Initialize the plugin
    require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-rpg-suite.php';
    $plugin = new RPG\Suite\Includes\RPG_Suite();
    $plugin->run();
}

// Run the plugin
run_rpg_suite();