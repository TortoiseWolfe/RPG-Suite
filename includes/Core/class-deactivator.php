<?php
/**
 * Fired during plugin deactivation
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 * It handles tasks like cleanup and flushing rewrite rules.
 */
class RPG_Suite_Deactivator {

    /**
     * Deactivate the plugin
     *
     * This method runs when the plugin is deactivated through the WordPress admin.
     * It handles necessary cleanup operations when the plugin is deactivated.
     *
     * @since 0.1.0
     */
    public static function deactivate() {
        // We don't remove capabilities on deactivation to preserve user permissions
        // if the plugin is reactivated
        
        // Flush rewrite rules on the next page load
        delete_option('rpg_suite_flush_rewrite_rules');
        add_action('shutdown', 'flush_rewrite_rules');
    }
}