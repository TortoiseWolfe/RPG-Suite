<?php
/**
 * RPG-Suite
 *
 * @package           RPG_Suite
 * @author            TurtleWolfe
 * @copyright         2025 TurtleWolfe
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       RPG-Suite
 * Plugin URI:        https://github.com/TortoiseWolfe/RPG-Suite
 * Description:       A WordPress plugin for implementing RPG mechanics with BuddyPress integration
 * Version:           0.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            TurtleWolfe
 * Author URI:        https://github.com/TortoiseWolfe
 * Text Domain:       rpg-suite
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/TortoiseWolfe/RPG-Suite
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('RPG_SUITE_VERSION', '0.1.0');
define('RPG_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RPG_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function rpg_suite_activate() {
    require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-activator.php';
    RPG_Suite_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function rpg_suite_deactivate() {
    require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-deactivator.php';
    RPG_Suite_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'rpg_suite_activate');
register_deactivation_hook(__FILE__, 'rpg_suite_deactivate');

/**
 * Load the autoloader.
 */
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-autoloader.php';
$autoloader = new RPG_Suite_Autoloader();

/**
 * Load the main plugin class directly to avoid autoloader issues.
 */
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-rpg-suite.php';

/**
 * Preload required classes to avoid autoloader issues.
 */
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-activator.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-deactivator.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-die-code-utility.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-event.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/interface-event-subscriber.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-event-dispatcher.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-permalink-debugger.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-permalink-check.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-test-data.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Character/class-character-post-type.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Character/class-character-meta-handler.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Character/class-character-manager.php';
require_once RPG_SUITE_PLUGIN_DIR . 'includes/BuddyPress/class-buddypress-integration.php';

/**
 * Hook to flush rewrite rules when needed
 * Using priority 999 to ensure it runs after post types are registered
 */
add_action('init', array('RPG_Suite_Activator', 'maybe_flush_rewrite_rules'), 999);

/**
 * Initialize the plugin.
 */
function rpg_suite_init() {
    // Initialize the plugin
    global $rpg_suite;
    $rpg_suite = RPG_Suite::get_instance();
}
add_action('plugins_loaded', 'rpg_suite_init');

/**
 * Helper function for global access.
 *
 * @return RPG_Suite
 */
function rpg_suite() {
    global $rpg_suite;
    return $rpg_suite;
}

/**
 * Add admin styles to ensure text visibility in the editor.
 */
function rpg_suite_admin_styles() {
    echo '<style>
        .editor-styles-wrapper {
            color: #333 !important;
        }
    </style>';
}
add_action('admin_head', 'rpg_suite_admin_styles');

/**
 * Debugging function to reset permalinks.
 * This can be called manually to force rewrite rules to regenerate.
 * 
 * Usage: https://yourdomain.com/?rpg_reset_permalinks=1
 */
function rpg_suite_reset_permalinks() {
    if (isset($_GET['rpg_reset_permalinks']) && current_user_can('manage_options')) {
        flush_rewrite_rules();
        wp_die('RPG-Suite permalinks have been reset. <a href="' . home_url() . '">Go back</a>');
    }
}
add_action('init', 'rpg_suite_reset_permalinks', 9999);